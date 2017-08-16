<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-7-10
 * Time: 上午10:44
 */
namespace app\index\model;
use think\Model;
use app\index\model\NeedModel;
use think\Db;
class IterationModel extends Model{

    //获取迭代列表
    public function getIteration($map,$query){
        $list = db('iteration')->where($map)->paginate(20,false,['query' => $query]);
        foreach($list as $key=>$v){
            $need= db('need')->where('iterationID',$v['id'])->select();
            $success = db('need')->where('iterationID',$v['id'])->where('status','测试通过')->count();
            $v['neednum'] = count($need);
            $v['overneed'] = $success;
            $v['bugnum'] = 0;
            $v['closebug'] = 0;
            $v['bugnum'] = db('bug')->where('iterationID',$v['id'])->count();
            $v['closebug'] = db('bug')->where('iterationID',$v['id'])->where('status','已关闭')->count();
           /* foreach($need as $vv){
                $bug = db('bug')->where('linkID','like','%"needID":"'.$vv['id'].'"%')->where('projectID',session('projectID'))->count();
                $close = db('bug')->where('linkID','like','%"needID":"'.$vv['id'].'"%')->where('projectID',session('projectID'))->where('status','已关闭')->count();
                $v['bugnum']+=$bug;
                $v['closebug'] += $close;
            }*/
            $list[$key] = $v;
        }
        return $list;
    }
}