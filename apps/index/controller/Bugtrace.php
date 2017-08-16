<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-6-8
 * Time: 下午5:57
 */

namespace app\index\controller;
use app\component\Admin;
use app\index\model\BugtraceModel;
use PHPExcel_IOFactory;
use PHPExcel;
use think\Paginator;
use think\Db;
class Bugtrace extends Admin{
    public function index(){
        session('model','bug');
        $projectID = session('projectID');
        if(session('whereAll')){
            $k=json_encode(session('whereAll'));
            $map = session('whereAll');
            session('whereAll',null);
            $map['projectID'] = $projectID;
            $query = $map;
        }else{
            $map['projectID'] = $projectID;
            $query['projectID'] = $projectID;
        }
		$bugtrace = db('bug')->where($map)->order('id desc')->paginate(20,false,['query' => $query]);
        //$bugtrace = db('bug')->join('tapd_version','tapd_bug.findversion = tapd_version.id')->where($map)->order('tapd_bug.id desc')->field('tapd_bug.*, tapd_version.title')->paginate(20,false,['query' => $query]);
        $fanye = array();
        foreach($bugtrace as $key=>$v){
            $fanye[] = $v['id'];
			if($v['model'] != ''){
				$modular = db('modular')->where('id',$v['model'])->field('modularName')->find();
				$v['model'] = $modular['modularName'];
			}
			if($v['findversion'] != ''){
				$version = db('version')->where('id',$v['findversion'])->field('title')->find();
				$v['findversion'] = $version['title'];
			}
			$bugtrace[$key] = $v;
        }
        session('fanye',$fanye);
        if(session('view')){
            $this->assign('view',session('view'));
            session('view',null);
        }else{
            $this->assign('view','');
        }
        //获取bug筛选条件
        $model = new BugtraceModel();
        $condition = $model->getBugCondition($projectID);
        $this->assign('data',$condition);
        //获取bug可导出字段
        $base = $model->getBugColumn();
        $this->assign('base',$base['base']);
        $this->assign('endbase',$base['people']);
        $this->assign('bugtrace',$bugtrace);
        $this->assign('projectID',$projectID);
        $this->assign('download','../bugtrace/outload');
        return $this->fetch('index');
    }

    /**************获取多条件搜索*****************/
    public function search(){
        $result = $this->validate($_POST,'Search');
        if(true !== $result){
            return array('status'=>0,'message'=>$result); // 验证失败 输出错误信息
        }
        $whereALL = array();
        foreach($_POST as $key=>$vo){
            if(empty($vo)||$vo == '--'){
                unset($vo);
            }else{
                $whereALL[$key] = $vo;
            }
        }
        if(isset($whereALL['bugName'])){
            $whereALL['bugName'] = array("like","%{$whereALL['bugName']}%");
        }
        if(isset($whereALL['planID']) && isset($whereALL['needID'])){
            $link = '"planID":'.'"'.$whereALL['planID'].'","needID":"'.$whereALL['needID'].'"';
            $whereALL['linkID'] = array("like","%{$link}%");
            unset($whereALL['planID']);unset($whereALL['needID']);
        }elseif(isset($whereALL['needID'])){
            $link = '"needID":'.'"'.$whereALL['needID'].'"';
            $whereALL['linkID'] = array("like","%{$link}%");
            unset($whereALL['needID']);
        }elseif(isset($whereALL['planID'])){
            $link = '"planID":'.'"'.$whereALL['planID'].'"';
            $whereALL['linkID'] = array("like","%{$link}%");
            unset($whereALL['planID']);
        }
        session('whereAll',$whereALL);
        return array('status'=>1,'message'=>'');
    }

