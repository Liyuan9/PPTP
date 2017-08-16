<?php

namespace app\index\controller;
use app\component\Admin;
use app\index\model\BugtraceModel;
use app\index\model\TestplanModel;
use app\index\model\NeedModel;
use PHPExcel_IOFactory;
use PHPExcel;
use think\Db;
class Testplan extends Admin
{
	private function checkChild($oneList,$projectID)
	{	
		$allList=db('cases_list')->where('projectID',$projectID)->order('list_level asc')->select();
		$temp=0;
		foreach ($allList as $key =>$value){
			if($value['list_pid']==$oneList['id']){
				$temp=1;
				break;
			}
		}
		return $temp;
	}
	private function allcount($re)
	{	
		$count=0;
		foreach ($re as $key =>$value){
			$count=$count+$value['tags'];		
		}
		return $count;
	}
	private function diguiChild($pid,$projectID)
	{	
		$result = array();
		$topList=db('cases_list')->where('projectID',$projectID)->where('list_pid',$pid)->order('list_level asc')->select();
		$caselist=$topList;
		$size = count($caselist);
		
		$i=0;
		for ($i;$i < $size;$i++){
			$keys=self::checkChild($caselist[$i],$projectID);//判断是否有子节点
			$tags = db('cases')->where(array('list'=>$caselist[$i]['id'],'projectID'=>$caselist[$i]['projectID']))->count();
			$caselist[$i]['url'] = url('Cases/caselist').'?id='.$caselist[$i]['projectID'].'&listid='.$caselist[$i]['id'];
			$caselist[$i]['text'] = $caselist[$i]['list_name'];
			
			if ($keys==1){
				$result[$i]=$caselist[$i];
				$re=self::diguiChild($caselist[$i]['id'],$projectID);
				$result[$i]['nodes']=$re;
				$count=self::allcount($re);
				$result[$i]['tags'] = $tags+$count; //关联用例数
			}else{
				$caselist[$i]['tags'] = $tags; //关联用例数
				$result[$i]=$caselist[$i];
			}
		}
		return $result;
		
	}
/*************************测试计划首页渲染*************************/
    public function index()
    {
		$projectID = session('projectID');
       $this->assign('projectID',$projectID);
        $m = new TestplanModel();
        //获取筛选条件
        $condition = $m->getPlanCondition($projectID);
        $this->assign('data',$condition);
        //获取测试计划可导出字段
        $column=Db::query("SELECT column_name,column_comment FROM information_schema.columns WHERE table_name = 'tapd_testplan' and table_schema='JiankeTapd'");
        $base= array();
        $endbase = array();
        $time = '时间';
        $people = '人';
        foreach($column as $v){
            if(strpos($v['column_comment'],$people)){
                $endbase[] = $v;
                unset($v);
            }elseif(strpos($v['column_comment'],$time)){
                $endbase[] = $v;
                unset($v);
            }else{
                $base[] = $v;
            }
        }
        array_pop($base);array_pop($base);
        $this->assign('base',$base);
        $this->assign('endbase',$endbase);
		//获取测试计划列表
		if(session('whereALL')){
           $map = session('whereAll');
            session('whereAll',null);
            $map['projectID'] = $projectID;
            $query = $map;
            $testplan = $m->getPlanList($map,$query);
        }else{
            $map['projectID'] = $projectID;
            $query['id'] = $projectID;
            $testplan = $m->getPlanList($map,$query);
        }
		foreach($testplan as $key=>$item){
            $title = db('version')->where('id',$item['versionID'])->field('title,StartTime,EndTime')->find();
            $item['title'] = $title['title'];
            //$item['startTest'] = $title['StartTime'];
            //$item['endTest'] = $title['EndTime'];
            $testplan[$key] = $item;
        }
        $fanye = array();
        foreach($testplan as $v){
            $fanye[] = $v['id'];
        }
        session('fanye',$fanye);
		$this->assign('testplan',$testplan);
        if(session('view')){
            $this->assign('view',session('view'));
            session('view',null);
        }else{
            $this->assign('view','');
        }
        $this->assign('download','../testplan/outload');
        session('model','testplan');
        return $this->fetch();
    }
/*******************************创建、编辑测试计划页面渲染**********************/
	public function create()
    { 	
		$_POST == null?$needID = '':$needID=$_POST['needID'];
		$versionID = input('get.version');   //从版本页创建计划；
		$projectID=session('projectID');
		$this->assign('projectID',$projectID);
		$types=db('cases_type')->where('projectID',$projectID)->select();
		$this->assign('types',$types);
		
		$testplanID=input('get.planid');   //编辑获取计划
		$this->assign('testplanID',$testplanID);
		if($testplanID){
			$data=db('testplan')->where('id',$testplanID)->find();
			if(!empty($data['upload'])){
                $uploads = explode('|',$data['upload']);
                $filelist = array();
                foreach($uploads as $v){
                    $filelist[]['file'] = json_decode($v,true);
                }
            }else{$filelist =array();}
			$data['title'] = db('version')->where('id',$data['versionID'])->find()['title'];
			$needID = $data['needID'];
		}else{
            $filelist =array();
			if($versionID){
				$data = db('version')->where('id',$versionID)->field('id as versionID,title,iterationID,type,testNeed')->find();
				$needID ==''?$needID = $data['testNeed']:$needID = $needID;
				unset($data['testNeed']);
			}else{
				$data['type'] = '--';
				$data['versionID'] = '0';
				$data['iterationID'] = '0';
				$data['title'] = '';
			}
			$data['responsible'] = '--';
			$data['status'] = '开启';
		}
		$this->assign('info',$data);
		$this->assign('uploads',$filelist);
        $names = $this->getNames($projectID);
        $this->assign('names',$names);
		//获取迭代与版本
		$iteration = db('iteration')->where('projectID',$projectID)->where('status','开启')->field('id,iterationName as planName')->select();
		foreach($iteration as $key=>$vo){
			$version = db('version')->where('iterationID',$vo['id'])->field('id,title')->select();
			$vo['vers'] = $version;
			$iteration[$key] = $vo;
		}
		$this->assign('needID',$needID);
        $this->assign('iteration',json_encode($iteration));
        return $this->fetch();
    }

/*****************************新增、修改测试计划****************/
	public function addplan()
    {	
		$name=session('loginname');
		$data=$_POST;
		unset($data['uploaded']);
		$result = $this->validate($data,'Testplan');
		if(true !== $result){			
			return array('status'=>0,'message'=>$result); 
		}else{
			if($data['publishTime'] == ''){
				$data['publishTime'] = null;
			}
			if($data['startTime'] == ''){
				$data['startTime'] = null;
			}
			if($data['overTime'] == ''){
				$data['overTime'] = null;
			}
			if($data['id']){
                $data['endName']=$name['dealname'];
                $data['endTime']=date("Y-m-d H:i:s",time());
				$info = db('testplan')->where('id',$data['id'])->find();
                $e = db('testplan')->update($data);
                if($e){
					$plan['planid'] = $info['id'];
					db('version')->where('id',$data['versionID'])->update($plan);
					unset($data['endName']);unset($data['endTime']);unset($info['endName']);unset($info['endTime']);
					if($data){
						$this->saveHistory($data['id'],$table='testplan',$info,$data);
					}
					 if($data['responsible'] != $name['dealname']){
                        $this->addMessage($data['id'],'testplan',$data['responsible'],'提测中','-1');
                    }
                    return array('status'=>1,'message'=>'编辑成功','id'=>$data['id']);
                }else{
                    return array('status'=>0,'message'=>'编辑失败');
                }
			}else{
				unset($data['id']);
                $data['creatTime']=date("Y-m-d H:i:s",time());
                $data['creatName']=$name['dealname'];
				if($data['needID'] != ''){
                    $needid = explode(',',$data['needID']);
                    foreach($needid as $v){
                        $before = db('need')->where('id',$v)->field('status,dealname')->find();
                        $after['status'] = '测试排期中';
                        $after['dealname'] = $data['responsible'];
                        $after['endName'] = $name['dealname'];
                        $after['endTimes'] = date("Y-m-d H:i:s",time());
                        $e = db('need')->where('id',$v)->update($after);
                        if($e){
                            unset($after['endName']);unset($after['endTimes']);
                            $this->saveHistory($v,$table='need',$before,$after);
                        }
                    }
                }
                $g=db('testplan')->insertGetId($data);
                if($g){
					$plan['planid'] = $g;
					db('version')->where('id',$data['versionID'])->update($plan);
					if($data['responsible'] != $name['dealname']){
                        $this->addMessage($g,'testplan',$data['responsible'],'提测中','-1');
                    }
                    return array('status'=>1,'message'=>'创建成功','id'=>$g);
                }else{
                    return array('status'=>0,'message'=>'创建失败');
                }
			}	
		}
    }

