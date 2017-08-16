<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-5-16
 * Time: 上午11:41
 */

namespace app\index\model;
use think\Model;

class TestplanModel extends Model
{

    //获取测试计划筛选条件
    public function getPlanCondition($projectID){

        $user=db('project_group')->where('projectID',$projectID)->find();
        if($user['com']){
            $users=$user['ad'].','.$user['com'];
        }else{
            $users=$user['ad'];
        }
        $users = explode(',',$users);
        $names=[];
        foreach ($users as $value){
            $ss=db('user')->where('id',$value)->field('dealname')->find();
            $people['id']=$ss['dealname'];
            $people['name']=$ss['dealname'];
            $names[] = $people;
        }
        $type[0]['id'] = '--';  $type[1]['name'] = '--';
        $type[1]['id'] = '功能测试';  $type[1]['name'] = '功能测试';
        $type[2]['id'] = '性能测试';  $type[2]['name'] = '性能测试';
        $type[3]['id'] = '接口测试';  $type[3]['name'] = '接口测试';
        $type[4]['id'] = '兼容性测试';  $type[4]['name'] = '兼容性测试';


        $status[0]['id'] = '--';  $status[0]['name'] = '--';
        $status[1]['id'] = '开启';  $status[1]['name'] = '开启';
        $status[2]['id'] = '关闭';  $status[2]['name'] = '关闭';


        $need = db('need')->where('projectID',$projectID)->field('id,needName as name')->select();
        /*$iterations = db('iteration')->where('projectID',$projectID)->field('id,iterationName as name')->select();*/

        $condition[0]['name']='planName';$condition[0]['comment']='计划名称';
        $condition[1]['name']='status';$condition[1]['comment']='计划状态';$condition[1]['children']=$status;
        $condition[2]['name']='type';$condition[2]['comment']='计划类型';$condition[2]['children']=$type;
        $condition[3]['name']='responsible';$condition[3]['comment']='测试负责人';$condition[3]['children']=$names;
        $condition[4]['name']='creatName';$condition[4]['comment']='创建人';$condition[4]['children']=$names;
        $condition[5]['name']='id';$condition[5]['comment']='计划ID';
        $condition[6]['name']='needID';$condition[6]['comment']='关联需求';$condition[6]['children'] = $need;
        /*$condition[7]['name']='iterations';$condition[7]['comment']='迭代';$condition[7]['children'] = $iterations;*/
        return $condition;
    }

    //获取测试计划列表
    public function getPlanList($map,$query){
        $planlist = db('testplan')->where($map)->order('id desc')->paginate(20,false,['query' => $query]);
        return $planlist;
    }