   public function create(){
        $bugid = input('get.bugid');
		$map['caseid'] = input('get.caseid');
        input('get.planid') != ''?$map['planid'] = input('get.planid'):$map='';
        input('get.needid') != ''?$map['needid'] = input('get.needid'):$map='';		
        $step = input('get.step');
        $projectID = session('projectID');
        $filelist = array();
        $link =array();
        $linkID = '';
		$version = array();
        if($bugid){
            $info = db('bug')->where('id',$bugid)->find();
            if(!empty($info['upload'])){
                $uploads = explode('|',$info['upload']);
                foreach($uploads as $v){
                    $filelist[]['file'] = json_decode($v,true);
                }
            }
            if(!empty($info['linkID'])){
                $linkID = explode('|',$info['linkID']);
                $links = array();
                foreach($linkID as $key=>$item){
                    $item = json_decode($item,true);
                    $plan = db('testplan')->where('id',$item['planID'])->field('id,planName')->find();
                    $need = db('need')->where('id',$item['needID'])->field('id,needName')->find();
                    $case = db('cases')->where('id',$item['caseID'])->field('id,casesName')->find();
                    $links['plan'] = $plan;
                    $links['need'] = $need;
                    $links['case'] = $case;
                    $link[] = $links;
                }
            }
			$info['version'] = db('version')->where('id',$info['findversion'])->find()['title'];
            $linkID = $info['linkID'];
            $url = "../bugtrace/index";
        }else{
            if($map){
                $plan = db('testplan')->where('id',$map['planid'])->field('id,planName,versionID')->find();
                $need = db('need')->where('id',$map['needid'])->field('id,needName')->find();
                $case = db('cases')->where('id',$map['caseid'])->field('id,casesName,step,result')->find();
                $info['step'] = $case['step'];
                $info['wish'] = $case['result'];
                unset($case['step']); unset($case['result']);
                $links['plan'] = $plan;
                $links['need'] = $need;
                $links['case'] = $case;
                $link[] = $links;
                $linkID['planID'] = "$plan[id]";
                $linkID['needID'] = "$need[id]";
                $linkID['caseID'] = "$case[id]";
                $linkID = json_encode($linkID);
                $url = "../testplan/playtest?planid=".$map['planid'];
				$ver = db('version')->where('id',$plan['versionID'])->field('id, title')->find();
				if($ver){
					$info['findversion'] = $ver['id'];
					$info['version'] = $ver['title'];
				}else{
					$info['findversion'] = '';
					$info['version'] = '';
				}
				$version = db('version')->where('projectID',$projectID)->where('planid',$map['planid'])->field('id,title')->select();
            }else{
				$version = db('version')->where('projectID',$projectID)->field('id,title')->select();
                $url = "../bugtrace/index";
				$info['findversion'] = '';
				$info['version'] = '';
            }
            $info['level'] = '';
            $info['model'] = '';
            $info['serious'] = '';
            $info['type'] = '';
            $info['system'] = '';
            $info['status'] = '';
            $info['sendto'] = '';
            $info['dealby'] = '';
        }
        //获取成员列表
        $names = $this->getNames($projectID);
        $this->assign('names',$names);

        //获取模块
        $modular = db('modular')->where('projectID',$projectID)->field('id,modularName')->select();

        $this->assign('modular',$modular);
        $this->assign('version',$version);
        //获取测试计划条件
        $plan = db('testplan')->where('projectID',$projectID)->field('id,needID,planName')->select();
        foreach($plan as $key=>$it){
            $needid = explode(',',$it['needID']);
            $needlist =array();
            foreach($needid as $vo){
                $need = db('need')->where('id',$vo)->field('id,needName')->find();
                if($need){
                    $case = db('cases')->where('needID','like',"%{$need['id']}%")->field('id,casesName,needID')->select();
                    foreach($case as $k=>$ca){
                        $needID = explode(',',$ca['needID']);
                        $i = 0;
                        foreach($needID as $v){
                            if($v == $need['id']){
                                $i++;
                                break;
                            }
                        }
                        if($i<1){
                            unset($case[$k]);
                        }
                    }
                    $need['case'] = $case;
                    $needlist[] = $need;
                }
            }
			$it['need'] = $needlist;
			 //获取版本
            $version = db('version')->where('planid',$it['id'])->order('id desc')->field('id,title')->select();
            if($version){
                $it['vers'] = $version;
            }
            $plan[$key] = $it;
        }
        $this->assign('plan',json_encode($plan));
        $this->assign('info',$info);
        $this->assign('linkID',$linkID);
        $this->assign('link',$link);
        $this->assign('url',$url);
        $this->assign('sign',$step);
        $this->assign('uploads',$filelist);
        return $this->fetch('create');
    }


