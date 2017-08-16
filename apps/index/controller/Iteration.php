<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-7-10
 * Time: 上午9:07
 */
namespace app\index\controller;
use app\component\Admin;
use app\index\model\IterationModel;
use app\index\model\TestplanModel;
use app\index\model\NeedModel;
use PHPExcel_IOFactory;
use PHPExcel;
use think\Db;

class Iteration extends Admin{
     public function  index(){
         $map['projectID'] = session('projectID');
         $query['projectID'] = session('projectID');
         $model = new IterationModel();
         $info = $model->getIteration($map,$query);

         $fanye = array();
         foreach($info as $key=>$v){
             $fanye[] = $v['id'];
             $version = db('version')->where('iterationID',$v['id'])->order('id desc')->limit(1)->find();
             if($version){
                 $v['version'] = $version['id'];
             }else{
                 $v['version'] = '';
             }
             $info[$key] = $v;
         }
         session('casepage',$fanye);
		
         $this->assign('info',$info);
		 $this->assign('itenum',count($info));
         session('model','iteration');
         return $this->fetch();
     }

    public function getneed($id=''){
        $info = $_POST;
        if($info){
            $id = $info['needid'];
            $map['iterationID'] = $id;
            $map['projectID'] = session('projectID');
            $query = '';
            $model = new needModel();
            $needlist= $model->getNeedList($map,$query,$pa=0);
            $bug = db('bug')->where($map)->select();
            if($needlist == '' && $bug == ''){
                return array('status'=>0,'msg'=>'此迭代还没有规划需求与缺陷');
            }else{
                return array('status'=>1,'need'=>json_encode($needlist),'bug'=>json_encode($bug));
            }
        }
    }


