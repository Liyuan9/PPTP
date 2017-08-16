<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-4-24
 * Time: 下午5:27
 */
namespace app\index\controller;
use app\component\Admin;
use app\index\model\NeedModel;
use PHPExcel_IOFactory;
use PHPExcel;
use think\Paginator;
use think\Db;
class Need extends Admin{
    private function checkChild($oneList,$projectID)
    {
        $allList=db('need_kind')->where('projectID',$projectID)->order('list_level asc')->select();
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
        $topList=db('need_kind')->where('projectID',$projectID)->where('list_pid',$pid)->order('list_level asc')->select();
        $needlist=$topList;
        $size = count($needlist);
        $i=0;
        for ($i;$i < $size;$i++){
            $keys=self::checkChild($needlist[$i],$projectID);//判断是否有子节点
            $tags = db('need')->where(array('needKind'=>$needlist[$i]['id'],'projectID'=>$needlist[$i]['projectID']))->count();
            $needlist[$i]['url'] = url('Need/index').'?id='.$needlist[$i]['projectID'].'&listid='.$needlist[$i]['id'];
            $needlist[$i]['text'] = $needlist[$i]['list_name'];
            if ($keys==1){
                $result[$i]=$needlist[$i];
                $re=self::diguiChild($needlist[$i]['id'],$projectID);
                $result[$i]['nodes']=$re;
                $count=self::allcount($re);
                $result[$i]['tags'] = $tags+$count; //关联需求数
            }else{
                $needlist[$i]['tags'] = $tags; //关联需求数
                $result[$i]=$needlist[$i];
            }
        }
        return $result;

    }

    public function index(){
        $listID = input('get.listid');
        $projectID = session('projectID');
        $this->assign('projectID',$projectID);
        $item = new NeedModel();
        //获取需求筛选条件
        $condition = $item->getNeedCondition($projectID);
        $this->assign('data',$condition);
        //获取分类
        $typelist=self::diguiChild(0,$projectID);
        $this->assign('treelist',json_encode($typelist));
        $unList = db('need')->where('projectID',$projectID)->where('needKind','-1')->count();
        $this->assign('unList',$unList);
        $allList = db('need')->where('projectID',$projectID)->count();
        $this->assign('allList',$allList);

        //获取需求可导出字段
        $column = $item->getNeedColumn();
        $this->assign('base',$column['base']);
        $this->assign('endbase',$column['people']);

        if(session('whereAll')){
            $k=json_encode(session('whereAll'));
            $map = session('whereAll');
            session('whereAll',null);
            $map['projectID'] = $projectID;
            $query = $map;
            $list = $item->getNeedList($map,$query);
        }else{
            $map['projectID'] = $projectID;
            $query['id'] = $projectID;
            if($listID){
                if($listID != '-1'){
                    $listALL=self::listID($listID,$projectID);
                    $data = db('need')->where('projectID',$projectID)->where('needKind','in',$listALL)->order('id', 'desc')->paginate(20,false,['query' => array('id'=>$projectID,'needKind'=>$listID)]);
                    $page = $data->render();
                    $list['page'] = $page;
                    $need_list = $item->getNeedTree($data);
                    $fanye = $item->getfanye($need_list);
                    $list['fanye'] = $fanye;
                    $list['tree_list'] = $need_list;
                }else{
                    $map['needKind'] = $listID;
                    $query['needKind'] = $listID;
                    $list = $item->getNeedList($map,$query);
                }
            }else{
                $list = $item->getNeedList($map,$query);
            }
        }
        session('needpage',$list['fanye']);
        $this->assign('tablelist', json_encode($list['tree_list']));
        $this->assign('page',$list['page']);
        $this->assign('download','../Need/outload');
		//获取项目
		$project = db('project')->where('status','eq','进行中')->field('projectName,id')->select();
		$projectlist = array();
        foreach($project as $v){
			if($v['id'] != session('projectID')){
				$name = session('loginname');
				$position = $name['position'];
				if(!strstr($position,'总监') && !strstr($position,'测试')){
					$ma['projectID'] = $v['id'];
					$ma['ad'] = array('like',"%{$name['id']}%");
					$mp['projectID'] = $v['id'];
					$mp['com'] = array('like',"%{$name['id']}%");
					$user = db('project_group')->where($ma)->find();
					if(!$user){
						$user = db('project_group')->where($mp)->find();
					}
					if($user){
						$projectlist[] = $v;
					}
				}else{
					$projectlist[] = $v;
				}
				
			}
        }     
		$this->assign('project',json_encode($projectlist));
		//获取属性值
		//名称*优先级*类型*业务价值*来源*开始时间*结束时间*处理人*状态*需求提出者*需求分类
		$value = $condition;
		$condi[0]['name'] = 'startTime'; $condi[0]['comment'] = '开始时间'; 
		$condi[1]['name'] = 'endTime'; $condi[1]['comment'] = '结束时间'; 
		$value = array_merge($value,$condi);
		$this->assign('column',json_encode($value));
		//获取分类列表
		$list=self::diguiChild(0,$projectID);
        $this->assign('treelist',json_encode($list));
        session('model','need');
        return $this->fetch('index');
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
        if(isset($whereALL['needName'])){
            $whereALL['needName'] = array("like","%{$whereALL['needName']}%");
        }
        session('whereAll',$whereALL);
        return array('status'=>1,'message'=>'');
    }