    public function addbug(){
        $info = $_POST;
		$needID = '';
        if($info){
            $sign = $info['sign'];
            unset($info['sign']);
            if($sign == 'play_case'){
                $linkID = json_decode($info['linkID'],true);
				$needID = $linkID['needID'];
            }else{
                if(!isset($info['linkID'])){
                    $needID = $info['needID'];
                    $linkID['planID'] = $info['planID'];
                    $linkID['needID'] = $info['needID'];
                    $linkID['caseID'] = $info['caseID'];
                    $info['linkID'] = json_encode($linkID);
                    unset($info['planID']);unset($info['needID']);unset($info['caseID']);
                }else{
					$linkID = json_decode($info['linkID'],true);
					$needID = $linkID['needID'];
				}
            }
            $result = $this->validate($info,'Bugtrace');
            if(true !== $result){
                return array('status'=>0,'message'=>$result); // 验证失败 输出错误信息
            }
            $bugname = $info['bugName'];
            if(empty($info['id'])){
                $info['status'] = '新缺陷';
                $info['creatName'] = session('loginname.dealname');
                $info['creatTime'] = date("Y-m-d H:i:s",time());
                $e = db('bug')->insertGetId($info);
				if($e){
					if($info['dealby'] != $info['creatName']){
                        $this->addMessage($e,'bug',$info['dealby'],$info['status'],'0');
                    }
                    if($info['sendto'] != ''){
                        db('message')->where('model','need')->where('linkID',$info['id'])->where('projectID',session('projectID'))->update(['type'=>'-1']);
                        if($info['sendto'] != ''){
                            $sendto = explode('|',$info['sendto']);
                            foreach($sendto as $v){
                                $da['model'] = 'bug';
                                $da['user'] = session('loginname.dealname');
                                $da['time'] = date('Y-m-d H:i:s',time());
                                $da['linkID'] = $e;
                                $da['dealname'] = $v;
                                $da['projectID'] = session('projectID');
                                $da['status'] = $info['status'].'[抄送]';
                                $da['endtime'] = date('Y-m-d H:i:s',time()+432000);
                                $da['type'] = '0';
                                db('message')->insert($da);
                            }
                        }
                    }
                }
            }else{
                $data = db('bug')->where('id',$info['id'])->find();
                $e = db('bug')->update($info);
                if($e){
                    if($info['dealby'] != $data['creatName'] && $info['dealby'] != $data['dealby']){
                        $this->addMessage($info['id'],'bug',$info['dealby'],$data['status'],'0');
                    }
                    if($data){
                        $this->saveHistory($info['id'],$table='bug',$data,$info);
                    }
                    if($info['sendto'] != $data['sendto']||$info['sendtext'] != $data['sendtext']){
                        db('message')->where('model','bug')->where('linkID',$info['id'])->where('projectID',session('projectID'))->update(['type'=>'-1']);
                        if($info['sendto'] != ''){
                            $sendto = explode('|',$info['sendto']);
                            foreach($sendto as $v){
                                $da['model'] = 'bug';
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
                }
            }
            if($e){
                //创建bug时，改变关联需求的状态
				if($needID){
                    $before = db('need')->where('id',$needID)->field('status,dealname')->find();
                    $after['status'] = '测试中';
                    $after['dealname'] = session('loginname.dealname');
                    $after['endName'] =  session('loginname.dealname');
                    $after['endTimes'] = date("Y-m-d H:i:s",time());
                    db('need')->where('id',$needID)->update($after);
					unset($after['endName']);unset($after['endTimes']);
                    $this->saveHistory($needID,$table='need',$before,$after);
                }
                if($sign == 'play_case'){
                    return array('status'=>1,'message'=>'创建成功','bugid'=>$e,'bugName'=>$bugname,'step'=>'play_case','planid'=>$linkID['planID'],'needid'=>$linkID['needID'],'caseid'=>$linkID['caseID']);
                }else{
                    return array('status'=>1,'message'=>'创建成功');
                }
            }else{
                return array('status'=>0,'message'=>'操作失败');
            }
        }else{
            return array('status'=>0,'message'=>'创建bug信息不能为空');
        }
    }

    public function desc(){
        $bugid = input('get.bugid');
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
		//$info = db('bug')->where('id',$bugid)->find();
		$info = db('bug')->join('tapd_modular','tapd_bug.model = tapd_modular.id','left')->join('tapd_version','tapd_bug.findversion = tapd_version.id','left')->where('tapd_bug.id',$bugid)->field('tapd_bug.*, tapd_modular.modularName, tapd_version.title')->find();
		/*if($info['model'] != ''){
			$modular = db('modular')->where('id',$info['model'])->field('modularName')->find();
			$info['modularName'] = $modular['modularName'];
		}else{
			$info['modularName'] = '';
		}
		if($info['findversion'] != ''){
			$version = db('version')->where('id',$info['findversion'])->field('title')->find();
			$info['title'] = $version['title'];
		}else{
			$info['title'] = '';
		}*/
        $filelist = array();
        $caseList = array();
        $needList = array();
        if(!empty($info['upload'])){
            $uploads = explode('|',$info['upload']);
            foreach($uploads as $v){
                $filelist[]['file'] = json_decode($v,true);
            }
        }
        if($info['linkID']){
            $link = explode('|',$info['linkID']);
            foreach($link as $item){
                $item = json_decode($item,true);
				$plan = db('testplan')->where('id',$item['planID'])->field('id as planid,planName')->find();
				$need = db('need')->where('id',$item['needID'])->field('id,needName,status,creatName,dealname,level')->find();
				$case = db('cases')->where('id',$item['caseID'])->field('id as caseid,casesName')->find();
				if($plan && $need){
                    $needList[] = $need;
                    if($case){
                        $resultid = db('play_case')->where('planID',$plan['planid'])->where('needID',$need['id'])->where('caseID',$case['caseid'])->find();
                        $result = db('play_case_result')->where('play_case_id',$resultid['id'])->order('id desc')->limit(1)->find();
                        $case = array_merge($case,$plan);
                        if($result){
                            $case = array_merge($case,$result);
                        }else{
                            $case['id'] = '';
                            $case['run_people'] = '';
                            $case['runtime'] = '';
                            $case['testresult'] = '未执行';
                        }
                        if($case){
                            $caseList[] = $case;
                        }
                    }
                }
                /*$case = db('cases')->where('tapd_cases.id',$item['caseID'])
                    ->join('tapd_play_case','tapd_cases.id = tapd_play_case.caseID')
                    ->join('tapd_play_case_result','tapd_play_case.id = tapd_play_case_result.play_case_id')
                    ->join('tapd_testplan','tapd_play_case.planID = tapd_testplan.id')->order('tapd_play_case_result.id desc')
                    ->field('tapd_cases.id as caseid,tapd_cases.casesName,tapd_testplan.id as planid,tapd_testplan.planName,tapd_play_case_result.id,tapd_play_case_result.run_people,tapd_play_case_result.runtime,tapd_play_case_result.testresult')->limit(1)->find();
                */
            }
        }
        $history = db('history')->where('changeID',$bugid)->where('changeTable','bug')->order('addTime desc')->select();
        $history = $this->checkHistory($history);
		$sta_history = array();
		foreach($history as $key=>$v){
			$status = array();
			$child = array();
			foreach($v['result'] as $k=>$vi){
				if($vi['column_sign'] == '当前处理人' ||$vi['column_sign'] == '缺陷状态'){
					$status[] = $vi;
				}else{
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
        $info['neednum'] = count($needList);
        $info['casesnum'] = count($caseList);
		$info['historynum'] = count($history)+count($sta_history);
       
        //上一条，下一条
        $fanye = session('fanye');
        $next = '';
        $prev = '';
        for($i=0;$i<count($fanye);$i++){
            if($fanye[$i] == $bugid){
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
		//状态流转
        $model = new BugtraceModel();
        $status = $model->getStatus($info['status']);
        //获取成员列表
		$projectID = session('projectID');
        $names = $this->getNames($projectID);
        $this->assign('names',$names);
        //获取评论
        $this->comment($bugid);
		$this->assign('status',$status);
        $this->assign('filelist',$filelist);
        $this->assign('needList',$needList);
        $this->assign('caseList',$caseList);
        $this->assign('history',$history);
		$this->assign('sta_history',$sta_history);
        $this->assign('info',$info);
        return $this->fetch('desc');
    }

    /*************导出缺陷******************/
    public function outload(){
        $data=json_decode($_POST["datas"]);
        $dataname=json_decode($_POST["dataname"]);
        $datacount=count($dataname);
        $bugList=db('bug')->where('projectID',$_POST['projectID'])->field(implode(',',$data))->select();
        $list = array();
        if($bugList){
            foreach($bugList as $vo ){
                if(isset($vo['projectID'])){
                    $project = db('project')->where('id',$vo['projectID'])->field('projectName')->find();
                    $vo['projectID'] = $project['projectName'];
                }
                if(isset($vo['linkID'])){
                    $linkname = '';
                    if(!empty($vo['linkID'])){
                        $linkID = explode('|',$vo['linkID']);
                        $linkname = '';
                        foreach($linkID as $value){
                            $item = json_decode($value,true);
                            $plan = db('testplan')->where('id',$item['planID'])->field('planName')->find();
                            $need = db('need')->where('id',$item['needID'])->field('needName')->find();
                            $cases = db('cases')->where('id',$item['caseID'])->field('casesName')->find();
                            $linkname == '' ? $linkname = $plan['planName'].'/'.$need['needName'].'/'.$cases['casesName'] : $linkname = $linkname.'；'.$plan['planName'].'/'.$need['needName'].'/'.$cases['casesName'];
                        }
                    }
                    $vo['linkID'] = $linkname;
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
                            $uploadfile = $uploadfile.','.$upfile;
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

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="缺陷导出.xlsx"');
        header('Cache-Control: max-age=0');//禁止缓存
        $PHPWriter->save("php://output");
    }
	
	 /************************状态流转******************/
    public function changeStatus(){
        $info = $_POST;
		if($info['dealby'] == ''){
			return array('status'=>'0','msg'=>'流转给谁不能为空');
		}
        if(!empty($info['comment'])){
            $comment['comment'] = $info['comment'];
            $comment['model'] = 'bug';
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
        if($info['olddeal'] != $info['dealby']){
			if($info['dealby'] == session('loginname.dealname')){
				$this->addMessage($info['id'],'bug',$info['dealby'],$info['status'],'-1',$info['comment']);
			}
			$this->addMessage($info['id'],'bug',$info['dealby'],$info['status'],'0',$info['comment']);
        }else{
			if($info['olddeal'] == session('loginname.dealname')){
				$this->addMessage($info['id'],'bug',$info['dealby'],$info['status'],'-1',$info['comment']);
			}else{
				$this->addMessage($info['id'],'bug',$info['dealby'],$info['status'],'0',$info['comment']);
			}
		}
		unset($info['comment']);
        unset($info['oldstatus']);
        unset($info['olddeal']);
		if($info['status'] == '已解决'){
			$info['restorer'] = $info['dealby'];
		}
		$data = db('bug')->where('id',$info['id'])->find();
        db('bug')->where('id',$info['id'])->update($info);
		if($info){
			/*$info['status'] = $info['dealby'].'</br>'.$info['status'];
			$data['status'] = $data['dealby'].'</br>'.$data['status'];
			$info['dealby'] = '';
			$data['dealby'] = '';*/
			$this->saveHistory($info['id'],$table='bug',$data,$info);
		}
        return array('status'=>'1','msg'=>'');
    }

    //获取评论
    public function comment($id){
        $map['a.modelID'] = $id;
        $map['a.model'] = 'bug';
        $commentlist = db('comment')->alias('a')->join('user b','a.user = b.id','left')->where($map)->order('a.id desc')->field('a.*,b.dealname as user,b.header')->limit(5)->select();
        $this->assign('commentlist',$commentlist);
    }

}