    //创建、编辑迭代
    public function create(){
        $id = input('get.id');
        if($id){
            $info = db('iteration')->where('id',$id)->find();
        }else{
            $info = '';
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    //保存迭代
    public function save(){
        $info = $_POST;
        if($info){
            if($info['iterationName'] != ''){
                $iteration = db('iteration')->where('iterationName',$info['iterationName'])->find();
                if($iteration['id'] != $info['id']){
                    return array('status'=>0,'message'=>'迭代名称已存在,请换个名称！');
                }
            }else{
                return array('status'=>0,'message'=>'请输入迭代名称！');
            }
            if($info['startTime'] == ''){
                return array('status'=>0,'message'=>'请输入开始时间！');
            }
            if($info['endTime'] == ''){
                return array('status'=>0,'message'=>'请输入结束时间！');
            }
            $info['status'] = '开启';
            if($info['id'] != ''){
                $e = db('iteration')->where('id',$info['id'])->update($info);
            }else{
                $info['createName'] = session('loginname.dealname');
                $info['createTime'] = date('Y-m-d H:i:s',time());
                $info['projectID'] = session('projectID');
                $e = db('iteration')->insert($info);
            }
            if($e){
                return array('status'=>1,'message'=>'创建成功');
            }else{
                return array('status'=>0,'message'=>'创建失败');
            }
        }else{
            return array('status'=>0,'message'=>'请先填写迭代信息，再创建！');
        }
    }

    //规划需求&需求详情页
    public function plan(){
        $step = input('get.step');
        $id = input('get.id');
        if($step == 'play'){
            $info = $_POST;
            if(isset($info['needid'])){
                $need = explode('|',$info['needid']);
                $data['iterationID'] = $info['id'];
                Db::startTrans();
                try{
                    foreach($need as $v){
                        Db('need')->where('id',$v)->update($data);
                        Db::commit();
                    }
                    return array('status'=>1,'msg'=>'成功');
                } catch (\Exception $e) {
                    Db::rollback();
                    return array('status'=>0,'msg'=>'失败');
                }
            }else if(isset($info['bugid'])){
                $bug = explode('|',$info['bugid']);
                $data['iterationID'] = $info['id'];
                Db::startTrans();
                try{
                    foreach($bug as $v){
                        Db('bug')->where('id',$v)->update($data);
                        Db::commit();
                    }
                    return array('status'=>1,'msg'=>'成功');
                } catch (\Exception $e) {
                    Db::rollback();
                    return array('status'=>0,'msg'=>'失败');
                }
            }

        }else{
            $iteration = db('iteration')->where('id',$id)->where('projectID',session('projectID'))->find();
            $need= db('need')->where('iterationID',$id)->select();
            $success = db('need')->where('iterationID',$id)->where('status','测试通过')->count();
            $bug = db('bug')->where('iterationID',$id)->select();
            $closebug = db('bug')->where('iterationID',$id)->where('status','已关闭')->count();
            $iteration['neednum'] = count($need);
            $iteration['overneed'] = $success;
            $iteration['bugnum'] = count($bug);
            $iteration['closebug'] = $closebug;
            foreach($bug as $key =>$v){
                $needName = array();
                if($v['linkID']){
                    $linkID = explode('|',$v['linkID']);
                    foreach($linkID as $vi){
                        $link = json_decode($vi,true);
                        $need = db('need')->where('id',$link['needID'])->field('id,needName')->find();
                        if($need){
                            $needName[] = $need;
                        }
                    }
                }
                $v['linkneed'] = $needName;
                $bug[$key] = $v;
            }
            /*$linkbug = array();
            foreach($need as $vv){
                $bug = db('bug')->where('linkID','like','%"needID":"'.$vv['id'].'"%')->where('projectID',session('projectID'))->select();
                $close = db('bug')->where('linkID','like','%"needID":"'.$vv['id'].'"%')->where('projectID',session('projectID'))->where('status','已关闭')->count();
                $iteration['bugnum'] += count($bug);
                $iteration['closebug'] += $close;
                foreach($bug as $k=>$vi){
                    $vi['needName'] = $vv['needName'];
                    $linkbug[] = $vi;
                }
            }*/
            $map['projectID'] = session('projectID');
            $map['iterationID'] = $id;
            $model = new needModel();
            $bindneed = $model->getNeedList($map,'',$pa=0);//已绑定需求
            if($step == 'desc'){
                if($iteration['neednum'] != 0){
                    $iteration['needrate'] = round($iteration['overneed']/$iteration['neednum'],3)*100 .'%';
                }else{
                    $iteration['needrate'] = '';
                }
                if($iteration['bugnum'] != 0){
                    $iteration['bugrate'] = round($iteration['closebug']/$iteration['bugnum'],3)*100 .'%';
                }else{
                    $iteration['bugrate'] = '';
                }
                $names = $this->getNames(session('projectID'));
                $this->assign('names',json_encode($names));
                $this->assign('bindneed',json_encode($bindneed));
                $this->assign('linkbug',$bug);
                //上一条，下一条
                $fanye = session('casepage');
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
                    }
                }
                $this->assign('next',$next);
                $this->assign('prev',$prev);
                $this->assign('info',$iteration);
                return $this->fetch('desc');
            }else{
                if(session('whereAll')){
                    $mp = session('whereAll');
                    session('whereAll',null);
                }
                $mp['projectID'] = session('projectID');
                $mp['iterationID'] = array('eq',0);
                if($step == 'need'){
                    $unbindneed = $model->getNeedList($mp,'',$pa=0);//未绑定需求
                    $this->assign('bindneed',json_encode($bindneed));
                    $this->assign('bindbug',json_encode($bug));
                    $this->assign('unbindneed',json_encode($unbindneed));
                    $this->assign('info',$iteration);
                    return $this->fetch('plan');
                }else if($step == 'bug'){
                    $unbindbug = db('bug')->where($mp)->select();//未绑定缺陷
                    $this->assign('bindneed',json_encode($bindneed));
                    $this->assign('bindbug',json_encode($bug));
                    $this->assign('unbindbug',$unbindbug);
                    $this->assign('info',$iteration);
                    return $this->fetch('planbug');
                }

            }
        }
    }

    //需求、缺陷移出迭代
    public function remove(){
        $info = $_POST;
        $table = $info['table'];
        $data['iterationID'] = 0;
        $e = db($table)->where('id',$info['id'])->update($data);
        if($e){
            return array('status'=>1,'msg'=>'移出成功');
        }else{
            return array('status'=>0,'msg'=>'移出迭代失败');
        }
    }





}