    /******************创建需求****************/
    public function create(){
        $needid = input('get.needid');
        $needKind = input('get.needkind');
        $pidNeed = input('get.pidneed');
        $projectID =session('projectID');
        $filelist =array();
        if($needid){
            $info = db('need')->where('id',$needid)->find();
            if(!empty($info['upload'])){
                $uploads = explode('|',$info['upload']);
                foreach($uploads as $v){
                    $filelist[]['file'] = json_decode($v,true);
                }
            }
            if(!empty($info['pidNeed'])&& $info['pidNeed']!=0){
                $needName = db('need')->where('id',$info['pidNeed'])->field('needName')->find();
                $info['pidName'] = $needName['needName'];
            }else{
                $info['pidName'] = '';
            }
        }else{
            if($needKind){
                $info['needKind'] = $needKind;
            }else{
                $info['needKind'] = '-1';
            }
            if($pidNeed){
                $needName = db('need')->where('id', $pidNeed)->field('needName,type,origin,value,needKind')->find();
                $info['pidName'] = $needName['needName'];
                $info['needKind'] = $needName['needKind'];
                $info['value'] = $needName['value'];
                $info['origin'] = $needName['origin'];
                $info['type'] = $needName['type'];
            }else{
                $pidNeed = 0;
                $info['pidName'] = '';
                $info['type'] = '--';
                $info['origin'] = '--';
            }
            $info['level'] = '--';
            $info['pidNeed'] = $pidNeed;
            $info['needby'] = session('loginname.dealname');
			$info['dealname'] = session('loginname.dealname');
        }
        $item = new NeedModel();
        //获取需求列表
        $needList = $item->getLinkNeed($projectID,$needid);
        $this->assign('needList',$needList);
		//获取迭代
		$iteration = db('iteration')->where('projectID',$projectID)->where('status','开启')->field('id,iterationName')->select();
		$this->assign('iteration',$iteration);
        //获取需求筛选条件
        $condition = $item->getNeedCondition($projectID);
        $this->assign('data',$condition);

        //获取成员列表
        $names = $this->getNames($projectID);
        $this->assign('names',$names);
        //获取分类列表
        $list=self::diguiChild(0,$projectID);
        $this->assign('treelist',json_encode($list));
        $this->assign('info',$info);
        $this->assign('uploads',$filelist);
        return $this->fetch('create');
    }

