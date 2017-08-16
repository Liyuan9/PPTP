<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-6-9
 * Time: 上午10:14
 */
namespace app\index\model;
use think\Model;
use think\Db;
class BugtraceModel extends Model{

    public function getBugCondition($projectID){
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
        $type[0]['id'] = '--';  $type[0]['name'] = '--';
        $type[1]['id'] = '代码错误';  $type[1]['name'] = '代码错误';
        $type[2]['id'] = '界面优化';  $type[2]['name'] = '界面优化';
        $type[3]['id'] = '设计缺陷';  $type[3]['name'] = '设计缺陷';
        $type[4]['id'] = '配置相关';  $type[4]['name'] = '配置相关';
        $type[5]['id'] = '安装部署';  $type[5]['name'] = '安装部署';
        $type[6]['id'] = '安全相关';  $type[6]['name'] = '安全相关';
        $type[7]['id'] = '性能问题';  $type[7]['name'] = '性能问题';
        $type[8]['id'] = '标准规范';  $type[8]['name'] = '标准规范';
        $type[9]['id'] = '接口错误';  $type[9]['name'] = '接口错误';


        $status[0]['id'] = '--';  $status[0]['name'] = '--';
        $status[1]['id'] = '新缺陷';  $status[1]['name'] = '新缺陷';
        $status[2]['id'] = '同意解决';  $status[2]['name'] = '同意解决';
        $status[3]['id'] = '已解决';  $status[3]['name'] = '已解决';
        $status[4]['id'] = '已关闭';  $status[4]['name'] = '已关闭';
        $status[5]['id'] = '重新激活';  $status[5]['name'] = '重新激活';
        $status[6]['id'] = '拒绝解决';  $status[6]['name'] = '拒绝解决';
        $status[7]['id'] = '拒绝原因评审中';  $status[7]['name'] = '拒绝原因评审中';
        $status[8]['id'] = '待预发布环境验证';  $status[8]['name'] = '待预发布环境验证';


        $level[0]['id'] = '--';  $level[0]['name'] = '--';
        $level[0]['id'] = '极高';  $level[0]['name'] = '极高';
        $level[1]['id'] = '高';  $level[1]['name'] = '高';
        $level[2]['id'] = '中';  $level[2]['name'] = '中';
        $level[3]['id'] = '低';  $level[3]['name'] = '低';

        $serious[0]['id'] = '--';  $serious[0]['name'] = '--';
        $serious[0]['id'] = '致命级';  $serious[0]['name'] = '致命级';
        $serious[1]['id'] = '严重级';  $serious[1]['name'] = '严重级';
        $serious[2]['id'] = '一般级';  $serious[2]['name'] = '一般级';
        $serious[3]['id'] = '轻微级';  $serious[3]['name'] = '轻微级';

        $need = db('need')->where('projectID',$projectID)->field('id,needName as name')->select();
        $plan = db('testplan')->where('projectID',$projectID)->field('id,planName as name')->select();


        $condition[0]['name']='bugName';$condition[0]['comment']='缺陷名称';
        $condition[1]['name']='status';$condition[1]['comment']='缺陷状态';$condition[1]['children']=$status;
        $condition[2]['name']='type';$condition[2]['comment']='缺陷类型';$condition[2]['children']=$type;
        $condition[3]['name']='dealby';$condition[3]['comment']='当前处理人';$condition[3]['children']=$names;
        $condition[4]['name']='creatName';$condition[4]['comment']='创建人';$condition[4]['children']=$names;
        $condition[5]['name']='planID';$condition[5]['comment']='所属计划';$condition[5]['children']=$plan;
        $condition[6]['name']='needID';$condition[6]['comment']='所属需求';$condition[6]['children'] = $need;
        $condition[7]['name']='serious';$condition[7]['comment']='严重程度';$condition[7]['children'] = $serious;
        $condition[8]['name']='level';$condition[8]['comment']='缺陷等级';$condition[8]['children'] = $level;
        /*$condition[7]['name']='iterations';$condition[7]['comment']='迭代';$condition[7]['children'] = $iterations;*/
        return $condition;
    }


    //获取bug可导出字段
    public function getBugColumn(){
        $list=Db::query("SELECT column_name,column_comment FROM information_schema.columns WHERE table_name = 'tapd_bug' and table_schema='JiankeTapd'");
        $people = array();
        foreach($list as $key=>$value){
            if($value['column_name'] == 'id'){
                $value['column_comment'] = '缺陷ID';
                $list[$key] = $value;
            }
            if(strstr($value['column_comment'],'人')|| strstr($value['column_comment'],'时间')){
                $people[] = $value;
                unset($list[$key]);
            }
        }
        $column['base'] = $list;
        $column['people'] = $people;
        return $column;
    }
	
	public function getStatus($info){
        switch($info){
            case '同意解决':
                $status[0]['value'] = '同意解决';
                $status[0]['name'] = '保持为同意解决';
                $status[1]['value'] = '已解决';
                $status[1]['name'] = '已解决';
                $status[2]['value'] = '拒绝解决';
                $status[2]['name'] = '拒绝解决';
                break;
            case '已解决':
                $status[0]['value'] = '已解决';
                $status[0]['name'] = '保持为已解决';
                $status[1]['value'] = '已关闭';
                $status[1]['name'] = '已关闭';
                $status[2]['value'] = '重新激活';
                $status[2]['name'] = '重新激活';
                break;
            case '重新激活':
                $status[0]['value'] = '重新激活';
                $status[0]['name'] = '重新激活';
                $status[1]['value'] = '同意解决';
                $status[1]['name'] = '同意解决';
                $status[2]['value'] = '拒绝解决';
                $status[2]['name'] = '拒绝解决';
                break;
            case '拒绝解决':
                $status[0]['value'] = '拒绝解决';
                $status[0]['name'] = '保持为拒绝解决';
				$status[1]['value'] = '重新激活';
                $status[1]['name'] = '重新激活';
				$status[2]['value'] = '拒绝原因评审中';
                $status[2]['name'] = '拒绝原因评审中';
				$status[3]['value'] = '待预发布环境验证';
                $status[3]['name'] = '待预发布环境验证';
                break;
			case '拒绝原因评审中':
                $status[0]['value'] = '拒绝原因评审中';
                $status[0]['name'] = '保持为拒绝原因评审中';
				$status[1]['value'] = '已关闭';
                $status[1]['name'] = '已关闭';
                $status[2]['value'] = '重新激活';
                $status[2]['name'] = '重新激活';
                break;
			case '待预发布环境验证':
                $status[0]['value'] = '待预发布环境验证';
                $status[0]['name'] = '保持为待预发布环境验证';
				$status[1]['value'] = '已关闭';
                $status[1]['name'] = '已关闭';
                $status[2]['value'] = '重新激活';
                $status[2]['name'] = '重新激活';
                break;
			case '已关闭':
				$status[0]['value'] = '已关闭';
                $status[0]['name'] = '保持为已关闭';
                $status[1]['value'] = '重新激活';
                $status[1]['name'] = '重新激活';
				break;
            default:
                $status[0]['value'] = '新缺陷';
                $status[0]['name'] = '保持为新缺陷';
                $status[1]['value'] = '同意解决';
                $status[1]['name'] = '同意解决';
                $status[2]['value'] = '拒绝解决';
                $status[2]['name'] = '拒绝解决';
                break;
        }
        return $status;
    }
}