    /**************获取多条件搜索*****************/
    public function search(){
        $result = $this->validate($_POST,'Search');
        if(true !== $result){
            return array('status'=>0,'message'=>$result); // 验证失败 输出错误信息
            exit();
        }
        $whereALL = array();
        foreach($_POST as $key=>$vo){
            if(empty($vo)||$vo == '--'){
                unset($vo);
            }else{
                $whereALL[$key] = $vo;
            }
        }
        if(isset($whereALL['planName'])){
            $whereALL['planName'] = array("like","%{$whereALL['planName']}%");
        }
        if(isset($whereALL['needID'])){
            $whereALL['needID'] = array("like","%{$whereALL['needID']}%");
        }
        session('whereAll',$whereALL);
        return array('status'=>1,'message'=>'');
    }

    /************************规划与执行页面渲染******************************/
    public function playtest(){
        $planid=input('get.planid');
        $projectID = session('projectID');
        $info = db('testplan')->where('id',$planid)->field('id,planName,responsible,needID,casesID')->find();
        $casenum = 0;
        $neednum = 0;
        $success = 0;
        $fail = 0;
        $block = 0;
        $noplay = 0;
        $linkneed = array();
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
                $need = db('need')->where($mp)->field('id,needName,level,status,endName')->find();
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
                //unset掉已移除计划的用例
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
                        $bug = db('bug')->where($p)->count();
                        $play_case = db('play_case')->where($map)->find();
                        if($play_case){
                            $play_result = db('play_case_result')->where('play_case_id',$play_case['id'])->order('id desc')->field('testresult,run_people')->select();
                            if($play_result){
                                $vi['testresult'] = $play_result[0]['testresult'];
                                $vi['run_people'] = $play_result[0]['run_people'];
                                $vi['playnum'] = count($play_result);
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
                                $vi['playnum'] = '0';
                                $noplay++;
                            }
                            $vi['play_case_people'] = $play_case['play_case_people'];
                        }else{
                            $vi['play_case_people'] = '';
                            $vi['testresult'] = '未执行';
                            $vi['run_people'] = '';
                            $vi['playnum'] = '0';
                            $noplay++;
                        }
                        $vi['bugnum'] = $bug;
                        $caseList[] = $vi;
                    }
                    $need['play'] = '已执行：'.$play.'/'.count($case);
                    $need['case'] = $caseList;
                    $linkneed[] = $need;
                }
            }
        }
        $info['needNum'] = $neednum;
        $info['caseNum'] = $casenum;
        $this->assign('info',$info);
        $this->assign('need',$linkneed);
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
        $this->assign('result',$result);
        //获取需求筛选条件
        $model = new NeedModel();
        $data = $model->getNeedCondition($projectID);
        $this->assign('data',$data);
        //获取需求
        $needlist = $model->getLinkNeed($projectID,0);
        $this->assign('needlist',$needlist);
		//获取项目人员
        $user = $this->getNames($projectID);
        $this->assign('user',json_encode($user));
        return $this->fetch('playtest');
    }


    /*************************批量执行与分配************/
    public function playcase(){
        $info = $_POST;
        $data = array();
        if($info['case'] != ''){
            foreach($info['case'] as $v){
                $v['planID'] = $info['planID'];
                if(isset($info['play_case_people'])){
                    $v['play_case_people'] = $info['play_case_people'];
                }
                $data[] = $v;
            }
        }
        if($info['sign'] == 'play_case_people'){
            // 启动事务
            Db::startTrans();
            try{
                foreach($data as $item){
                    $value = $item;
                    unset($item['play_case_people']);
                    $g = Db::table('tapd_play_case')->where($item)->find();
                    $map['play_case_people'] = $info['play_case_people'];
                    if($g){
                        $e = Db::table('tapd_play_case')->where('id',$g['id'])->update($map);
                    }else{
                        $e = Db::table('tapd_play_case')->insert($value);
                    }
                }
                // 提交事务
                Db::commit();
                return array('status'=>1,'msg'=>'批量操作成功!');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return array('status'=>0,'msg'=>'执行失败!!!');
            }
        }else if($info['sign'] == 'play_result'){
            $map['testresult'] = $info['testresult'];
            $map['realresult'] = $info['realresult'];
            $map['runtime'] = date("Y-m-d H:i:s",time());
            $map['run_people'] = session('loginname.dealname');
            Db::startTrans();
            try{
                foreach($data as $item){
                    $g = Db::table('tapd_play_case')->where($item)->find();
                    $map['play_case_id'] = $g['id'];
                    if($g){
                       $e = Db::table('tapd_play_case_result')->insert($map);
                    }else{
                        return array('status'=>0,'msg'=>'请先分配用例负责人，再执行！');
                    }
                }
                // 提交事务
                Db::commit();
                return array('status'=>1,'msg'=>'批量操作成功');
            }catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return array('status'=>0,'msg'=>'执行失败!');
            }
        }
    }

	//执行测试用例
	public function play_case(){
        if($_POST){
            $info  = $_POST;
            $map['planID'] = $info['planid'];
            $map['needID'] = $info['needid'];
            $map['caseID'] = $info['caseid'];
            $data = db('play_case')->where($map)->find();
            if($data){
                $result['play_case_id'] = $data['id'];
                $result['testresult'] = $info['testresult'];
                $result['realresult'] = $info['realresult'];
                $result['run_people'] = session('loginname.dealname');
                $result['runtime'] = date("Y-m-d H:i:s",time());
                $result['bugID'] = $info['bugid'];
                $g = db('play_case_result')->insert($result);
                if($g){
                    return array('status'=>1,'msg'=>'操作成功');
                }else{
                    return array('status'=>0,'msg'=>'执行测试用例失败');
                }
            }else{
                return array('status'=>0,'msg'=>'请先分配用例负责人，再执行！');
            }
        }else{
            $map['planID'] = input('get.planid');
            $map['needID'] = input('get.needid');
            $map['caseID'] = input('get.caseid');
            if($map['planID']!=''&&$map['needID']!=''&&$map['caseID']!=''){
                $responsible = db('testplan')->where('id',$map['planID'])->field('responsible')->find();
                $info = db('cases')->where('id',$map['caseID'])->find();
                if($info){
                    $info['needid'] = $map['needID'];
                    $info['planid'] = $map['planID'];
                    $info['responsible'] = $responsible['responsible'];
                }
                $this->assign('info',$info);
            }
            return $this->fetch('playcase');
        }
	}
	//查看执行结果
	public function playresult(){
        $map['a.planID'] = input('get.planid');
        $map['a.needID'] = input('get.needid');
        $map['a.caseID'] = input('get.caseid');
        $case = array();
        $result= array();
        if($map){
            $case = db('cases')->where('id',$map['a.caseID'])->field('id,casesName')->find();
            $plan = db('testplan')->where('id',$map['a.planID'])->field('responsible')->find();
            $result = db('play_case')->alias('a')->join('tapd_play_case_result','a.id=tapd_play_case_result.play_case_id')->where($map)->field('a.play_case_people,tapd_play_case_result.*')->select();
            $case['responsible'] = $plan['responsible'];
        }
        $this->assign('case',$case);
        $this->assign('result',$result);
		return $this->fetch('playresult');
	}


	//关联缺陷
	public function linkbug(){
        $projectID = session('projectID');
        $map['planID'] = input('get.planid');
        $map['needID'] = input('get.needid');
        $map['caseID'] = input('get.caseid');
        $linkID = json_encode($map);
		$model = new BugtraceModel();
        $condition = $model->getBugCondition($projectID);
		$this->assign('data',$condition);
        if(session('whereAll')){
            $data = session('whereAll');
            session('whereAll',null);
        }
        $data['projectID'] = $projectID;
        $data['linkID'] = array('NOT LIKE',"%{$linkID}%");
        $buglist = db('bug')->where($data)->order('id desc')->field('id, bugName,status,level,serious,creatName,creatTime')->select();
        $this->assign('buglist',$buglist);
        $this->assign('info',$map);
        session('linkID',$linkID);
        $this->assign('linkID',$linkID);
		return $this->fetch('linkbug');
	}

