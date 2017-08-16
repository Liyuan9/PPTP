<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-5-15
 * Time: 下午4:55
 */
namespace app\index\model;
use think\Model;
use think\Paginator;
use think\Db;


class NeedModel extends Model
{
    public $level=array(
        '0'=>array('id'=>'极高','name'=>'极高'),
        '1'=>array('id'=>'高','name'=>'高'),
        '2'=>array('id'=>'中','name'=>'中'),
        '3'=>array('id'=>'低','name'=>'低'),
    );

    public $type=array(
        '0'=>array('id'=>'--','name'=>'--'),
        '1'=>array('id'=>'技术','name'=>'技术'),
        '2'=>array('id'=>'业务','name'=>'业务'),
    );
    public $root;
    public $child=array();
    public $fanye=array();

    //获取关联需求列表
    public function getLinkNeed($projectID,$needid=''){
        if($projectID){
            if($needid){
                $map['id'] = array('neq',$needid);
            }
            $map['projectID'] = $projectID;
            $needlist = Db('need')->where($map)->select();
            return($needlist);
        }
    }

    //获取需求筛选条件
    public function getNeedCondition($projectID){
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
        $status[0]['id'] = '--';  $status[0]['name'] = '--空--';
        $status[1]['id'] = '新需求';  $status[1]['name'] = '新需求';
        $status[2]['id'] = '规划中';  $status[2]['name'] = '规划中';
        $status[3]['id'] = '实现中';  $status[3]['name'] = '实现中';
        $status[4]['id'] = '测试排期中';  $status[4]['name'] = '测试排期中';
        $status[5]['id'] = '已拒绝';  $status[5]['name'] = '已拒绝';
        $status[6]['id'] = '已实现';  $status[6]['name'] = '已实现';
		$status[7]['id'] = '测试中';  $status[7]['name'] = '测试中';
        $status[8]['id'] = '冒烟测试失败';  $status[8]['name'] = '冒烟测试失败';
		$status[9]['id'] = '测试通过';  $status[9]['name'] = '测试通过';

        $condition[0]['name']='needName';$condition[0]['comment']='需求名称';
        $condition[1]['name']='status';$condition[1]['comment']='需求状态';$condition[1]['children']=$status;
        $condition[2]['name']='level';$condition[2]['comment']='优先级';$condition[2]['children']= $this->level;
        $condition[3]['name']='type';$condition[3]['comment']='需求类型';$condition[3]['children']=$this->type;
        $condition[4]['name']='creatName';$condition[4]['comment']='创建人';$condition[4]['children']=$names;
        $condition[5]['name']='needby';$condition[5]['comment']='需求提出者';$condition[5]['children']=$names;
        $condition[6]['name']='dealname';$condition[6]['comment']='当前处理人';$condition[6]['children']=$names;
        $condition[7]['name']='iterationID';$condition[7]['comment']='迭代';$condition[7]['children'] = Db('iteration')->where('projectID',$projectID)->field('id,iterationName as name')->select();

        return $condition;
    }