    /***************************新增、编辑需求********************/
    public function addneed(){
        $info = $_POST;
        $result = $this->validate($info,'Need');
        if(true !== $result){
            return array('status'=>0,'message'=>$result); // 验证失败 输出错误信息
        }else{
            if($info['type'] == '--'){
                return array('status'=>0,'message'=>'需求类型不能为空');
            }
            if($info['value'] == '--'){
                return array('status'=>0,'message'=>'业务价值不能为空');
            }
            if(isset($info['needby'])&&$info['needby'] == ''){
                $info['needby'] = session('loginname.dealname');
            }
			if($info['startTime'] == ''){
				$info['startTime'] = null;
			}
			if($info['endTime'] == ''){
				$info['endTime'] = null;
			}
            $info['projectID'] = session('projectID');
            if(!isset($info['needKind'])){
                $info['needKind'] = '-1';
                if(isset($info['pidNeed'])){
                    $data = db('need')->where('id',$info['pidNeed'])->field('needKind,type,origin,value')->find();
                    $info['needKind'] = $data['needKind'];
                    $info['type'] = $data['type'];
                    $info['origin'] = $data['origin'];
                    $info['value'] = $data['value'];
                    $info['needby'] = session('loginname.dealname');
                }
            }
            if(isset($info['id'])&&$info['id'] != ''){
				$need = db('need')->where('id',$info['id'])->field('creatName,status')->find();
				$name = session('loginname.dealname');
				if($need['creatName'] == $name ){
					$info['endName'] = $name;
					$info['endTimes'] = date("Y-m-d H:i:s",time());
					$data = db('need')->where('id',$info['id'])->find();
					$e = db('need')->update($info);
					if($e){
						unset($info['endName']);unset($info['endTimes']);unset($data['endName']);unset($data['endTimes']);
						unset($info['pidNeed']);unset($data['pidNeed']);
						if($data){
							$this->saveHistory($info['id'],$table='need',$data,$info);
						}
						if($info['dealname'] != $name){
							$this->addMessage($info['id'],'need',$info['dealname'],$need['status'],0);
						}
                        if($info['sendto'] != $data['sendto']){
                            db('message')->where('model','need')->where('linkID',$info['id'])->where('projectID',session('projectID'))->update(['type'=>'-1']);
                            if($info['sendto'] != ''){
                                $sendto = explode('|',$info['sendto']);
                                foreach($sendto as $v){
                                    $da['model'] = 'need';
                                    $da['user'] = session('loginname.dealname');
                                    $da['time'] = date('Y-m-d H:i:s',time());
                                    $da['linkID'] = $info['id'];
                                    $da['dealname'] = $v;
                                    $da['projectID'] = session('projectID');
                                    $da['status'] = $data['status'].'[抄送]';
                                    $da['endtime'] = date('Y-m-d H:i:s',time()+432000);
                                    $da['type'] = '0';
                                    db('message')->insert($da);
                                }
                            }
                        }
						return array('status'=>1,'message'=>'编辑成功','id'=>$info['id']);
					}else{
						return array('status'=>0,'message'=>'编辑失败');
					}	
				}else{
					return array('status'=>0,'message'=>'你不是此需求的创建者，不能对此需求进行编辑');
				}
            }else{
				if(!isset($info['needby'])){
                    $info['needby'] = session('loginname.dealname');
                }
                $info['status'] = '新需求';
                $info['creatName'] = session('loginname.dealname');
                $info['creatTime'] = date("Y-m-d H:i:s",time());
                $g=db('need')->insertGetId($info);
                if($g){
					if($info['dealname'] != $info['creatName']){
                        $this->addMessage($g,'need',$info['dealname'],$info['status'],0);
                    }
                    if($info['sendto'] != ''){
                        $sendto = explode('|',$info['sendto']);
                        foreach($sendto as $v){
                            $da['model'] = 'need';
                            $da['user'] = session('loginname.dealname');
                            $da['time'] = date('Y-m-d H:i:s',time());
                            $da['linkID'] = $g;
                            $da['dealname'] = $v;
                            $da['projectID'] = session('projectID');
                            $da['status'] = $info['status'].'[抄送]';
                            $da['endtime'] = date('Y-m-d H:i:s',time()+432000);
                            $da['type'] = '0';
                            db('message')->insert($da);
                        }
                    }
                    return array('status'=>1,'message'=>'新建成功','id'=>$g);
                }else{
                    return array('status'=>0,'message'=>'新建失败');
                }
            }
        }

    }

