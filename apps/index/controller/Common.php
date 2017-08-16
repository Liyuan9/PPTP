<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-5-8
 * Time: 下午4:38
 */

namespace app\index\controller;
use app\component\Admin;
use think\Db;
use app\index\model\CasesModel;
class Common extends Admin{
    public function detail(){
        return $this->fetch('detail');
    }
    public function substory(){
        return $this->fetch('substory');
    }
    public function linkbug(){
        return $this->fetch('linkbug');
    }
    public function linkstory(){
        return $this->fetch('linkstory');
    }
    public function history(){
        return $this->fetch('history');
    }



/*****************需求关联用例页面渲染*******************/
    public function linkcases(){
        $needID = input('get.needID');
        $projectID = session('projectID');
        if(session('condition')){
            $map = session('condition');
            session('condition',null);
        }
        $map['needID'] = array("NOT LIKE","%{$needID}%");
        $map['projectID'] = $projectID;
        $item = new CasesModel();
        $data = $item->getCaseCondition($projectID);
        $this->assign('data',$data);
        $this->assign('needID',$needID);
        $list=self::diguiChild(0,$projectID,$map);
        $map['list'] = -1;
        $cata["id"] = '';
        $cata["list_name"] = '未分类';
        $cata["list_pid"] = 0;
        $cata["list_level"] = 1;
        $cata["projectID"] = session('projectID');
        $cata["depict"] = '';
        $cata["text"] = "未分类";
        $child = db('cases')->where($map)->field('id,casesName as case_name')->select();
        $cata["cases"] = $child;
        $cata['tags'] = count($child);
        array_unshift($list,$cata);
        $this->assign('treelist',json_encode($list));
        return $this->fetch('linkcases');
    }

/*****************搜索用例*******************/
    public function search(){
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
        session('condition',$whereALL);
        return array('status'=>1,'message'=>'');
    }

/**********************评论页*************/
    public function comment(){
        $info['id'] = input('get.id');
        $info['ta'] = input('get.ta');
        $map['a.modelID'] = $info['id'];
        $map['a.model'] = $info['ta'];
        $this->assign('info',$info);
        $commentlist = db('comment')->alias('a')->join('user b','a.user = b.id')->where($map)->order('a.id desc')->select();
        $comment = db('comment')->alias('a')->join('user b','a.user = b.id')->where($map)->order('a.id desc')->field('a.*,b.dealname as user,b.header')->paginate(5,false,['query' => array('id'=>$info['id'],'ta'=>$info['ta'])]);
        $this->assign('commentlist',$commentlist);
        $this->assign('comment',$comment);
        return $this->fetch('comment');
    }

/**************评论添加****************/
    public function addComment(){
        $info = $_POST;
        if(!empty($info['id']) && !empty($info['ta'])){
            $map['model'] = $info['ta'];
            $map['modelID'] = $info['id'];
            $map['user'] = session('loginname.id');
            $map['time'] = date('Y年n月d日 h:i:s',time());
            $map['comment'] = $info['comment'];
			$da = db($info['ta'])->where('id',$info['id'])->field('status')->find();
			if($da){
				$map['addwhere'] = '在状态['.$da['status'].']添加';
			}
            $e = db('comment')->insert($map);
            if($e){
                return array('status'=>1,'message'=>'评论成功');
            }else{
                return array('status'=>0,'message'=>'评论失败');
            }
        }else{
            return array('status'=>0,'message'=>'评论失败');
        }
    }


/******点击铅笔快速修改***********/
    public function penEdit(){
        $info = $_POST;
        if(!empty($info['id']) && !empty($info['table'])){
            $table = $info['table'];
            unset($info['table']);
            if(isset($info['needKind'])){
                $e = db($table)->update($info);
                $list_name = db('need_kind')->where('id',$info['needKind'])->field('list_name')->find();
                if($e){
                    return array('status'=>1,'msg'=>'编辑成功','list_name'=>$list_name['list_name'],'url'=>'');
                }else{
                    return array('status'=>0,'msg'=>'编辑失败');
                }
            }else{
                if($table == 'project_group'){
                    $user = db('project_group')->where('projectId',session('projectID'))->find();
                    if($info['role'] == '普通成员'){
                        if($user['com'] != ''){
                            $user['com'] = $user['com'].','.$info['id'];
                        }else{
                            $user['com'] = $info['id'];
                        }
                        $item = explode(',',$user['ad']);
                        $sign = 'ad';
                    }else{
                        $user['ad'] == ''?$user['ad'] = $info['id']:$user['ad'] = $user['ad'].','.$info['id'];
                        $item = explode(',',$user['com']);
                        $sign = 'com';
                    }
                    $data['user'] = '';
                    foreach($item as $v){
                        if($v != $info['id']){
                            $data['user'] == ''?$data['user']=$v:$data['user'] = $data['user'].','.$v;
                        }
                    }
                    if($sign == 'ad'){
                        $user['ad'] = $data['user'];
                    }else{
                        $user['com'] = $data['user'];
                    }
                    $e = db('project_group')->where('id',$user['id'])->update($user);
                    if($e){
                        return array('status'=>1,'msg'=>'编辑成功','url'=>'../set/user');
                    }else{
                        return array('status'=>0,'msg'=>'编辑失败');
                    }
                }else{
                    $data = db($table)->where('id',$info['id'])->find();
                    $e = db($table)->update($info);
                    if($e){
                        $this->saveHistory($info['id'],$table,$data,$info);
                        return array('status'=>1,'msg'=>'编辑成功','url'=>'');
                    }else{
                        return array('status'=>0,'msg'=>'编辑失败');
                    }
                }
            }
        }else{
            return array('status'=>0,'msg'=>'编辑失败');
        }

    }
/***************附件添加*********************/
    public function addFile(){
        $info = $_POST;
        if(!empty($info['id']) && !empty($info['ta'])){
            $table = $info['ta'];
            unset($info['ta']);
            $data = db($table)->where('id',$info['id'])->field('upload')->find();
            if(!empty($data['upload'])){
                $info['upload'] = $data['upload'].'|'.$info['upload'];
            }
            $e = db($table)->update($info);
            if($e){
                return array('status'=>1,'message'=>'上传成功');
            }else{
                return array('status'=>0,'message'=>'上传失败');
            }
        }else{
            return array('status'=>0,'message'=>'上传失败');
        }
    }

/**************删除*************/
    public function del(){
        $info = $_POST;
        $projectID = session('projectID');
        $map['projectID'] = $projectID;
        if(!empty($info['id']) && !empty($info['table'])){
            $table = $info['table'];
            switch($table){
                case 'need':
                    $map['needID'] = array('like',"%{$info['id']}%");
                    $case = db('cases')->where($map)->field('id,needID')->select();
                    if($case){
                        foreach($case as $value){
                            $value['needID'] = explode(',',$value['needID']);
                            $data['needID'] = '';
                            foreach($value['needID'] as $vo){
                                if($vo != $info['id']){
                                    $data['needID'] == ''?$data['needID'] = $vo:$data['needID']=$data['needID'].','.$vo;
                                }
                            }
                            $g = db('cases')->where('id',$value['id'])->update($data);
                            if(!$g){
                                return array('status'=>0,'msg'=>'解除此需求所关联的测试用例失败，暂时不能对此执行删除');
                            }
                        }
                    }
                    unset($map['needID']);
                    $link = '"needID":"'.$info['id'].'"';
                    $map['linkID'] = array('LIKE','%{$link}%');
                    $case = db('bug')->where($map)->field('linkID')->select();
                    foreach($case as $v){
                        $item = explode('|',$v['linkID']);
                        $linkID = '';
                        foreach($item as $vo){
                            if(!strpos($vo,$link)){
                                $linkID == ''?$linkID=$vo:$linkID=$linkID.'|'.$vo;
                            }
                        }
                        $data['linkID'] = $linkID;
                        $g = db('bug')->where('id',$v['id'])->update($data);
                        if(!$g){
                            return array('status'=>0,'msg'=>'解除此需求所关联的缺陷失败，暂时不能对此执行删除');
                        }
                    }
                    $e = db('need')->where('id',$info['id'])->delete();
                    if($e){
                        return array('status'=>1,'msg'=>'已成功删除','url'=>'../need/index');
                    }else{
                        return array('status'=>0,'msg'=>'删除失败');
                    }
                    break;
                case 'cases':
					$name = session('loginname');
					if(!strstr($name['position'],'测试')){
						return array('status'=>0,'msg'=>'你不是测试人员，不能对测试用例执行删除'); // 验证失败 输出错误信息
					}
                    $link = '"caseID":"'.$info['id'].'"';
                    $map['linkID'] = array('LIKE','%{$link}%');
                    $case = db('bug')->where($map)->field('linkID')->select();
                    foreach($case as $v){
                        $item = explode('|',$v['linkID']);
                        $linkID = '';
                        foreach($item as $vo){
                            if(!strpos($vo,$link)){
                                $linkID == ''?$linkID=$vo:$linkID=$linkID.'|'.$vo;
                            }
                        }
                        $data['linkID'] = $linkID;
                        $g = db('bug')->where('id',$v['id'])->update($data);
                        if(!$g){
                            return array('status'=>0,'msg'=>'解除此用例所关联的缺陷失败，暂时不能对此执行删除');
                        }
                    }
                    $e = db('cases')->where('id',$info['id'])->delete();
                    if($e){
                        return array('status'=>1,'msg'=>'已成功删除','url'=>'../cases/index');
                    }else{
                        return array('status'=>0,'msg'=>'删除失败');
                    }
                    break;
                case 'testplan':
                    $link = '"planID":"'.$info['id'].'"';
                    $map['linkID'] = array('LIKE','%{$link}%');
                    $bug = db('bug')->where($map)->field('linkID')->select();
                    foreach($bug as $v){
                        $item = explode('|',$v['linkID']);
                        $linkID = '';
                        foreach($item as $vo){
                            if(!strpos($vo,$link)){
                                $linkID == ''?$linkID=$vo:$linkID=$linkID.'|'.$vo;
                            }
                        }
                        $data['linkID'] = $linkID;
                        $g = db('bug')->where('id',$v['id'])->update($data);
                        if(!$g){
                            return array('status'=>0,'msg'=>'解除此计划所关联的缺陷失败，暂时不能对此执行删除');
                        }
                    }
                    $e = db('testplan')->where('id',$info['id'])->delete();
                    if($e){
                        return array('status'=>1,'msg'=>'已成功删除','url'=>'../testplan/index');
                    }else{
                        return array('status'=>0,'msg'=>'删除失败');
                    }
                    break;
                case 'project_group':
                    $list = db('project_group')->where('projectID',$projectID)->find();
                    $data = array();
                    if(strstr($list['ad'],$info['id'])){
                        $user = explode(',',$list['ad']);
                        if(count($user)<=1){
                            return array('status'=>0,'msg'=>'项目必须保留一位管理员，若要移除此成员，需先为项目设定另一个管理员');
                        }
                        $data['ad'] = '';
                        foreach($user as $vi){
                            if($vi!=$info['id']){
                                $data['ad'] == ''?$data['ad'] = $vi:$data['ad'] = $data['ad'].','.$vi;
                            }
                        }
                    }else if(strstr($list['com'],$info['id'])){
                        $user = explode(',',$list['com']);
                        $data['com'] = '';
                        foreach($user as $vi){
                            if($vi!=$info['id']){
                                $data['com'] == ''?$data['com'] = $vi:$data['com'] = $data['com'].','.$vi;
                            }
                        }
                    }
                    $e = db('project_group')->where('id',$list['id'])->update($data);
                    if($e){
                        return array('status'=>1,'msg'=>'已成功删除','url'=>'../set/user');
                    }else{
                        return array('status'=>0,'msg'=>'删除失败');
                    }
                    break;
                case 'bug':
                    $e = db('bug')->where('id',$info['id'])->delete();
                    if($e){
                        return array('status'=>1,'msg'=>'已成功删除','url'=>'../bugtrace/index');
                    }else{
                        return array('status'=>0,'msg'=>'删除失败');
                    }
                    break;
				case 'modular':
                    $g = db('bug')->where('model','like',"{$info['id']}")->where('projectID',$projectID)->select();
                    if($g){
                        return array('status'=>0,'msg'=>'你选中的模块已被关联缺陷，暂时不能删除');
                    }else{
                        $e = db('modular')->where('id',$info['id'])->delete();
                        if($e){
                            return array('status'=>1,'msg'=>'已成功删除','url'=>'../set/modular');
                        }else{
                            return array('status'=>0,'msg'=>'删除失败');
                        }
                    }
                    break;
                case 'version':
                    $g = db('bug')->where('findversion','like',"{$info['id']}")->where('projectID',$projectID)->select();
                    if($g){
                        return array('status'=>0,'msg'=>'你选中的模块已被关联缺陷，暂时不能删除');
                    }else{
                        $e = db('version')->where('id',$info['id'])->delete();
                        if($e){
                            return array('status'=>1,'msg'=>'已成功删除','url'=>'../set/version');
                        }else{
                            return array('status'=>0,'msg'=>'删除失败');
                        }
                    }
                    break;
				case 'iteration':
                    $need = db('need')->where('iterationID',$info['id'])->select();
                    $bug = db('bug')->where('iterationID',$info['id'])->select();
                    if($need == ''&&$bug == ''){
                        $e = db('iteration')->where('id',$info['id'])->delete();
                        if($e){
                            return array('status'=>1,'msg'=>'已成功删除','url'=>'../iteration/index');
                        }else{
                            return array('status'=>0,'msg'=>'删除失败');
                        }
                    }else{
                        return array('status'=>0,'msg'=>'此迭代有规划需求或者缺陷，不可删除！');
                    }
                    break;
                default:
                    return array('status'=>0,'msg'=>'删除失败');
                    break;
            }
        }else{
            return array('status'=>0,'msg'=>'删除失败');
        }
    }

/******************新增测试用例子目录*************/
    public function addCatalog(){
        $info = $_POST;
        if(!empty($info['table'])){
            $table = $info['table'];
            unset($info['table']);
            $info['projectID'] = session('projectID');
            $level = db($table)->where(array('id'=>$info['list_pid']))->field('list_level')->find();
            if($level){
                $info['list_level'] = $level['list_level'] + 1;
            }else{
                $info['list_level'] = 1;
            }
            $g = db($table)->insertGetId($info);
            if($g){
                return array('status'=>1,'msg'=>'新增子目录成功','id'=>$g);
            }else{
                return array('status'=>0,'msg'=>'新增子目录失败');
            }
        }
    }


/**************************获取可显示字段**********************/

