<?php
namespace app\index\controller;
use app\component\Admin;
use think\Db;
class Set extends Admin{
    //设置
    public function index(){
        session('model','set');
        return $this->fetch('index');
    }

    //成员与权限
    public function  userRole(){
        return $this->fetch('userrole');
    }
    //休息与报告
    public function  newsReport(){
        return $this->fetch('newsreport');
    }
    //其他设置
    public function  otherSet(){
        return $this->fetch('other');
    }
    // 项目成员
    public function user(){
        $projectID = session('projectID');
        $user_list = array();
        $common = 0;
        $admini = 0;
        $user = db('project_group')->where('projectId',$projectID)->find();
        if($user['ad']){
            $item = explode(',',$user['ad']);
            foreach($item as $vo){
                $name = db('user')->where('id',$vo)->find();
                if($name){
                    $people = $name;
                    $people['role'] = '管理员';
                    $user_list[] = $people;
                    $admini++;
                }
            }
        }
        if($user['com']){
            $item = explode(',',$user['com']);
            foreach($item as $vo){
                $name = db('user')->where('id',$vo)->find();
                if($name){
                    $people = $name;
                    $people['role'] = '普通成员';
                    $user_list[] = $people;
                    $common++;
                }
            }
        }
        $role['common'] = $common;
        $role['admini'] = $admini;
        $role['num'] = $common + $admini ;
        $this->assign('num',$role);
        $this->assign('userlist',$user_list);
        return $this->fetch('user');
    }

/***************新增用户到项目与创建用户*****
 * @join 新增用户至项目
 * @add  创建用户并增加至项目
 *
 ****/
    public function getuser(){
        $projectID = session('projectID');
        $step = input('get.step');
        $info = $_POST;
        $data = array();
        switch($step){
            case 'join':
                if($info){
                    $people = db('project_group')->where('projectID',$projectID)->find();
                    if($people['com'] != ''){
                        $data['com'] = $people['com'].','.$info['user'];
                    }else{
                        $data['com'] = $info['user'];
                    }
                    $e = db('project_group')->where('id',$people['id'])->update($data);
                    if($e){
                        return array('status'=>1,'msg'=>'新增成员成功');
                    }else{
                        return array('status'=>0,'msg'=>'新增成员失败');
                    }
                }else{
                    $user = db('project_group')->where('projectID',$projectID)->find();
                    $people = array();
                    if($user['com']){
                        $userID = $user['ad'].','.$user['com'];
                    }else{
                        $userID = $user['ad'];
                    }
                    $people = explode(',',$userID);
                    $userlist = db('user')->select();
                    foreach($people as $item){
                        foreach($userlist as $k=>$v){
                            if($item == $v['id']){
                                unset($userlist[$k]);
                            }
                        }
                    }
					$admin = '';
					foreach($userlist as $item){
						if($item['role'] == 'admin'){
							$admin == ''?$admin = $item['dealname']:$admin=$admin.'，'.$item['dealname'];
						}
					}
                    $userlist = $this->checkName($userlist);
                    $this->assign('userlist',$userlist);
					$this->assign('admin',$admin);
                    return $this->fetch('getuser');
                }
                break;
            case 'add':
                if($info){
                    if($info['id'] != ''){
                        $e = db('user')->where('id',$info['id'])->update($info);
                        if($e){
                            return array('status'=>1,'msg'=>'修改成功');
                        }else{
                            return array('status'=>0,'msg'=>'修改失败');
                        }
                    }else{
                        $userid = db('user')->insertGetId($info);
                        if($userid){
                            $people = db('project_group')->where('projectID',$projectID)->find();
                            if($people['com'] != ''){
                                $data['com'] = $people['com'].','.$userid;
                            }else{
                                $data['com'] = $userid;
                            }
                            $e = db('project_group')->where('id',$people['id'])->update($data);
                            if($e){
                                return array('status'=>1,'msg'=>'添加成员成功，并已自动添加到当前项目');
                            }else{
                                return array('status'=>0,'msg'=>'添加成员成功，但自动添加到当前项目失败');
                            }
                        }else{
                            return array('status'=>'0','msg'=>'创建成员失败');
                        }
                    }
                }else{
                    $this->assign('info','');
                    return $this->fetch('adduser');
                }
                break;
            case 'edit':
                $id = input('get.id');
                if($id){
                    $info = db('user')->where('id',$id)->find();
                    $this->assign('info',$info);
                }
                return $this->fetch('adduser');
            default:
                return array('status'=>'0','msg'=>'操作失败');
                break;
        }
    }

