<?php

namespace app\index\controller;
use app\component\Admin;
use app\index\model\CasesModel;
use app\index\model\NeedModel;
use PHPExcel_IOFactory;
use PHPExcel;
use think\Db;
class Cases extends Admin
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
			$caselist[$i]['url'] = url('Cases/index').'?id='.$caselist[$i]['projectID'].'&listid='.$caselist[$i]['id'];
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
/**********************渲染测试用例主页********************/
    public function index()
    {
        $listID=input('get.listid');
        $projectID = session('projectID');
		$this->assign('projectID',$projectID);
		$list=self::diguiChild(0,$projectID);
		$this->assign('treelist',json_encode($list));
		$unList = db('cases')->where('projectID',$projectID)->where('list','-1')->count();
		$this->assign('unList',$unList);
		$allList = db('cases')->where('projectID',$projectID)->count();
		$this->assign('allList',$allList);
        //获取测试用例可导出字段
        $base=Db::query("SELECT column_name,column_comment FROM information_schema.columns WHERE table_name = 'tapd_cases' and table_schema='JiankeTapd'");
        unset($base[11]); unset($base[12]);
        $endbase[]=array_pop($base); $endbase[]=array_pop($base); $endbase[]=array_pop($base); $endbase[]=array_pop($base);
        $this->assign('base',$base);
        $this->assign('endbase',$endbase);

        $m = new CasesModel();
        //获取筛选条件字段
        $condition = $m->getCaseCondition($projectID);
		$this->assign('data',$condition);
        /*//设置列表基本显示字段//
        $listname[0]['name'] = 'id';$listname[0]['comment'] ='用例ID';
        $listname[0]['name'] = 'casesName';$listname[0]['comment'] ='用例名称';
        $listname[0]['name'] = 'point';$listname[0]['comment'] ='功能点';
        $listname[0]['name'] = 'type';$listname[0]['comment'] ='用例类型';
        $listname[0]['name'] = 'status';$listname[0]['comment'] ='用例状态';
        $listname[0]['name'] = 'garde';$listname[0]['comment'] ='用例等级';
        $listname[0]['name'] = 'creatName';$listname[0]['comment'] ='创建人';
        $listname[0]['name'] = 'creatTime';$listname[0]['comment'] ='创建时间';
        $this->assign('listname',$listname);*/
        //获取列表
        if(session('whereAll')){
            $k=json_encode(session('whereAll'));
            $map = session('whereAll');
            session('whereAll',null);
            $map['projectID'] = $projectID;
            $query = $map;
            $list = $m->getCaseList($map,$query);
        }else{
            $map['projectID'] = $projectID;
            $query['id'] = $projectID;
            if($listID){
                $map['list'] = $listID;
                $query['list'] = $listID;
                if($listID != '-1'){
                    $listALL=self::listID($listID,$projectID);
                    $list = db('cases')->where('projectID',$projectID)->where('list','in',$listALL)->order('id', 'desc')->paginate(20,false,['query' => array('id'=>$projectID,'listid'=>$listID)]);
                }else{
                    $list = $m->getCaseList($map,$query);
                }
            }else{
                $list = $m->getCaseList($map,$query);
            }
        }
        $fanye = array();
        foreach($list as $v){
            $fanye[] = $v['id'];
        }
        session('casepage',$fanye);
        session('model','cases');
        $this->assign('list', $list);
        $this->assign('download','../cases/outload');
        return $this->fetch();
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
        if(isset($whereALL['casesName'])){
            $whereALL['casesName'] = array("like","%{$whereALL['casesName']}%");
        }
        if(isset($whereALL['point'])){
            $whereALL['point'] = array("like","%{$whereALL['point']}%");
        }
        if(isset($whereALL['needID'])){
            $whereALL['needID'] = array("like","%{$whereALL['needID']}%");
        }
        session('whereAll',$whereALL);
        return array('status'=>1,'message'=>'');
	}