    //获取需求可导出字段
    public function getNeedColumn(){
        $list=Db::query("SELECT column_name,column_comment FROM information_schema.columns WHERE table_name = 'tapd_need' and table_schema='JiankeTapd'");
        $people = array();
        foreach($list as $key=>$value){
            if($value['column_name'] == 'id'){
                $value['column_comment'] = '需求ID';
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

    public function getNeedList($map,$query,$pa=1){
        $this->pa=$pa;
        if($query == ''){
            $list = db('need')->where($map)->order('pidNeed')->paginate(100,false,['query' =>$query]);
            $tree_need = $this->getNeedTree($list);
        }else{
            $list = db('need')->where($map)->order('pidNeed')->paginate(15,false,['query' =>$query]);
            $page = $list->render();
            $tree_need['page'] = $page;
            $tree_need['page'] = $page;
            $tree_need['tree_list'] = $this->getNeedTree($list);
            $tree_need['fanye'] = $this->getfanye($tree_need['tree_list']);
        }
        return $tree_need;
    }

    public function getNeedTree($list){
         $this->root = array();
         $this->child=array();
         $this->fanye=array();
        foreach($list as $key=>$v){
            $v['val_level'] = $this->level;
            $v['val_type'] = $this->type;
			$v['iterationID'] = db('iteration')->where('id',$v['iterationID'])->find()['iterationName'];
            $list[$key] = $v;
        }
        $need_list = $this->diguiChild($list,0);
        $data = $this->findChild($this->root);
        $need_list = array_merge($need_list,$data);
        return $need_list;
    }

    public function findChild($list){
        foreach($list as $key=>$v){
            unset($list[$key]);
            if($v['pidNeed'] != 0){
                $need_list = $this->diguiChild($list,$v['id']);
                if($need_list){
                    $v['nodes'] = $need_list;
                }
                $pa = $this->findParent($v['pidNeed'],$v);
                $this->child[] = $pa;
            }
            if(count($this->root)>=1){
                return $this->findChild($this->root);
            }
            break;
        }
        return $this->child;
    }

    public  function checkChild($parent,$list)
    {
        $temp=0;
        foreach ($list as $value){
            if($value['pidNeed']==$parent['id']){
                $temp=1;
                break;
            }
        }
        return $temp;
    }

    public  function diguiChild($data,$pid)
    {
        $result = array();
        $needlist = array();
        foreach($data as $key=>$v){

            if($v['pidNeed']==$pid){
                $needlist[] = $v;
                unset($data[$key]);
            }
        }
        for($i=0;$i<count($needlist);$i++){
            $child = $this->checkChild($needlist[$i],$data);//判断是否有子节点
            if($child==1){
                $result[$i]=$needlist[$i];
                $re=$this->diguiChild($data,$needlist[$i]['id']);
                $result[$i]['nodes']=$re;
            }else{
                $result[$i]=$needlist[$i];
            }
        }
        $this->root = $data;
        return $result;
    }

    public $parents;
    public function findParent($pid,$child){
        if($this->pa == 1){
            $parent = db('need')->alias('a')->join('iteration b','a.iterationID = b.id','left')->where('a.id',$pid)->field('a.*,b.iterationName as iterationID')->find();
        }else{
            $parent = '';
        }
        if($parent){
            $parent['nodes'][] = $child;
            if($parent['pidNeed'] != 0){
                $this->findParent($parent['pidNeed'],$parent);
            }else{
                $this->parents = $parent;
            }
        }else{
            $this->parents = $child;
        }
        return $this->parents;
    }


    public function getfanye($list){
        foreach($list as $v){
            $this->fanye[] = $v['id'];
            if(isset($v['nodes'])){
                $this->getfanye($v['nodes']);
            }
        }
        return $this->fanye;
    }
	
	public function getStatus($info){
        switch($info){
            case '规划中':
                $status[0]['value'] = '规划中';
                $status[0]['name'] = '保持为规划中';
                $status[1]['value'] = '实现中';
                $status[1]['name'] = '实现中';
                $status[2]['value'] = '已拒绝';
                $status[2]['name'] = '已拒绝';
                break;
            case '实现中':
                $status[0]['value'] = '实现中';
                $status[0]['name'] = '保持为实现中';
                $status[1]['value'] = '已实现';
                $status[1]['name'] = '已实现';
                $status[2]['value'] = '已拒绝';
                $status[2]['name'] = '已拒绝';
                break;
            case '已实现':
                $status[0]['value'] = '规划中';
                $status[0]['name'] = '规划中';
                $status[1]['value'] = '已实现';
                $status[1]['name'] = '保持为已实现';
                $status[2]['value'] = '测试排期中';
                $status[2]['name'] = '申请测试排期';
                break;
            case '测试排期中':
                $status[0]['value'] = '测试排期中';
                $status[0]['name'] = '保持为测试排期';
				$status[1]['value'] = '测试中';
                $status[1]['name'] = '测试中';
                break;
			case '测试中':
                $status[0]['value'] = '测试中';
                $status[0]['name'] = '保持为测试中';
				$status[1]['value'] = '测试通过';
                $status[1]['name'] = '测试通过';
				$status[2]['value'] = '冒烟测试失败';
                $status[2]['name'] = '冒烟测试失败';
                break;
			case '冒烟测试失败':
                $status[0]['value'] = '冒烟测试失败';
                $status[0]['name'] = '保持为冒烟测试失败';
				$status[1]['value'] = '实现中';
                $status[1]['name'] = '实现中';
                break;
			case '测试通过':
                $status[0]['value'] = '测试通过';
                $status[0]['name'] = '保持为测试通过';
                break;
			case '已拒绝':
                $status[0]['value'] = '已拒绝';
                $status[0]['name'] = '保持为已拒绝';
				$status[1]['value'] = '规划中';
                $status[1]['name'] = '规划中';
                break;
            default:
                $status[0]['value'] = '新需求';
                $status[0]['name'] = '保持为新需求';
                $status[1]['value'] = '规划中';
                $status[1]['name'] = '规划中';
                $status[2]['value'] = '已拒绝';
                $status[2]['name'] = '已拒绝';
                break;
        }
        return $status;
    }

}