    /*******************创建，修改，删除目录************************/
    public function addList(){
        try{
            $data=$_POST;
            if($data['sign']=="del"){
                // 启动事务
                Db::startTrans();
                try{
                    Db::table('tapd_need_kind')->delete($data['list_id']);

                    Db::table('tapd_need')->where('needKind',$data['list_id'])->update(['needKind'=>'-1']);

                    // 提交事务
                    Db::commit();
                    return array('status'=>1,'message'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return array('status'=>0,'message'=>'删除失败');
                }
                db('need_kind')->delete($data['list_id']);
                return array('status'=>1,'message'=>'删除成功');
            }elseif($data['sign']=="add"){
                $level=db('need_kind')->where('id',$data['list_id'])->field('list_level')->find();
                $addlist['list_level']=$level['list_level']+1;
                $addlist['projectID']= session('projectID');
                $addlist['list_pid']=$data['list_id'];
                $addlist['list_name']=$data['list_name'];
                $addlist['depict']=$data['desc'];
                db('need_kind')->insert($addlist);
                return array('status'=>1,'message'=>'添加成功');
            }elseif($data['sign']=="edit"){
                $edit['id']=$data['list_id'];
                $edit['list_name']=$data['list_name'];
                $edit['depict']=$data['desc'];
                db('need_kind')->update($edit);
                return array('status'=>1,'message'=>'修改成功');
            }
        }catch (\Exception $e) {
            return array('status'=>0,'message'=>'操作失败');
        }
    }

    private function listID($listID,$projectID)
    {
        static $result =array();
        array_push($result,$listID);
        $keys=db('need_kind')->where('projectID',$projectID)->where('list_pid',$listID)->order('list_level asc')->select();
        $size = count($keys);
        if ($size){
            $i=0;
            for ($i;$i < $size;$i++){
                self::listID($keys[$i]['id'],$projectID);
            }
        }
        return $result;
    }

    /*****************关联需求页面渲染*******************/
    public function linkneed(){
        $planid = input('get.planid');
        $projectID = session('projectID');
        if(session('whereAll')){
            $map = session('whereAll');
            session('whereAll',null);
        }
        $needid = db('testplan')->where('id',$planid)->field('needID')->find();
        $needID = explode(',',$needid['needID']);
        $map['projectID'] = $projectID;
        $item = new needModel();
        $data = $item->getNeedCondition($projectID);
        $this->assign('data',$data);
        $this->assign('planid',$planid);
        $needlist = db('need')->where($map)->select();
        foreach($needID as $v){
            foreach($needlist as $key=>$vi){
                if($v == $vi['id']){
                    unset($needlist[$key]);
                }
            }
        }
        $this->assign('needlist',$needlist);
        return $this->fetch('linkneed');
    }

    //导入需求
    public function import(){
        //获取分类列表
        $list=self::diguiChild(0,session('projectID'));
        $this->assign('treelist',json_encode($list));
        return $this->fetch('import');
    }

    public function desc(){
        $id = input('get.needid');
		$project = input('get.projectID');
        $msgid = input('get.msg');
        if($project){
            session('projectID',$project);
			if($msgid){
				$dat['type'] = 1;
            db('message')->where('id',$msgid)->update($dat);
            $msg['type'] = 0;
            $msg['dealname'] = session('loginname.dealname');
            $msgnum=db('message')->where($msg)->count();
            session('msgnum',$msgnum);
			}
        }
        if($id){
            $info = db('need')->where('id',$id)->find();
            if($info){
                $projectID=$info['projectID'];
                if($info['needKind'] == '-1'){
                    $info['list_name'] = '未规划';
                }else{
                    $needKind=db('need_kind')->where('id',$info['needKind'])->field('list_name')->find();
                    if($needKind){
                        $info['list_name'] = $needKind['list_name'];
                    }else{
                        $info['list_name'] = '未规划';
                    }
                }
                if(!empty($info['upload'])){
                    $uploads = explode('|',$info['upload']);
                    $filelist = array();
                    foreach($uploads as $v){
                        $filelist[]['file'] = json_decode($v,true);
                    }
                }else{
                    $filelist = array();
                }
                $this->assign('filelist',$filelist);
                //获取子需求
                $childneed = db('need')->where('pidNeed',$info['id'])->field('id,needName,status,level,dealname')->select();
                $info['childnum'] = count($childneed);
                $this->assign('childneed',$childneed);
                //获取关联缺陷信息
                $linkbug = array();
                $link = '"needID":"'.$id.'"';
                $map['linkID'] = array('LIKE',"%{$link}%");
                $map['projectID'] = $projectID;
                $linkbug = db('bug')->where($map)->field('id,level,bugName,status,type,serious,dealby,creatName,creatTime,linkID')->select();
                $info['bugnum'] = count($linkbug);
                $this->assign('linkbug',$linkbug);
                //获取任务
                $info['tasknum'] = 0;
                //获取测试用例
                $caselist = db('cases')->where('needID','like',"%{$info['id']}%")->field('id,casesName,list,type,status,grade,creatName,creatTime')->select();
                $case = array();
                foreach($caselist as $v){
                    if($v['list']=='-1'){
                        $v['list_name'] = '未规划';
                    }else{
                        $list_name = db('cases_list')->where('id',$v['list'])->field('list_name')->find();
                        if($list_name){
                            $v['list_name'] = $list_name['list_name'];
                        }else{
                            $v['list_name'] = '';
                        }
                    }
                    $case[] = $v;
                }
                if($case){
                    $fanye = array();
                    foreach($case as $v){
                        $fanye[] = $v['id'];
                    }
                    session('casepage',$fanye);
                }
                //$case = db('cases')->alias('a')->join('cases_list b','a.list = b.id')->where('a.needID','like',"%{$info['id']}%")->field('a.id,a.casesName,b.list_name,a.type,a.status,a.grade,a.creatName,a.creatTime')->select();
                $info['casesnum'] = count($case);
                $this->assign('case',$case);
                //获取变更历史
				$history = db('history')->where('changeID',$id)->where('changeTable','need')->order('addTime desc')->select();
				$history = $this->checkHistory($history);
				$sta_history = array();
				foreach($history as $key=>$v){
					$status = array();
					$child = array();
					foreach($v['result'] as $k=>$vi){
						if($vi['column_sign'] == '当前处理人' ||$vi['column_sign'] == '状态'){
							$status[] = $vi;
						}else{
							if($vi['column_sign'] == '需求分类'){
								if($vi['before_sign'] == '-1'){
									$vi['before_sign'] = '未规划';
								}else{
									$vi['before_sign'] = db('need_kind')->where('id',$vi['before_sign'])->where('projectID',$projectID)->find()['list_name'];
								}
								if($vi['after_sign'] == '-1'){
									$vi['after_sign'] = '未规划';
								}else{
									$vi['after_sign'] = db('need_kind')->where('id',$vi['after_sign'])->where('projectID',$projectID)->find()['list_name'];
								}
							}	
							$child[] = $vi;
						}
					}
					if($status){
						$sta = $v;
						$sta['result'] = $status;
						$sta_history[] = $sta;
					}
					if($child){
						$v['result'] = $child;
						$history[$key] = $v;
					}else{
						unset($history[$key]);
					}
				}
				$info['historynum'] = count($history)+count($sta_history);
				$this->assign('history',$history);
				$this->assign('sta_history',$sta_history);
                //获取父需求
                if($info['pidNeed'] !=0 ){
                    $pid = db('need')->where('id',$info['pidNeed'])->field('needName')->find();
                    if($pid){
                        $info['pidNeed'] = $pid['needName'];
                    }else{
                        $info['pidNeed'] = '--';
                    }
                }else{
                    $info['pidNeed'] = '--';
                }
                //上一条，下一条
                $fanye = session('needpage');
                $next = '';
                $prev = '';
                for($i=0;$i<count($fanye);$i++){
                    if($fanye[$i] == $id){
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
                    }else{
                        $next = $id;
                        $prev = $id;
                    }
                }
                //获取分类列表
                $list=self::diguiChild(0,$projectID);
                $this->assign('treelist',json_encode($list));
				 //状态流转
                $model = new NeedModel();
                $status = $model->getStatus($info['status']);
                //获取成员列表
                $names = $this->getNames($projectID);
                $this->assign('names',$names);
                //获取评论
                $this->comment($id);
				$this->assign('status',$status);
                $this->assign('projectID',$projectID);
                $this->assign('next',$next);
                $this->assign('prev',$prev);
                $this->assign('info',$info);
            }
        }
        return $this->fetch('desc');
    }

    /*************导出需求******************/
    public function outload(){
        $data=json_decode($_POST["datas"]);
        $dataname=json_decode($_POST["dataname"]);
        $datacount=count($dataname);
        $needList=db('need')->where('projectID',$_POST['projectID'])->field(implode(',',$data))->select();
        $list = array();
        if($needList){
            foreach($needList as $vo ){
                if(isset($vo['needKind'])){
                    if($vo['needKind'] == '-1'){
                        $vo['needKind'] = '未分类';
                    }else{
                        $listname = db('need_kind')->where('id',$vo['needKind'])->field('list_name')->find();
                        $vo['needKind'] = $listname['list_name'];
                    }
                }
                if(isset($vo['pidNeed'])){
                    $pidName = db('need')->where('id',$vo['pidNeed'])->field('needName')->find();
                    $vo['pidNeed'] = $pidName['needName'];
                }
                if(isset($vo['needDepict'])){
                    $vo['needDepict'] = htmltoexcel($vo['needDepict']);
                }
                /*if(isset($vo['casesID'])){
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
                }*/
                $list[] = $vo;
            }
        }
        vendor("PHPExcel.PHPExcel");
        $PHPExcel = new PHPExcel(); //实例化PHPExcel类，类似于在桌面上新建一个Excel表格
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

        $filename = session('projectName').'--需求导出'.date('Ymdhms',time()).'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');//禁止缓存
        $PHPWriter->save("php://output");
    }