/****************测试用例详情页********************/
    public function desc(){
		$casesID=input('get.caseid');
		$cases=db('cases')->where('id',$casesID)->find();
		$projectID=$cases['projectID'];
        $listname = db('cases_list')->where('id',$cases['list'])->field('list_name')->find();
        if($listname){
            $cases['list']= $listname['list_name'];
        }else{
            $cases['list'] = '未分类';
        }
        if(!empty($cases['upload'])){
            $uploads = explode('|',$cases['upload']);
            $filelist = array();
            foreach($uploads as $v){
                $filelist[]['file'] = json_decode($v,true);
            }
        }else{
            $filelist = array();
        }
        $this->assign('filelist',$filelist);
        //获取关联需求信息
        $linkneed = array();
        if(!empty($cases['needID'])){
            $needID = explode(',',$cases['needID']);
            foreach($needID as $vo){
                $need = db('need')->where('id',$vo)->field('id,needName,status,level,dealname')->find();
                $linkneed[] = $need;
            }
        }
        $cases['neednum'] = count($linkneed);
        $this->assign('linkneed',$linkneed);
        //获取关联缺陷信息
        $linkbug = array();
        $link = '"caseID":"'.$casesID.'"';
        $map['linkID'] = array('LIKE',"%{$link}%");
        $map['projectID'] = $projectID;
        $linkbug = db('bug')->where($map)->field('id,level,bugName,status,serious,dealby,creatName,creatTime,linkID')->select();
        $cases['bugnum'] = count($linkbug);
        foreach($linkbug as $key=>$item){
            $linkID = explode('|',$item['linkID']);
            foreach($linkID as $v){
                $v = json_decode($v,true);
                if($v['caseID'] == $casesID){
                    $plan = db('testplan')->where('id',$v['planID'])->field('id,planName')->find();
                    if($plan){
                        $item['planid'] = $plan['id'];
                        $item['planName'] = $plan['planName'];
                    }
                }
            }
            $linkbug[$key] = $item;
        }
        $this->assign('linkbug',$linkbug);
		//获取历史信息
		$history = db('history')->where('changeID',$casesID)->order('id desc')->where('changeTable','cases')->select();
        $history = $this->checkHistory($history);
		foreach($history as $key=>$vi){
			foreach($vi['result'] as $k=>$v){
				if($v['column_sign'] == '用例目录'){
					if($v['before_sign'] == '-1'){
						$v['before_sign'] = '未规划';
					}else{
						$v['before_sign'] = db('cases_list')->where('id',$v['before_sign'])->where('projectID',$projectID)->find()['list_name'];
					}
					if($v['after_sign'] == '-1'){
						$v['after_sign'] = '未规划';
					}else{
						$v['after_sign'] = db('cases_list')->where('id',$v['after_sign'])->where('projectID',$projectID)->find()['list_name'];
					}
				}
				$vi['result'][$k] = $v;
			}
			$history[$key] = $vi;
		}
		$cases['historynum'] = count($history);
		$this->assign('history',$history);
        //上一条，下一条
        $fanye = session('casepage');
        $next = '';
        $prev = '';
        for($i=0;$i<count($fanye);$i++){
            if($fanye[$i] == $casesID){
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
		 //获取评论
        $this->comment($casesID);
		$this->assign('cases',$cases);
		$this->assign('projectID',$projectID);
        return $this->fetch();
	}
/************************获取评论****************/
    //获取评论
    public function comment($id){
        $map['a.modelID'] = $id;
        $map['a.model'] = 'cases';
        $commentlist = db('comment')->alias('a')->join('user b','a.user = b.id','left')->where($map)->order('a.id desc')->field('a.*,b.dealname as user,b.header')->limit(5)->select();
        $this->assign('commentlist',$commentlist);
    }

/************************渲染测试用例创建、编辑页面****************/
    public function createCase(){
		$projectID=session('projectID');
		$this->assign('projectID',$projectID);

		$list=self::diguiChild(0,$projectID);
		$this->assign('treelist',json_encode($list));
		
		$listID=input('get.listid');
		
		$casesID=input('get.casesid');
		$this->assign('casesID',$casesID);
		if($casesID){
			$cases=db('cases')->where('id',$casesID)->find();
            if(!empty($cases['needID'])){
                $needID = explode(',',$cases['needID']);
                $linkneed = array();
                foreach($needID as $vo){
                    $need = db('need')->where('id',$vo)->field('id,needName')->find();
                    $linkneed[] = $need;
                }
            }else{
                $linkneed = array();
            }
            if(!empty($cases['upload'])){
                $uploads = explode('|',$cases['upload']);
                $filelist = array();
                foreach($uploads as $v){
                    $filelist[]['file'] = json_decode($v,true);
                }
            }else{
                $filelist =array();
            }
        }else{
            $linkneed = array();
            $filelist =array();
            if($listID){
                $cases['list']= $listID;
            }else{
                $cases['list']= '-1';
            }
            $cases['status']= '--';
            $cases['grade']= '3';
            $cases['type']= '--';
            $cases['id'] = '';
        }
        $this->assign('linkneed',$linkneed);
        $this->assign('uploads',$filelist);
		$types=db('cases_type')->where('projectID',$projectID)->select();
		$this->assign('types',$types);
		$this->assign('info',$cases);
        //获取需求
        $need = new NeedModel();
        $needlist = $need->getLinkNeed($projectID);
        $this->assign('needlist',$needlist);

        //获取需求筛选条件
        $condition = $need->getNeedCondition($projectID);
        $this->assign('data',$condition);
		return $this->fetch('create');
	}

    /***********************新增、修改测试用例**************/
	public function addcase(){
		$name=session('loginname');
		if(!strstr($name['position'],'测试') && $name['role'] != 'admin'){
			return array('status'=>0,'message'=>'你不是测试人员，不能对测试用例执行操作'); // 验证失败 输出错误信息
		}
		$data=$_POST;
        if(!isset($data['projectID'])){
            $data['projectID'] = session('projectID');
            $data['id'] = '';
        }
        if(!isset($data['id'])){
            $data['id'] = '';
        }
		$result = $this->validate($data,'Cases');
		if(true !== $result){
			return array('status'=>0,'message'=>$result); // 验证失败 输出错误信息
		}
		else{
			if($data['id']){
				$info = db('cases')->where('id',$data['id'])->find();
                $data['endName']=$name['dealname'];
                $data['endTime']=date("Y-m-d H:i:s",time());
                $e = db('cases')->update($data);
                if($e){
					if($data){
						unset($info['endName']);unset($info['endTime']);unset($data['endName']);unset($data['endTime']);
						unset($info['needID']);unset($data['needID']);
                        $this->saveHistory($data['id'],$table='cases',$info,$data);
                    }
                    return array('status'=>1,'message'=>'编辑成功','id'=>$data['id']);
                }else{
                    return array('status'=>0,'message'=>'编辑失败');
                }
			}
			else{
                unset($data['caseid']);
                $data['creatTime']=date("Y-m-d H:i:s",time());
                $data['creatName']=$name['dealname'];
                $g=db('cases')->insertGetId($data);
                if($g){
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
				Db::table('tapd_cases_list')->delete($data['list_id']);
				
				Db::table('tapd_cases')->where('list',$data['list_id'])->update(['list'=>'-1']);

				// 提交事务
				Db::commit(); 
				return array('status'=>1,'message'=>'删除成功');	
			} catch (\Exception $e) {
				// 回滚事务
				Db::rollback();
				return array('status'=>0,'message'=>'删除成功');
			}
				db('cases_list')->delete($data['list_id']);
				return array('status'=>1,'message'=>'删除成功');
			}elseif($data['sign']=="add"){
				$level=db('cases_list')->where('id',$data['list_id'])->field('list_level')->find();
				$addlist['list_level']=$level['list_level']+1;
				$addlist['projectID']= session('projectID');
				$addlist['list_pid']=$data['list_id'];
				$addlist['list_name']=$data['list_name'];
				$addlist['depict']=$data['desc'];
				db('cases_list')->insert($addlist);
				return array('status'=>1,'message'=>'添加成功');
			}elseif($data['sign']=="edit"){
				$edit['id']=$data['list_id'];
				$edit['list_name']=$data['list_name'];
				$edit['depict']=$data['desc'];
				db('cases_list')->update($edit);
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
		$keys=db('cases_list')->where('projectID',$projectID)->where('list_pid',$listID)->order('list_level asc')->select();
		$size = count($keys);
		if ($size){
			$i=0;
			for ($i;$i < $size;$i++){
				self::listID($keys[$i]['id'],$projectID);
			}
		}
		return $result;
	}


/************导入页面渲染**************/
	public function import(){
		$projectID=session('projectID');
		$this->assign('projectID',$projectID);
		$list=self::diguiChild(0,$projectID);
		$this->assign('treelist',json_encode($list));
		return $this->fetch('import');
	}
	
	public function childlist(){
			return $this->fetch('import');
	}

/**************导入测试用例****************/
	public function casesUpload()
    {  		
		$uploadType=$_POST['loadtype'];
		$projectID=$_POST['projectID'];
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
						self::loadexcel($_FILES["file"]["tmp_name"],$projectID);
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
		$name=session('loginname');
		vendor("PHPExcel.PHPExcel");
        $objReader = PHPExcel_IOFactory::createReaderForFile($file);
        $objPHPExcel = $objReader->load($file,$encode='utf-8');
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();           //取得总行数
        $highestColumn = $sheet->getHighestColumn(); //取得总列数
		$column=range('A',$highestColumn);
		$casestitle=array('list','casesName','needID','precondition','step','result','type','status','grade','creatName','point');
		$everyColumn=array_combine($casestitle,$column);
        $data = array();
		
		for($j=2;$j<=$highestRow;$j++)                      
            {
				foreach($everyColumn as $key=>$val)
				$data[$j][$key] = $objPHPExcel->getActiveSheet()->getCell($val.$j)->getValue();
				$data[$j]['creatTime']=date("Y-m-d H:i:s",time());
				$data[$j]['projectID']=$projectID;
				if(empty($data[$j]['creatName'])){$data[$j]['creatName']=$name['dealname'];}
            }
		db('cases')->insertAll($data);
		
    }


/*************导出测试用例******************/
	public function outload(){
		$data=json_decode($_POST["datas"]);
		$dataname=json_decode($_POST["dataname"]);
		$datacount=count($dataname);
		$cases=db('cases')->where('projectID',$_POST['projectID'])->field(implode(',',$data))->select();
        $caselist = array();
		if($cases){
            foreach($cases as $vo ){
                if(isset($vo['list'])){
                    if($vo['list'] == '-1'){
                        $vo['list'] = '未分类';
                    }else{
                        $listname = db('cases_list')->where('id',$vo['list'])->field('list_name')->find();
                        $vo['list'] = $listname['list_name'];
                    }
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
                if(isset($vo['precondition'])){
                    $vo['precondition'] = htmltoexcel($vo['precondition']);
                }
                if(isset($vo['step'])){
                    $vo['step'] = htmltoexcel($vo['step']);
                }
                if(isset($vo['result'])){
                    $vo['result'] = htmltoexcel($vo['result']);
                }
                $caselist[] = $vo;
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
		foreach($caselist as $key=>$value){
			$k=$key+2;
			for($j=0;$j<$datacount;$j++){
				$PHPSheet->setCellValue($cellName[$j].$k,$value[$data[$j]]);
			}
		}
		$PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel2007');//按照指定格式生成Excel文件，‘Excel2007’表示生成2007版本的xlsx，‘Excel5’表示生成2003版本Excel文件
		$filename = session('projectName').'--测试用例导出'.date('Ymdhms',time()).'.xlsx';
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
		header('Cache-Control: max-age=0');//禁止缓存
        $PHPWriter->save("php://output");
	}




}       
                                                                           