    //批量移除成员
    public function deluser(){
        $info = $_POST;
        $projectID = session('projectID');
        $info = explode(',',$info['user']);
        $common = db('project_group')->where('projectID',$projectID)->find();
        $com = explode(',',$common['com']);
        $ad = explode(',',$common['ad']);
        $com = array_diff($com,$info);
        $ad = array_diff($ad,$info);
        $data['com'] = implode(',',$com);
        $data['ad'] = implode(',',$ad);
        if($data['ad'] == ''){
            return array('status'=>0,'msg'=>'项目必须保留一位管理员，若要移除此成员，需先为项目设定另一个管理员');
        }
        $e = db('project_group')->where('id',$common['id'])->update($data);
        if($e){
            return array('status'=>1,'msg'=>'删除成功');
        }else{
            return array('status'=>0,'msg'=>'删除失败');
        }
    }
	
	
    /***********模块新增与删除*****
     * @add   新增模块
     * @del   删除模块
     * @copy   复制模块
     */

    public function modular(){
        $step = input('get.step');
        $projectID = session('projectID');
        $name = session('loginname.dealname');
        switch($step){
            case 'add':
                $da = $_POST;
                if($da['modularName'] != ''){
                    $da['creatName'] = $name;
                    $da['projectID'] = $projectID;
                    $da['addTime'] = date('Y-m-d',time());
                    $e = db('modular')->insert($da);
                    if($e){
                        return array('status'=>1);
                    }else{
                        return array('status'=>0);
                    }
                }else{
                    return array('status'=>0,'msg'=>'模块名称不能为空');
                }
                break;
            case 'del':
                $mod = $_POST;
                $modular = explode(',',$mod['modular']);
                $mos = '';
                foreach($modular as $v){
                    $g = db('bug')->where('model','like',"%{$v}%")->where('projectID',$projectID)->select();
                    if($g){
                        $data = db('modular')->where('id',$v)->field('modularName')->find();
                        $mos == ''?$mos = $data['modularName']:$mos=$mos.'，'.$data['modularName'];
                    }else{
                        db('modular')->where('id',$v)->delete();
                    }
                }
                if($mos){
                    $msg = '你选中的模块'.$mos.'已被关联缺陷，暂时不能删除此模块';
                    return array('status'=>0,'msg'=>$msg);
                }else{
                    $msg = '已成功删除模块';
                    return array('status'=>1,'msg'=>$msg);
                }
                break;
            case 'copy':
                $info = $_POST;
                $id = explode(',',$info['id']);
                Db::startTrans();
                try{
                    foreach($id as $v){
                        $data = db('modular')->where('id',$v)->find();
                        if($info['creatName'] == 'new'){
                            $data['creatName'] = session('loginname.dealname');
                        }
                        $data['addTime'] = date('Y-m-d',time());
                        $data['projectID'] = $info['projectID'];
                        unset($data['id']);
                        Db::table('tapd_modular')->insert($data);
                        Db::commit();
                    }
                    return array('status'=>1,'msg'=>'已成功复制到项目');
                } catch (\Exception $e) {
                    Db::rollback();
                    return array('status'=>0,'msg'=>'批量复制模块失败');
                }
                break;
            default:
				$project = db('project')->where('status','eq','进行中')->field('projectName,id')->select();
				$projectList = array();
				foreach($project as $key=>$v){
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
                    }
                    if($user){
                       $projectList[] = $v;
                    }
                }
                $modular = db('modular')->where('projectID',$projectID)->order('id desc')->select();
                $this->assign('modular',$modular);
                $this->assign('projectlist',json_encode($projectList));
                return $this->fetch('modular');
            break;
        }
    }


    /***********版本新增与删除*****
     * @add   新增版本
     * @del   删除版本
     * @edit   编辑版本
     */
    /***********版本新增与删除*****
     * @add   新增版本
     * @del   删除版本
     * @edit   编辑版本
     */
    public function version(){
        $step = input('get.step');
        $projectID = session('projectID');
        switch($step){
            case 'del':
                $mod = $_POST;
                $version = explode(',',$mod['version']);
                $mos = '';
                foreach($version as $v){
                    $g = db('bug')->where('findversion','like',"%{$v}%")->where('projectID',$projectID)->select();
                    if($g){
                        $data = db('version')->where('id',$v)->field('title')->find();
                        $mos == ''?$mos = $data['title']:$mos=$mos.'，'.$data['title'];
                    }else{
                        db('version')->where('id',$v)->delete();
                    }
                }
                if($mos){
                    $msg = '你选中的版本'.$mos.'已被关联缺陷，暂时不能删除此版本';
                    return array('status'=>0,'msg'=>$msg);
                }else{
                    $msg = '已成功删除版本';
                    return array('status'=>1,'msg'=>$msg);
                }
                break;
            default:
                 $planid = input('get.planid');
                $map['a.projectID'] = $projectID;
                if($planid){
                    $map['a.planid'] = $planid;
                }
                $info = $_POST;
                if($info){
                    if($info['planid'] != '-1'){
                        $map['a.planid'] = $info['planid'];
                    }
                    $planid = $info['planid'];
                    if($info['iterationID'] != '-1'){
                        $map['a.iterationID'] = $info['iterationID'];
                    }
                    $iterationID = $info['iterationID'];
                    $this->assign('iterationID',$iterationID);
                }else{
                    $this->assign('iterationID','-1');
                }
                $version = db('version')->alias('a')->join('testplan b','a.planid = b.id','left')->join('iteration c','a.iterationID = c.id')->where($map)->order('a.id desc')->field('a.*,b.planName,c.iterationName')->select();
                $plan = db('testplan')->where('projectID',$projectID)->field('id,planName as name')->select();
                $iteration = db('iteration')->where('projectID',$projectID)->field('id,iterationName as name')->select();
                $this->assign('plan',$plan);
                $this->assign('iteration',$iteration);
                $this->assign('planid',$planid);
                $this->assign('version',$version);
                return $this->fetch('version');
                break;
        }

    }
	
	  /******
     *
     * 申请测试
     * 添加版本
     */
    //申请测试
   public function applytest(){
        $iterationID= input('get.id');
        $title= input('get.title');
        $step = input('get.step');
        $version = input('get.version');
        $test = input('get.test');
        switch($step){
            case "save":
                $info = $_POST;
                $result = $this->validate($info,'Version');
                if(true !== $result){
                    return array('status'=>0,'message'=>$result);
                }else{
                    $sign = '';
                    if(isset($info['type2'])){
                        $sign = $info['type2'];
                        unset($info['type2']);
                    }
                    $info['creatName'] = session('loginname.dealname');
                    $info['creatTime'] = date('Y-m-d H:s:m',time());
                    $info['projectID'] = session('projectID');
                    $info['status'] = '打开';
                    if($info['id'] != ''){
                        $g = db('version')->update($info);
                        if($g){
                            if($sign){
                                $url = '../set/applytest?title='.$sign.'&id='.$info['iterationID'].'&test=retest';
                                return array('status'=>3,'url'=>$url);
                            }else{
                                return array('status'=>1,'message'=>'修改成功');
                            }
                        }else{
                            return array('status'=>1,'message'=>'修改失败');
                        }
                    }else{
                        $e = db('version')->insertGetId($info);
                        if($e){
                            if($info['testNeed']){
                                $need = explode('|',$info['testNeed']);
                                foreach($need as $v){
                                    $status = db('need')->where('id',$v)->find()['status'];
                                    if($status == '已实现'){
                                        $data['status'] = '测试排期中';
                                        db('need')->where('id',$v)->update($data);
                                    }
                                }
                            }
                            if($info['testBug']){
                                $bug = explode('|',$info['testBug']);
                                foreach($bug as $v){
                                    $data['status'] = '已解决';
                                    db('bug')->where('id',$v)->update($data);
                                }
                            }
                            $user=db('project_group')->where('projectID',session('projectID'))->find();
                            if($user['com']){
                                $users=$user['ad'].','.$user['com'];
                            }else{
                                $users=$user['ad'];
                            }
                            $users = explode(',',$users);
                            foreach ($users as $value){
                                $ss=db('user')->where('id',$value)->where('bm','测试')->find()['dealname'];
                                if($ss){
                                    $this->addMessage($e,'iteration',$ss,'',-1);
                                }
                            }
                            if($sign){
                                $url = '../set/applytest?title='.$sign.'&id='.$info['iterationID'].'&version='.$e.'&test=test';
                                return array('status'=>3,'url'=>$url);
                            }else{
                                return array('status'=>1,'message'=>'申请测试成功');
                            }
                        }else{
                            return array('status'=>0,'message'=>'申请测试失败',);
                        }
                    }
                }
                break;
            default:
                $filelist = array();
                if($test == 'retest'){
                    $version = db('version')->where('type',$title)->where('iterationID',$iterationID)->order('id desc')->limit(1)->find()['id'];
                }
                if($version){
                    $info = db('version')->where('id',$version)->find();
                    $iterationID = $info['iterationID'];
                    if($info['testNeed'] != ''){
                        $testneed = explode('|',$info['testNeed']);
                        foreach($testneed as $key=>$v){
                            $ne = db('need')->where('id',$v)->field('id,needName')->find();
                            if($ne){
                                $testneed[$key] = $ne;
                            }else{
                                unset($testneed[$key]);
                            }
                        }
                        $info['testneed'] = $testneed;
                    }else{
                        $info['testneed'] = '';
                    }
                    if($info['testBug'] != ''){
                        $testbug = explode('|',$info['testBug']);
                        foreach($testbug as $key=>$v){
                            $bug = db('bug')->where('id',$v)->field('id,bugName')->find();
                            if($bug){
                                $testbug[$key] = $bug;
                            }
                        }
                        $info['testbug'] = $testbug;
                    }else{
                        $info['testbug'] = '';
                    }
                    if($step == 'edit' ||$step=='desc' ||$test=='retest'){
                        $title = $info['type'];
                        if(!empty($info['upload'])){
                            $uploads = explode('|',$info['upload']);
                            foreach($uploads as $v){
                                $filelist[]['file'] = json_decode($v,true);
                            }
                        }
                    }else{
                        $info['title'] = '';
                        $info['upload'] = '';
						unset($info['id']);
                    }
                }else{
                    $info= '';
                    $filelist = array();
                }
                $need1= db('need')->where('iterationID',$iterationID)->where('status','eq','测试中')->select();
                $need2= db('need')->where('iterationID',$iterationID)->where('status','eq','已实现')->select();
                $need3= db('need')->where('iterationID',$iterationID)->where('status','eq','测试排期中')->select();
                //$need4= db('need')->where('iterationID',$iterationID)->where('status','eq','测试通过')->select();
                $need = '';
                if($need1){
                    $need = $need1;
                }
                if($need2){
                    if($need){
                        $need = array_merge($need,$need2);
                    }else{
                        $need = $need2;
                    }
                }
                if($need3){
                    if($need){
                        $need = array_merge($need,$need3);
                    }else{
                        $need = $need3;
                    }
                }
                $testneed = '';
                $testbug = '';
                /*if($need){
                    foreach($need as $vv){
                        $bug = db('bug')->where('linkID','like','%"needID":"'.$vv['id'].'"%')->where('projectID',session('projectID'))->where('status','neq','已关闭')->select();
                        foreach($bug as $vi){
                            $vi['needName'] = $vv['needName'];
                            $linkbug[] = $vi;
                            $testbug == ''?$testbug = $vi['id']:$testbug = $testbug.'|'.$vi['id'];
                        }
                        $testneed == ''?$testneed = $vv['id']:$testneed = $testneed.'|'.$vv['id'];
                    }
                }*/
                if($need){
                    foreach($need as $vv){
                        $testneed == ''?$testneed = $vv['id']:$testneed = $testneed.'|'.$vv['id'];
                    }
                }
                $bug = db('bug')->where('iterationID',$iterationID)->where('status','neq','已关闭')->field('id,bugName,status,dealby,linkID')->select();
				if($bug){
					foreach($bug as $key =>$v){
                    $needName = array();
                    if($v['linkID']){
                        $linkID = explode('|',$v['linkID']);
                        foreach($linkID as $vi){
                            $link = json_decode($vi,true);
                            $Need = db('need')->where('id',$link['needID'])->field('id,needName')->find();
                            if($Need){
                                $needName[] = $Need;
                            }
                        }
                    }
                    $v['linkneed'] = $needName;
                    $bug[$key] = $v;
                    $testbug == ''?$testbug = $v['id']:$testbug = $testbug.'|'.$v['id'];
                }
				}else{
					$bug = '';
				}
                $this->assign('testneed',$testneed); //全部待选需求
                $this->assign('testbug',$testbug); //全部待选缺陷
                $this->assign('need',$need); //待选需求
                $this->assign('linkbug',$bug);// 待选缺陷
				$this->assign('ne',json_encode($need));
                $this->assign('lbug',json_encode($bug));
                $this->assign('title',$title);
                $this->assign('iterationID',$iterationID);
                $this->assign('info',$info);
                $this->assign('uploads',$filelist);
                if($step == 'desc'){
                    return $this->fetch('applytestdesc');
                }else{
                    return $this->fetch('applytest');
                }
                break;
        }
    }

    //角色权限
    public function urole(){
        return $this->fetch('urole');
    }
    //权限设置
    public function authority(){
        $role = input('get.role');
        $this->assign('role',$role);
        return  $this->fetch('authority');
    }
    //项目公共参数设置
    public function projectCommon(){
        return  $this->fetch('projectcommon');
    }
    //项目系统设置
    public function system(){
        return $this->fetch('system');
    }

    //基线
    public function baseline(){
        return $this->fetch('baseline');
    }
}