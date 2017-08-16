<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-5-16
 * Time: 上午11:41
 */

namespace app\index\model;
use think\Model;

class CasesModel extends Model
{

    //获取测试用例筛选条件
    public function getCaseCondition($projectID){
        $types=db('cases_type')->where('projectID',1)->field('typename')->select();
        $data = array();
        $type=array();
        foreach ($types as $value){
            $data['id'] = $value['typename'];
            $data['name'] = $value['typename'];
            $type[]=$data;
        }
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
        $status[0]['id'] = '正常';  $status[0]['name'] = '正常';
        $status[1]['id'] = '待更新';  $status[1]['name'] = '待更新';
        $status[2]['id'] = '已废弃';  $status[2]['name'] = '已废弃';
        $grade[0]['id'] = '1';  $grade[0]['name'] = '1';
        $grade[1]['id'] = '2';  $grade[1]['name'] = '2';
        $grade[2]['id'] = '3';  $grade[2]['name'] = '3';
        $grade[3]['id'] = '4';  $grade[3]['name'] = '4';
        $need = db('need')->where('projectID',$projectID)->field('id,needName as name')->select();
        $condition[0]['name']='point';$condition[0]['comment']='功能点';
        $condition[1]['name']='casesName';$condition[1]['comment']='用例名称';
        $condition[2]['name']='status';$condition[2]['comment']='用例状态';$condition[2]['children']=$status;
        $condition[3]['name']='grade';$condition[3]['comment']='用例等级';$condition[3]['children']=$grade;
        $condition[4]['name']='type';$condition[4]['comment']='用例类型';$condition[4]['children']=$type;
        $condition[5]['name']='creatName';$condition[5]['comment']='创建人';$condition[5]['children']=$names;
        $condition[6]['name']='id';$condition[6]['comment']='用例ID';
        $condition[7]['name']='needID';$condition[7]['comment']='关联需求';$condition[7]['children'] = $need;
        return $condition;
    }

    //获取测试用例列表
    public function getCaseList($map,$query){
        $caselist = db('cases')->where($map)->order('id desc')->paginate(20,false,['query' => $query]);
        return $caselist;
    }
}