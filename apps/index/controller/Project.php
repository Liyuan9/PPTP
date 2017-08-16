<?php
namespace app\index\controller;
use app\component\Admin;
use think\Paginator;
use think\Db;
class Project extends Admin
{
    public function index(){
        $projectname = input('post.projectName');
        if($projectname){
            $map['a.projectName'] = array('like',"%{$projectname}%");
        }
        $map['a.id'] = array('neq','0');
        $name = session('loginname');
        $position = $name['position'];
        if(strstr($position,'总监')||strstr($position,'测试')){
            $query = $map;
            $info = db('project')->alias('a')->join('tapd_project_group b','a.id = b.projectID','left')->where($map)->order('a.createTime desc')->field('a.*,b.com,b.ad')->paginate(10,false,['query' => $query]);
        }else{
            $map['b.ad|b.com'] = array('like',"%{$name['id']}%");
            $query = $map;
            $info = db('project')->alias('a')->join('tapd_project_group b','a.id = b.projectID','left')->where($map)->order('a.createTime desc')->field('a.*,b.com,b.ad')->paginate(10,false,['query' => $query]);
        }
        foreach($info as $key=>$vi){
            $user_list = array();
            $product = array();
            $develop = array();
            $test = array();
            $opreation = array();
            $ad = explode(',',$vi['ad']);
            foreach($ad as $v){
                $name = db('user')->where('id',$v)->field('dealname,bm,position')->find();
                $people['role'] = 'administrator';
                $people['username'] = $name['dealname'];
                $people['position'] = $name['position'];
                switch ($name['bm']){
                    case '产品':
                        $product[] = $people;
                        break;
                    case '研发':
                        $develop[] = $people;
                        break;
                    case '测试':
                        $test[] = $people;
                        break;
                    case '运维':
                        $opreation[] = $people;
                        break;
                    default:break;
                }
            }
            $com = explode(',',$vi['com']);
            foreach($com as $v){
                $name = db('user')->where('id',$v)->field('dealname,bm,position')->find();
                $people['role'] = 'common';
                $people['username'] = $name['dealname'];
                $people['position'] = $name['position'];
                switch($name['bm']){
                    case '产品':
                        $product[] = $people;
                        break;
                    case '研发':
                        $develop[] = $people;
                        break;
                    case '测试':
                        $test[] = $people;
                        break;
                    case '运维':
                        $opreation[] = $people;
                        break;
                    default:break;
                }
            }
            $user_list['product'] = $product;
            $user_list['test'] = $test;
            $user_list['develop'] = $develop;
            $user_list['opreation'] = $opreation;
            $vi['user'] = $user_list;
            $info[$key] = $vi;
        }
        $this->assign('num',count($info));
        $this->assign('info',$info);
        session('projectID',null);
        session('projectName',null);
        session('model',null);
        return $this->fetch('index');
    }


    public function createproject(){
        $user = db('user')->select();
        $admin = '';
        foreach($user as $item){
            if($item['role'] == 'admin'){
                $admin == ''?$admin = $item['dealname']:$admin=$admin.'，'.$item['dealname'];
            }
        }
        $user = $this->checkName($user);
        $this->assign('user',$user);
        $this->assign('admin',$admin);
        //return $this->fetch('createproject');
		return $this->fetch('create');
    }

    public function addproject(){
        $info = array();
        $name = session('loginname');
        $data = $_POST;
        if($data){
            if(isset($data['id']) && $data['id'] != ''){
                $e = db('project')->where('id',$data['id'])->update($data);
                if($e){
                    return array('status'=>1,'message'=>'更新成功', 'url'=>'');
                }
            }else{
                if(!empty($data['groupId'])){
                    $info['com'] = $data['groupId'];
                }
                unset($data['groupId']);
                $data['company']='健客网';
                $data['status']='进行中';
                $data['creatname']=$name['dealname'];
                $data['createTime']=time();
                $result = $this->validate($data,'Project');
                if(true !== $result){
                    return array('status'=>0,'message'=>$result); // 验证失败 输出错误信息
                }
                else{
                    // 启动事务
                    Db::startTrans();
                    try{
                        $project=Db::table('tapd_project')->insertGetId($data);//新增目录
                        $info['ad']=$name['id'];
                        if(isset($info['com'])){
                            $user = explode(',',$info['com']);
                            $info['com'] = '';
                            foreach($user as $v){
                                if($v != $name['id'] && $v != ''){
                                    $info['com'] == ''?$info['com'] = $v:$info['com'] = $info['com'].','.$v;
                                }
                            }
                            $info['projectID'] = $project;
                        }
						Db::table('tapd_project_group')->insert($info);//添加成员
                        // 提交事务
                        Db::commit();
                        return array('status'=>1,'message'=>'成功','projectID'=>$project,'url'=>'../project/index');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                       // return array('status'=>0,'message'=>'失败');
                    }
                }
            }
        }
    }

    //成员加入
    public function addmate($info){
        if($info){
            $data['user']=$info['user'];
            $data['projectID']=$info['projectID'];
        }else{
            $dataID=$_POST['UserID'];
            $data['user']=implode(',',$dataID);
            $data['projectID']=$_POST['projectID'];
        }
        $data['type']='系统';
        $data['groupName']='普通成员';
        $data['role']='common';
        $result = $this->validate($data,'ProjectGroup');
        if(true !== $result){
            return array('status'=>0,'message'=>$result); // 验证失败 输出错误信息
        }
        else{
            try{
                $e=db('project_group')->insert($data);
                return array('status'=>1,'message'=>'成功');
            } catch (\Exception $e) {
                return array('status'=>0,'message'=>'失败');
            }
        }
    }

    public function edit(){
        $projectID = session('projectID');
        if($projectID>0){
            $info = db('project')->where('id',$projectID)->find();
        }
        $this->assign('info',$info);
        return $this->fetch('edit');
    }
}