    public function getPlay($planid){
        $testplan_result = array();
        $info = db('testplan')->where('id',$planid)->field('id,planName,responsible,needID,casesID')->find();
        $casenum = 0; //用例数
        $neednum = 0; //需求数
		$bugnum = 0; //缺陷数
        $success = 0; //用例通过
        $fail = 0; //用例失败
        $block = 0; //用例阻塞
        $noplay = 0;//用例未执行
        $linkneed = array();
		$buglist = array();
		//获取计划中不关联的用例
        if(!empty($info['casesID'])){
            $unlink = explode('|',$info['casesID']);
            foreach($unlink as $key=>$v){
                $unlink[$key] = json_decode($v,true);
            }
        }
        if(!empty($info['needID'])){
            $needid = explode(',',$info['needID']);
            if(session('whereAll')){
                $mp = session('whereAll');
                session('whereAll',null);
            }
            foreach($needid as $v){
                $caseList = array();
                $mp['id'] = $v;
                $need = db('need')->where($mp)->field('id,needName,level,status')->find();
                $case = db('cases')->where('needID','like',"%{$v}%")->field('id,casesName,grade,status,needID')->select();
				foreach($case as $key=>$item){
					$lneedID = explode(',',$item['needID']);
					$i = 0;
					foreach($lneedID as $vv){
						if($vv == $v){
							$i++;
						}
					}
					if($i==0){
						unset($case[$key]);
					}
				}
                if(!empty($unlink)){
                    foreach($unlink as $it){
                        if($it['needID'] == $v){
                            $ite = explode(',',$it['caseid']);
                            foreach($ite as $vv){
                                foreach($case as $key=>$ca){
                                    if($ca['id'] == $vv){
                                        unset($case[$key]);
                                    }
                                }
                            }
                        }
                    }
                }
                $casenum = $casenum + count($case);
                $map['planID'] = $planid;
                $map['needID'] = $v;
                if($need){
                    $neednum++;
                    $play = 0;
                    foreach($case as $vi){
                        $map['caseID'] = "$vi[id]";
                        $linkBug = json_encode($map);
                        $p['linkID'] = array('like',"%{$linkBug}%");
                        $bug = db('bug')->where($p)->field('id,bugName,serious,level,status,dealby,creatName,creatTime')->select();
                        foreach($bug as $item){
                            $item['linkNeed']['id'] = $need['id'];
                            $item['linkNeed']['name'] = $need['needName'];
                            $item['linkCase']['id'] = $vi['id'];
                            $item['linkCase']['name'] = $vi['casesName'];
                            $buglist[] = $item;
                        }
                        $play_case = db('play_case')->where($map)->find();
                        if($play_case){
                            $play_result = db('play_case_result')->where('play_case_id',$play_case['id'])->order('id desc')->field('testresult,run_people,runtime,realresult')->select();
                            if($play_result){
                                $vi['testresult'] = $play_result[0]['testresult'];
                                $vi['run_people'] = $play_result[0]['run_people'];
                                $vi['playnum'] = count($play_result);
								$vi['runtime'] = $play_result[0]['runtime'];
                                $vi['text'] = $play_result[0]['realresult'];
                                if($vi['testresult'] == '通过'){
                                    $success++;
                                }else if($vi['testresult'] == '不通过'){
                                    $fail++;
                                }else{
                                    $block++;
                                }
                                $play++;
                            }else{
                                $vi['testresult'] = '未执行';
                                $vi['run_people'] = '';
								$vi['text'] = '';
                                $vi['runtime'] = '';
                                $vi['playnum'] = '0';
                                $noplay++;
                            }
                            $vi['play_case_people'] = $play_case['play_case_people'];
                        }else{
                            $vi['play_case_people'] = '';
                            $vi['testresult'] = '未执行';
                            $vi['run_people'] = '';
							 $vi['text'] = '';
                            $vi['runtime'] = '';
                            $vi['playnum'] = '0';
                            $noplay++;
                        }
                          $vi['bugnum'] = count($bug);
                        $caseList[] = $vi;
                        $bugnum += count($bug);
                    }
                    $need['play'] = '已执行：'.$play.'/'.count($case);
                    $need['case'] = $caseList;
                    $linkneed[] = $need;
					$map['caseID'] = "";
                        $linkBug = json_encode($map);
                        $p['linkID'] = array('like',"%{$linkBug}%");
					$bugall = db('bug')->where($p)->field('id,bugName,serious,level,status,dealby,creatName,creatTime')->select();
					if($bugall){
						foreach($bugall as $vvv){
							$vvv['linkNeed']['id'] = $need['id'];
                            $vvv['linkNeed']['name'] = $need['needName'];
							$vvv['linkCase'] = array();
							$buglist[] = $vvv;
						}
					}
					
                }
            }
        }
        $info['needNum'] = $neednum;
        $info['caseNum'] = $casenum;
		$info['bugNum'] = count($buglist);
        $testplan_result['info'] = $info;
        $testplan_result['need'] = $linkneed;
        //执行结果
        $result['success'] =  $success;
        $result['fail'] = $fail;
        $result['block'] = $block;
        $result['noplay'] = $noplay;
        if($casenum>0){
            $rate = round(($casenum-$noplay)/$casenum,3)*100;
            $result['rate'] = $rate.'%';
        }else{
            $result['rate'] = '0%';
        }
        $testplan_result['result'] = $result;
		$testplan_result['buglist'] = $buglist;
        return $testplan_result;
    }

}