    /****************************导入需求********************/
    public function needUpload(){
        $loadtype = $_POST['loadtype'];//$loadtype=1:批量插入
        $projectID = session('projectID');
        $path=ROOT_PATH . 'public' . DS . 'input'.DS.date("Y-m-d",time()).DS;
        if(!file_exists($path)){mkdir($path,0755,true);}
        if ($_FILES["file"]["size"] < 2000000)
        {
            if ($_FILES["file"]["error"]> 0)
            {
                return json_encode(array('status'=>0,'msg'=>$_FILES["file"]["error"]));
            }
            else
            {
                try{
                    $this->loadexcel($_FILES["file"]["tmp_name"],$projectID);
                    return json_encode(array('status'=>1,'msg'=>'导入成功'));
                } catch (\Exception $e) {
                    return json_encode(array('status'=>0,'msg'=>'文件内容出错，请参考模板'));
                }
            }
        }
        else
        {
            return json_encode(array('status'=>0,'msg'=>'文件超过2M'));
        }

    }
    public function loadexcel($file,$projectID){
        $name=session('loginname.dealname');
        vendor("PHPExcel.PHPExcel");
        $exceldate = new \PHPExcel_Shared_Date();
        $objReader = PHPExcel_IOFactory::createReaderForFile($file);
        $objPHPExcel = $objReader->load($file,$encode='utf-8');
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();           //取得总行数
        $highestColumn = $sheet->getHighestColumn(); //取得总列数
        $column=range('A',$highestColumn);
        $needtitle=array('needName','needDepict','needKind','pidNeed','type','origin','level','value','sendto','needby','iterationID','startTime','endTime','dealname','creatName');
        $everyColumn=array_combine($needtitle,$column);
        $data = array();
        $deal = array();
        for($j=2;$j<=$highestRow;$j++)
        {
            foreach($everyColumn as $key=>$val){
                if($key == 'startTime' || $key == 'endTime'){
                    $info[$key] = $objPHPExcel->getActiveSheet()->getCell($val.$j)->getValue();
                    if($info[$key] != '' && $info[$key]>1){
                        $info[$key] = gmdate("Y-m-d",$exceldate::ExcelToPHP($info[$key]));
                    }
                }else{
                    $info[$key] = $objPHPExcel->getActiveSheet()->getCell($val.$j)->getValue();
                }
            }
                $info['creatTime']=date("Y-m-d H:i:s",time());
                $info['projectID']=$projectID;
                $info['status'] = '新需求';
                if(empty($info['creatName'])){$info['creatName']=$name;}
                if(empty($info['dealname'])){$info['dealname']=$name;}
                if(empty($info['needby'])){$info['needby']=$name;}
                if(empty($info['origin'])){$info['origin'] = '--';}
                if(empty($info['level'])){$info['level'] = '--';}
                if($info['dealname'] != $info['creatName']||$info['sendto'] != ''){
                    $deal[] =$info;
                }else{
                    $data[] = $info;
                }

        }
        if(!empty($deal)){
            foreach($deal as $v){
                $id = db('need')->insertGetId($v);
                if($v['dealname'] != $v['creatName']){
                    $this->addMessage($id,'need',$v['dealname'],$v['status'],0);
                }
                if($v['sendto'] != ''){
                    $sendto = explode('|',$v['sendto']);
                    foreach($sendto as $vi){
                        $da['model'] = 'need';
                        $da['user'] = session('loginname.dealname');
                        $da['time'] = date('Y-m-d H:i:s',time());
                        $da['linkID'] = $id;
                        $da['dealname'] = $vi;
                        $da['projectID'] = $v['projectID'];
                        $da['status'] = $v['status'].'[抄送]';
                        $da['endtime'] = date('Y-m-d H:i:s',time()+432000);
                        $da['type'] = '0';
                        db('message')->insert($da);
                    }
                }

            }
        }
        db('need')->insertAll($data);
    }
	
	
    /************************状态流转******************/
    public function changeStatus(){
        $info = $_POST;
        if(!empty($info['comment'])){
            $comment['comment'] = $info['comment'];
            $comment['model'] = 'need';
            $comment['modelID'] = $info['id'];
            $comment['user'] = session('loginname.id');
            $comment['time'] = date('Y年n月d日 h:i:s',time());
            if($info['oldstatus'] == $info['status']){
                $comment['addwhere'] = '在状态['.$info['status'].']添加';
            }else{
                $comment['addwhere'] = '在状态['.$info['oldstatus'].']流转到['.$info['status'].']添加';
            }
            db('comment')->insert($comment);
        }
        if($info['olddeal'] != $info['dealname']){
			if($info['dealname'] == session('loginname.dealname')){
				$this->addMessage($info['id'],'need',$info['dealname'],$info['status'],'-1');
			}
			$this->addMessage($info['id'],'need',$info['dealname'],$info['status'],'0');
        }else{
			if($info['olddeal'] == session('loginname.dealname')){
				$this->addMessage($info['id'],'need',$info['dealname'],$info['status'],'-1');
			}else{
				$this->addMessage($info['id'],'need',$info['dealname'],$info['status'],'0');
			}
		}
        unset($info['comment']);
        unset($info['oldstatus']);
        unset($info['olddeal']);
		$data = db('need')->where('id',$info['id'])->find();
        db('need')->where('id',$info['id'])->update($info);
		if($data){
			unset($data['endName']); unset($data['endTimes']);
			$this->saveHistory($info['id'],$table='need',$data,$info);
		}
        return array('status'=>'1','msg'=>'');
    }