/***********************测试计划详情页渲染*******************/
	public function desc(){
		$planID = input('get.planid');
		$data = db('testplan')->alias('a')->join('version b','a.versionID = b.id','left')->join('iteration c','a.iterationID = c.id','left')->where('a.id',$planID)->field('a.*,b.title,b.StartTime as startTest,b.EndTime as endTest,c.iterationName')->find();
			//获取变更历史
		$history = db('history')->where('changeID',$planID)->where('changeTable','testplan')->order('addTime desc')->select();
		$history = $this->checkHistory($history);
		$data['historynum'] = count($history);
		$this->assign('history',$history);
		$this->assign('info',$data);
        if(!empty($data['upload'])){
            $uploads = explode('|',$data['upload']);
            $filelist = array();
            foreach($uploads as $v){
                $filelist[]['file'] = json_decode($v,true);
            }
        }else{
            $filelist = array();
        }
        //上一条，下一条
        $fanye = session('fanye');
        $next = '';
        $prev = '';
        for($i=0;$i<count($fanye);$i++){
            if($fanye[$i] == $planID){
                if($i-1<0){
                    $prev = $fanye[$i];
                }else{
                    $prev = $fanye[$i-1];
                }
                if($i+1>=count($fanye)){
                    $next = $fanye[$i];
                }else{
                    $next = $fanye[$i+1];
                }
                break;
            }
        }
        $this->assign('next',$next);
        $this->assign('prev',$prev);
        $this->assign('filelist',$filelist);
        $model = new TestplanModel();
        $play_result = $model->getPlay($planID);
        $result = $play_result['result'];
        $result['needNum'] = $play_result['info']['needNum'];
        $result['caseNum'] = $play_result['info']['caseNum'];
		$result['bugNum'] = $play_result['info']['bugNum'];
        if($result['caseNum'] == 0){
            $result['successrate'] = 0 .'%';
        }else{
            $result['successrate'] = round($result['success']/$result['caseNum'],3)*100 .'%';
        }
		$bugList = $play_result['buglist'];
        $this->assign('buglist',$bugList);
        $this->assign('result',$result);
		return $this->fetch('desc');
	}


    /*************导出测试计划******************/
    public function outload(){
        $data=json_decode($_POST["datas"]);
        $dataname=json_decode($_POST["dataname"]);
        $datacount=count($dataname);
        $listall=db('testplan')->where('projectID',$_POST['projectID'])->field(implode(',',$data))->select();
        $list = array();
        if($listall){
            foreach($listall as $vo ){
                if(isset($vo['iterationID'])){
                    $iterationname = db('iteration')->where('id',$vo['iterationID'])->field('iterationName')->find();
                    $vo['iterations'] = $iterationname['iterationName'];
                }
                if(isset($vo['needID'])){
                    $needid = explode(',',$vo['needID']);
                    $needname = '';
                    foreach($needid as $v){
                        $need = db('need')->where('id',$v)->field('needName')->find();
                        if($need){
                            $needname = $needname.','.$need['needName'];
                        }
                    }
                    $needname = substr($needname,1);
                    $vo['needID'] = $needname;
                }
                if(isset($vo['casesID'])){
                    $caseid = explode(',',$vo['casesID']);
                    $casename = '';
                    foreach($caseid as $v){
                        $cases = db('cases')->where('id',$v)->field('casesName')->find();
                        if($cases){
                            $casename = $casename.','.$cases['casesName'];
                        }
                    }
                    $casename = substr($casename,1);
                    $vo['caseID'] = $casename;
                }
                if(isset($vo['content'])){
                    $vo['content'] = htmltoexcel($vo['content']);
                }
                if(isset($vo['upload'])){
                    $uploadfile = '';
                    $upfile = '';
                    if(!empty($vo['upload'])){
                        $uploads = explode('|',$vo['upload']);
                        $filelist = array();
                        foreach($uploads as $vv){
                            $filelist[] = json_decode($vv,true);
                            foreach($filelist as $vvv){
                                $upfile = $vvv['path'].$vvv['title'];
                            }
                            $uploadfile = $uploadfile.'，\r\n'.$upfile;
                        }
                        $uploadfile = substr($uploadfile,1);
                    }else{
                        $uploadfile = '';
                    }
                    $vo['upload'] = $uploadfile;
                }
                $list[] = $vo;
            }
        }
        vendor("PHPExcel.PHPExcel");
        $PHPExcel = new PHPExcel(); //实例化PHPExcel类，类似于在桌面上新建一个Excel表格`
        $PHPSheet = $PHPExcel->getActiveSheet(); //获得当前活动sheet的操作对象
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        $PHPSheet->setTitle('demo'); //给当前活动sheet设置名称
        for($i=0;$i<$datacount;$i++){
            $PHPSheet->setCellValue($cellName[$i].'1',$dataname[$i]);
        }
        foreach($list as $key=>$value){
            $k=$key+2;
            for($j=0;$j<$datacount;$j++){
                $PHPSheet->setCellValue($cellName[$j].$k,$value[$data[$j]]);
            }
        }
        $PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel2007');//按照指定格式生成Excel文件，‘Excel2007’表示生成2007版本的xlsx，‘Excel5’表示生成2003版本Excel文件
        $filename = session('projectName').'--测试计划导出'.date('Ymdhms',time()).'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');//禁止缓存
        $PHPWriter->save("php://output");
    }
	
	
     /*************测试报告******************/
    public function report(){
        $planid = input('get.planid');
        //$info = db('testplan')->alias('a')->join('version b','a.versionID = b.id','left')->where('a.id',$planid)->field('a.*,b.title,b.StartTime as startTest,b.EndTime as endTest,b.countTime,b.creatTime as createVersion')->find();
        $info = db('testplan')->where('id',$planid)->find();
		$version = db('version')->alias('a')->join('iteration b','a.iterationID = b.id')->where('a.id',$info['versionID'])->field('a.*,b.iterationName')->find();
		$hour=round((strtotime($version['EndTime'])-strtotime($version['StartTime']))%86400/3600,2);
		$info['countTime'] = $hour;
		$now = date('Y-m-d',time());
		$info['delayDay'] = round((strtotime($now)-strtotime($info['publishTime']))%86400/86400,2);
		$this->assign('info',$info);
		$this->assign('version',$version);
        $needid = explode(',',$info['needID']);
        $needList = array();
		$bugnum = 0;
		$play_result['height'] = 0;
        $play_result['middle'] = 0;
		$play_result['base'] = 0;
		$play_result['lower'] = 0;
		$play_result['overbug'] = 0;
		$play_result['refusebug'] = 0;
		$play_result['openbug'] = 0;
		$play_result['closebug'] = 0;
        foreach($needid as $v){
            $need = db('need')->alias('a')->join('need_kind b','a.needKind = b.id','left')->where('a.id',$v)->field('a.id,a.needName,a.level,a.status,a.needKind,b.list_name')->find();
			if($need){
				if($need['needKind'] == '-1'){
					$need['list_name'] = '未分类';
				}
				$linkbug = '"planID":"'.$planid.'","needID":"'.$need['id'].'"';
				$bug = db('bug')->where('linkID','like',"%{$linkbug}%")->field('id,bugName,status,serious,dealmethod')->select();
				$bugnum += count($bug);
				$need['linkBug'] = $bug;
				if($bug){
					foreach($bug as $item){
						switch ($item['status']){
							case '已解决':
								$play_result['overbug']++;
								break;
							case '已关闭':
								$play_result['closebug']++;
								break;
							case '拒绝解决':
								$play_result['refusebug']++;
								break;
							default :
								$play_result['openbug']++;
								break;
						}
						switch ($item['serious']){
							case '致命级':
								$play_result['height']++;
								break;
							case '严重级':
								$play_result['middle']++;
								break;
							case '一般级':
								$play_result['base']++;
								break;
							default :
								$play_result['lower']++;
								break;
						}
					}
					$need['status'] = 'bug修复中';
				}
                $needList[] = $need;
			}
			
        }
		$linkversion = db('version')->where('planid',$planid)->count();
        $play_result['versionNum'] = $linkversion;
        $play_result['needNum'] = count($needList);
		$play_result['bugnum'] = $bugnum;
		if($bugnum>0){
			$play_result['height'] = round($play_result['height']/$bugnum,3)*100 .'%';
			$play_result['middle'] = round($play_result['middle']/$bugnum,3)*100 .'%';
			$play_result['base'] = round($play_result['base']/$bugnum,3)*100 .'%';
			$play_result['lower'] = round($play_result['lower']/$bugnum,3)*100 .'%';
		}else{
			$play_result['height'] = 0;
			$play_result['middle'] = 0;
			$play_result['base'] = 0;
			$play_result['lower'] = 0;
		}
        $this->assign('play_result',$play_result);
        $this->assign('need',$needList);
        $user=db('project_group')->where('projectID',session('projectID'))->find();
		
        if($user['com']){
            $users=$user['ad'].','.$user['com'];
        }else{
            $users=$user['ad'];
        }
        $users = explode(',',$users);
        $names=[];
        foreach ($users as $value){
            $ss=db('user')->where('id',$value)->field('dealname,email')->find();
            if($ss['email'] != '' && $ss['email'] != '--'){
                $names[$value]=$ss['dealname'].'&lt'.$ss['email'].'&gt';
            }
        }
        $subject = session('projectName').'>>'.$info['planName'].'>>'.$version['title'];
        $this->assign('subject',$subject);
        $this->assign('name',$names);
        return $this->fetch('report');

    }
	
	
	//发送邮件
	public function sendmail(){
        $info = $_POST;
		$step = input('get.step');
        if($info){
			if($step == 'send'){
				if(isset($info['id']) && !isset($info['subject'])){
					$info = db('mail')->where('id',$info['id'])->field('sendto,copysend,subject,message')->find();
				}
				$subject = $info['subject'];
				$content = $info['message'];
				$sendto =array();
				if($info['sendto']){
					$to = explode('|',$info['sendto']);
					foreach($to as $v){
						if(strpos($v,'<')){
							$cc = explode('<',$v);
							$cc = explode('>',$cc[1]);
							$sendto[] = $cc[0];
						}else{
							$sendto[] = $v;
						}
						
					}
				}
				$csend =array();
				if($info['copysend']){
					$copyto = explode('|',$info['copysend']);
					foreach($copyto as $v){
						if(strpos($v,'<')){
							$cc = explode('<',$v);
							$cc = explode('>',$cc[1]);
							$csend[] = $cc[0];
						}else{
							$csend[] = $v;
						}
					}
				}
                if(send_email($sendto,$subject,$content,$csend)){
                    return array('status'=>'1','msg'=>'邮件发送成功');
                }else{
                    return array('status'=>'0','msg'=>'邮件发送失败');
                }
            }elseif($step == 'save'){
                $data = $info;
                $data['fromto'] = session('loginname.dealname').'<'.session('loginname.email').'>';
                $data['savetime'] = date('Y-m-d',time());
				$data['status'] = 0;
                $e = db('mail')->insert($data);
                if($e){
                    return array('status'=>'1','msg'=>'邮件保存成功,是否前往邮件中心？');
                }else{
                    return array('status'=>'0','msg'=>'邮件保存失败');
                }
            }
        }
    }

}