    public function setthead(){
        $table = input('get.ta');
        if($table == 'cases'){
            $base=Db::query("SELECT column_name,column_comment FROM information_schema.columns WHERE table_name = 'tapd_cases' and table_schema='JiankeTapd'");
            unset($base[11]); unset($base[12]);
            $endbase[]=array_pop($base); $endbase[]=array_pop($base); $endbase[]=array_pop($base); $endbase[]=array_pop($base);

        }
        $this->assign('base',$base);
        $this->assign('endbase',$endbase);
        return $this->fetch('setthead');
    }

/***************************视图视图筛选*****************/
    public function chooseView(){
        $info = $_POST;
        if($info){
            switch($info['table']){
                case 'testplan':
                    switch($info['view']){
                        case '未关闭':
                            $map['status'] = '开启';
                            break;
                        case '已关闭':
                            $map['status'] = '关闭';
                            break;
                        case '我负责的':
                            $map['responsible'] = session('loginname.dealname');
                            break;
                        case '我创建的':
                            $map['creatName'] = session('loginname.dealname');
                            break;
                        default:
                            $map = null;
                            break;
                    }
                    session('whereAll',$map);
                    session('view',$info['view']);
                    $url = '../testplan/index';
                    break;
                case 'bug':
                    switch($info['view']){
                        case '未关闭':
                            $map['status'] = array('neq','已关闭');
                            break;
                        case '已关闭':
                            $map['status'] = '已关闭';
                            break;
                        case '待我处理的':
                            $map['dealby'] = session('loginname.dealname');
                            break;
                        case '我创建的':
                            $map['creatName'] = session('loginname.dealname');
                            break;
                        default:
                            $map = null;
                            break;
                    }
                    session('whereAll',$map);
                    session('view',$info['view']);
                    $url = '../bugtrace/index';
                    break;
                default:
                    return array('status'=>0,'message'=>'搜索此视图出错',);
                    break;
            }
            return array('status'=>1,'message'=>'','url'=> $url );
        }else{
            return array('status'=>0,'message'=>'搜索此视图出错',);
        }
    }

/**************************确定关联******
 *测试用例关联需求
 *任务关联需求
 *测试计划关联需求
 *迭代关联需求
 *发布计划关联需求
 *************************/
    public function sureLink(){
        $info = $_POST;
        if($info){
            switch($info['link']){
                case 'cases':
                    if($info['linkby'] == 'need'){
                        $info['needID'] = $info['id'];
                        unset($info['id']);
                    }
                    $caseid = $info['linkid'];
                    foreach($caseid as $v){
                        $case = db('cases')->where('id',$v)->field('needID')->find();
                        if(!empty($case['needID'])){
                            if(!strpos($case['needID'],$info['needID'])){
                                $case['needID'] = $case['needID'].','.$info['needID'];
                                $e = db('cases')->where('id',$v)->update($case);
                            }
                        }else{
                            $case['needID'] = $info['needID'];
                            $e = db('cases')->where('id',$v)->update($case);
                        }
                        if(!$e){
                            return array('status'=>0,'msg'=>'关联测试用例失败');
                        }
                    }
                    break;
                case 'testplan':
                    if($info['linkby'] == 'need'){
                        $planid = $info['id'];
                        unset($info['id']);
                    }
                    $needid = $info['linkid'];
                    $data = db('testplan')->where('id',$planid)->field('needID')->find();
                    foreach($needid as $v){
                        $data['needID']== null?$data['needID']=$v:$data['needID']=$data['needID'].','.$v;
                    }
                    $e = db('testplan')->where('id',$planid)->update($data);
                    if(!$e){
                        return array('status'=>0,'msg'=>'关联需求失败');
                    }
                    break;
                case 'bug':
                    if($info['id'] == ''){
                        $linkID = session('linkID');
                        session('linkID',null);
                    }
                    $bugid = $info['linkid'];
                    $map['linkID'] = array('NOT LIKE','%{$linkID}%');
                    foreach($bugid as $vo){
                        $map['id'] = $vo;
                        $data = db('bug')->where($map)->field('linkID')->find();
                        if($data['linkID']){
                            $data['linkID'] = $data['linkID'].'|'.$linkID;
                        }else{
                            $data['linkID'] = $linkID;
                        }
                        $e = db('bug')->where('id',$vo)->update($data);
                        if(!$e){
                            return array('status'=>0,'msg'=>'关联缺陷失败');
                        }
                    }
                    break;
                default:
                    return array('status'=>0,'msg'=>'关联失败');
                    break;
            }
            if($e){
                return array('status'=>1,'msg'=>'关联成功');
            }
        }else{
            return array('status'=>0,'msg'=>'请先选择所需关联的对象');
        }
    }
/*************************取消关联*****************************/
    public function cancelLink(){
        $info = $_POST;
        if($info){
            switch($info['table']){
                case 'cases':
                    if($info['sign'] == 'need'){
                        $needID = $info['cancelID'];
                    }
                    $data = db('cases')->where('id',$info['id'])->field('needID')->find();
                    if($data){
                        $linkNeed = explode(',',$data['needID']);
                        $need_id = '';
                        foreach($linkNeed as $key=>$v){
                            if($v != $needID){
                                $need_id == ''?$need_id = $v:$need_id=$need_id.','.$v;
                            }
                        }
                        $da['needID'] = $need_id;
                        $e = db('cases')->where('id',$info['id'])->update($da);
                    }
                    break;
                case 'testplan':
                    if($info['sign'] == 'need'){
                        $needID = $info['cancelID'];
                    }
                    $data = db('testplan')->where('id',$info['id'])->field('needID')->find();
                    if($data){
                        $linkNeed = explode(',',$data['needID']);
                        $need_id = '';
                        foreach($linkNeed as $v){
                            if($v != $needID){
                                $need_id == ''?$need_id = $v:$need_id=$need_id.','.$v;
                            }
                        }
                        $da['needID'] = $need_id;
                        $e = db('testplan')->where('id',$info['id'])->update($da);
                    }
                    break;
                case 'bug':
                    if($info['sign'] == 'need'){
                        $link = '"needID":"'.$info['cancelID'].'"';
                    }else if($info['sign'] == 'case'){
                        $link = '"caseID":"'.$info['cancelID'].'"';
                    }
                    $data = db('bug')->where('id',$info['id'])->field('linkID')->find();
                    if($data){
                        $links = explode('|',$data['linkID']);
                        $link_ID = '';
                        foreach($links as $item){
                            if(!strpos($item,$link)){
                                $link_ID == ''?$link_ID=$item:$link_ID = $link_ID.'|'.$item;
                            }
                        }
                        $da['linkID'] = $link_ID;
                        $e = db('bug')->where('id',$info['id'])->update($da);
                    }
                    break;
                default:
                    return array('status'=>0,'msg'=>'取消关联失败');
                    break;
            }
            if($e){
                return array('status'=>1,'msg'=>'已成功取消关联');
            }else{
                return array('status'=>0,'msg'=>'取消关联失败');
            }
        }else{
            return array('status'=>0,'msg'=>'取消关联失败');
        }
    }

/**************移出计划************/
    function  removeplan(){
        $info = $_POST;
        $uncase['needID'] = $info['needID'];
        $uncase['caseid'] = $info['casesID'];
        if($info){
            $data = db('testplan')->where('id',$info['id'])->field('casesID')->find();
            if(!empty($data['casesID'])){
                $data['casesID'] = explode('|',$data['casesID']);
                $unlink = '';
                $i = 0;
                foreach($data['casesID'] as $v){
                    $case = json_decode($v,true);
                    if($case['needID'] == $info['needID']){
                        $case['caseid'] = $case['caseid'].','.$info['casesID'];
                        $i++;
                    }
                    $case = json_encode($case);
                    $unlink==''?$unlink=$case:$unlink=$unlink.'|'.$case;
                }
                if($i<1){
                    $data['casesID'] = $data['casesID'].'|'.json_encode($uncase);
                }else{
                    $data['casesID'] = $unlink;
                }

            }else{
                $data['casesID'] = json_encode($uncase);
            }
            $e = db('testplan')->where('id',$info['id'])->update($data);
            if($e){
                return array('status'=>1,'msg'=>'已成功移出');
            }else{
                return array('status'=>0,'msg'=>'移出计划失败');
            }
        }else{
            return array('status'=>0,'msg'=>'移出计划失败');
        }

    }

/****************************循环树*******************************/
    private function checkChild($oneList)
    {
        $allList=db('cases_list')->where('projectID',1)->order('list_level asc')->select();
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
    private function diguiChild($pid,$projectID,$map)
    {
        $result = array();
        $topList=db('cases_list')->where('projectID',$projectID)->where('list_pid',$pid)->order('list_level asc')->select();
        $caselist=$topList;
        $size = count($caselist);
        $i=0;
        for ($i;$i < $size;$i++){
            $keys=self::checkChild($caselist[$i]);//判断是否有子节点
            $map['list'] = $caselist[$i]['id'];
            $case = db('cases')->where($map)->field('id,casesName as case_name')->select();
            $caselist[$i]['text'] = $caselist[$i]['list_name'];
            $caselist[$i]['cases'] = $case;
            if ($keys==1){
                $result[$i]=$caselist[$i];
                $re=self::diguiChild($caselist[$i]['id'],$projectID,$map);
                $result[$i]['nodes']=$re;
                $count=self::allcount($re);
                $result[$i]['tags'] = count($case)+$count; //关联用例数
            }else{
                $caselist[$i]['tags'] = count($case); //关联用例数
                $result[$i]=$caselist[$i];
            }
        }
        return $result;
    }
	
	  public function lookdiff(){
        $id = input('get.step');
        $info = db('history')->where('id',$id)->find();
        $this->assign('info',$info);
        return $this->fetch('diff');
    }
	
	    public function changestatus(){
        $info = $_POST;
        $table = $info['table'];
        unset($info['table']);
        $e = db($table)->where('id',$info['id'])->update($info);
        if($e){
            return array('status'=>1);
        }else{
            return array('status'=>0,'msg'=>'设置状态失败');
        }
    }


}