    //获取评论
    public function comment($id){
        $map['a.modelID'] = $id;
        $map['a.model'] = 'need';
        $commentlist = db('comment')->alias('a')->join('user b','a.user = b.id','left')->where($map)->order('a.id desc')->field('a.*,b.dealname as user,b.header')->limit(5)->select();
        $this->assign('commentlist',$commentlist);
    }

	
	//批量复制
	public function batchCopy(){
		$info = $_POST;
		$needid = explode(',',$info['id']);
		$data = array();
		foreach($needid as $v){
			$need = db('need')->where('id',$v)->find();
			if($need){
				$need['projectID'] = $info['projectID'];
				if($info['status'] != 'old'){
					$need['status'] = $info['status_value'];
				}
				if($info['creatName'] != 'old'){
					$need['creatName'] = session('loginname.dealname');
				}
				$need['creatTime'] = date('Y-m-d H:i:s',time());
				$need['pidNeed'] = 0;
				$need['needKind'] = -1;
				$need['iterationID'] = 0;
				unset($need['id']);
				$data[] = $need;
			}
		}
		 // 启动事务
        Db::startTrans();
        try{
			foreach($data as $v){
				Db::table('tapd_need')->insert($v);
			}
			// 提交事务
            Db::commit();
            return array('status'=>1,'msg'=>'已成功复制');
        } catch (\Exception $e){
            // 回滚事务
            Db::rollback();
            return array('status'=>0,'msg'=>'批量复制失败');
        }
		
	}
	
