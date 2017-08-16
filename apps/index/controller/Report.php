<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-8-4
 * Time: 下午3:35
 */
namespace app\index\controller;
use app\component\Admin;
use app\index\model\NeedModel;
use app\index\model\BugtraceModel;
use think\Db;
class Report extends admin{
    public function index(){
        $step = input('get.step');
        $projectID = session('projectID');
        if($step == 'need'){
            $item = new NeedModel();
            //获取需求筛选条件
            $condition = $item->getNeedCondition($projectID);
            $this->assign('data',$condition);
            $column = $item->getNeedColumn();
            $col = array_merge($column['base'],$column['people']);
            foreach($col as $key=>$vo){
                if($vo['column_name'] == 'bugID'||$vo['column_name'] == 'needName' || $vo['column_name'] == 'sendto' || $vo['column_name'] == 'sendcontent' || $vo['column_name'] == 'upload' || $vo['column_name'] == 'needDepict'
                || $vo['column_name'] == 'pidNeed' || $vo['column_name'] == 'needKind' || strstr($vo['column_comment'],'ID')){
                    unset($col[$key]);
                }
            }
            $this->assign('table','tapd_need');
            $this->assign('column',$col);
            $this->assign('title','需求统计分布');
            return $this->fetch('reportneed');
        }else if($step == 'bug'){
            $item = new BugtraceModel();
            //获取bug筛选条件
            $condition = $item->getBugCondition($projectID);
            $this->assign('data',$condition);
            $column = $item->getBugColumn();
            $col = array_merge($column['base'],$column['people']);
            foreach($col as $key=>$vo){
                if($vo['column_name'] == 'step'||$vo['column_name'] == 'result' || $vo['column_name'] == 'wish' || $vo['column_name'] == 'bugName' || $vo['column_name'] == 'upload' || $vo['column_name'] == 'model'
                    || $vo['column_name'] == 'sendtext' || $vo['column_name'] == 'sendto' || strstr($vo['column_comment'],'ID')){
                    unset($col[$key]);
                }
            }
            $this->assign('table','tapd_bug');
            $this->assign('column',$col);
            $this->assign('title','缺陷统计分布');
            return $this->fetch('reportneed');
        }else{
            session('model','report');
            return $this->fetch();
        }
    }

    public function search(){
        $data = $_POST;
        if($data['X'] == ''){
            return array('status'=>0,'message'=>'请选择横轴！');
        }
        if($data['Y'] == ''){
            return array('status'=>0,'message'=>'请选择纵轴！');
        }
        $Xsign = explode('|',$data['X']);
        $Ysign = explode('|',$data['Y']);
        $X_sign = $Xsign[0]; //X轴
        $Y_sign = $Ysign[0]; //Y轴
        unset($data['X']);unset($data['Y']);
        $table = $data['table'];unset($data['table']);
        $where = '';
        $start = '';
        $end = '';
        if($data['timeType'] == '固定时间'){
            $start = $data['start'];
            $end = $data['end'];
            unset($data['start']);
            unset($data['end']);
        }else if($data['timeType'] == '动态时间之内'){
            $day = $data['day']*86400;
            $end = date('Y-m-d',time());
            $start = date('Y-m-d',strtotime($end)-$day);
            unset($data['day']);
        }else{
            $day = $data['day']*86400;
            $end = date('Y-m-d',time());
            $end = date('Y-m-d',strtotime($end)-$day);
            unset($data['day']);
        }
        unset($data['timeType']);
        if($start != '' && $end != ''){
            $where = 'creatTime BETWEEN \''.$start. '\' AND \''.$end.'\'';
        }else if($start == '' && $end != ''){
            $where = 'creatTime <= \''.$end.'\'';
        }else if($start != '' && $end == ''){
            $where = 'creatTime >= \''.$start.'\'';
        }
        foreach($data as $key=>$v){
            if($v == '--'||$v == null){
                unset($data[$key]);
            }else if(!isset($data['planID']) && !isset($data['needID'])){
                $where != ''? $where = $where.' AND '.$key.' = \''.$v.'\'':$where=$key.'= \''.$v.'\'';
            }else{
                if($table == 'tapd_bug'){
                    if(isset($data['planID']) && isset($data['needID'])){
                        $link = '"planID":'.'"'.$data['planID'].'","needID":'.'"'.$data['needID'].'"';
                    }elseif(isset($data['planID'])){
                        $link = '"planID":'.'"'.$data['planID'].'"';
                    }else{
                        $link = '"needID":'.'"'.$data['needID'].'"';
                    }
                    $where != ''? $where = $where.' AND linkID like \'%'.$link.'%\'':$where='linkID like \'%'.$link.'%\'';
                }

            }
        }
        $where != ''? $where = $where.' AND projectID = \''.session('projectID').'\'':$where='projectID = \''.session('projectID').'\'';
        $Ylist = Db::query("select ".$X_sign.",".$Y_sign.',count('.$Y_sign.") as num from ".$table." where ".$where."group by ".$Y_sign.','.$X_sign);
        $X = array();
        $Y = array();
        foreach($Ylist as $key=>$v){
            if($X_sign == 'iterationID'){
                $v[$X_sign] = db('iteration')->where('id',$v[$X_sign])->find()['iterationName'];
            }
            if($Y_sign == 'iterationID'){
                $v[$Y_sign] = db('iteration')->where('id',$v[$Y_sign])->find()['iterationName'];
            }
            if($X_sign == 'findversion'){
                $v[$X_sign] = db('version')->where('id',$v[$X_sign])->find()['title'];

            }
            if($Y_sign == 'findversion'){
                $v[$Y_sign] = db('version')->where('id',$v[$Y_sign])->find()['title'];
            }
            if($v[$Y_sign] == '--' || $v[$Y_sign] == NULL){
                $v[$Y_sign] = '空';
            }
            if($v[$X_sign] == '--' || $v[$X_sign] == NULL){
                $v[$X_sign] = '空';
            }
            if($X_sign == 'value'){
                $X[] = $v[$X_sign].'分';
                $v['name'] = $v[$X_sign].'分';
            }else{
                $X[] = $v[$X_sign];
                $v['name'] = $v[$X_sign];
            }
            if($Y_sign == 'value'){
                $Y[] = $v[$Y_sign].'分';
                $v['name'] = $v[$Y_sign].'分';
            }else{
                $Y[] = $v[$Y_sign];
                $v['name'] = $v[$Y_sign];
            }
            $v['x'] = $v[$X_sign];
            $v['type'] = 'bar';
            $Ylist[$key] = $v;
        }
        $X = array_unique($X);
        $Y = array_unique($Y);
        $list['X'] = $X;
        $list['Y'] = $Y;
        $list['xsign'] = $Xsign[1];
        $list['ysign'] = $Ysign[1];
        $list['data'] = $this->getYNum($X,$Y,$Ylist,'bar');
        return array('status'=>1,'list'=>$list);
    }



    function  getYNum($X,$Y,$Ylist,$type){
        $list = array();
        foreach($Y as $y){
            $data['name'] = $y;
            $data['type'] = $type;
            $label['position'] = 'inside';
            $label['show'] = true;
            $normal['normal'] = array('label'=>$label);
            $data['itemStyle'] = $normal;
            $num = array();
            foreach($X as $x){
                $nu = 0;
                foreach($Ylist as $vi){
                    if($vi['name'] == $y && $vi['x'] == $x){
                        $nu = $nu+$vi['num'];
                    }
                }
                $num[] = $nu;
            }
            $data['data'] = $num;
            $list[] = $data;
        }
        return $list;
    }
}