	//批量编辑
	public function batchEdit(){
		$info = $_POST;
		unset($info['column']);
		$need = explode(',',$info['id']);
		unset($info['id']);
		$info['endName'] = session('loginname.dealname');
		$info['endTimes'] = date("Y-m-d H:i:s",time());
		 // 启动事务
        Db::startTrans();
        try{
			foreach($need as $v){
				$data = db('need')->where('id',$v)->find();
				Db::table('tapd_need')->where('id',$v)->update($info);
				unset($info['endName']);unset($info['endTimes']);unset($data['endName']);unset($data['endTimes']);
				$this->saveHistory($v,$table='need',$data,$info);					
				if(isset($info['dealname']) && $info['dealname'] != session('loginname.dealname')){
					$this->addMessage($v,'need',$info['dealname'],$need['status'],-1);
				}
			}
			// 提交事务
            Db::commit();
            return array('status'=>1,'msg'=>'批量编辑成功');
        } catch (\Exception $e){
            // 回滚事务
            Db::rollback();
            return array('status'=>0,'msg'=>'批量编辑失败');
        }
	}
	
	//申请需求变更
	public function apply(){
		$id = input('get.needid');
		$projectID = session('projectID');
		$info = db('need')->where('id',$id)->find();
		$filelist =array();
        if(!empty($info['upload'])){
            $uploads = explode('|',$info['upload']);
            foreach($uploads as $v){
                $filelist[]['file'] = json_decode($v,true);
            }
        }
        if(!empty($info['pidNeed'])&& $info['pidNeed']!=0){
            $needName = db('need')->where('id',$info['pidNeed'])->field('needName')->find();
            $info['pidName'] = $needName['needName'];
        }else{
            $info['pidName'] = '';
        }
		$item = new NeedModel();
        //获取需求列表
        $needList = $item->getLinkNeed($projectID,$id);
        $this->assign('needList',$needList);
		
		
		$iteration = db('iteration')->where('projectID',$projectID)->where('status','开启')->field('id,iterationName')->select();
		$this->assign('iteration',$iteration);
        //获取需求筛选条件
        $condition = $item->getNeedCondition($projectID);
        $this->assign('data',$condition);

        //获取成员列表
        $names = $this->getNames($projectID);
        $this->assign('names',$names);
        //获取分类列表
        $list=self::diguiChild(0,$projectID);
        $this->assign('treelist',json_encode($list));
		$this->assign('info',$info);
		$this->assign('uploads',$filelist);
		return $this->fetch('apply');
		
	}
	
}