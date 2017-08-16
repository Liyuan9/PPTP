<?php
namespace app\index\controller;
use app\component\Admin;
use think\Db;
class Home extends Admin
{

	/* 工作台 */
    public function index()
    {
		$projectID = input('get.projectID');
		if($projectID){
			$map['a.projectID'] = $projectID;
		}
		$name = session('loginname');
		$map['a.dealname'] = $name['dealname'];
		$map['a.type'] = array('neq','-1');
        $map['a.status'] = array('NOT LIKE','%[抄送]');
		$deal = db('message')->where('dealname',$name['dealname'])->where('type','neq','-1')->where('status','NOT LIKE','%[抄送]')->select();
		$message = db('message')->where('dealname',$name['dealname'])->where('type','0')->count();
		session('msgnum',$message);
		session('dealnum',count($deal));
		$today = array();
		$before = array();
		$after = array();
		$need = db('message')->alias('a')->join('tapd_need b','a.linkID = b.id')->where($map)->where('a.model','need')
            ->field('b.id,b.needName,b.status,b.level,b.endTime,b.projectID')->select();
		foreach($need as $item){
			$time = strtotime(date('Y-m-d',time()));
			$endTime = strtotime(date('Y-m-d',strtotime($item['endTime'])));
			if($endTime-$time<0){
				$before[] = $item;
			}else if($endTime-$time == 0){
				$today[] = $item;
			}else{
				$after[] = $item;
			}			
		}
		$bug = db('message')->alias('a')->join('tapd_bug c','a.linkID = c.id')->where($map)->where('a.model','bug')
            ->field('c.id,c.bugName,c.status,c.level,c.serious,c.projectID')->select();
		$this->assign('bugnum',count($bug));
		$this->assign('bug',$bug);
		$this->assign('afternum',count($after));
		$this->assign('todaynum',count($today));
		$this->assign('beforenum',count($before));
		$this->assign('after',$after);
		$this->assign('today',$today);
		$this->assign('before',$before);
		$this->assign('project',$this->getProject());
		return $this->fetch();
    }
	/**********消息中心***********/
	 public function message(){
        $projectID = input('get.projectID');
        if($projectID){
            $map['a.projectID'] = $projectID;
        }
        $map['a.dealname'] = session('loginname.dealname');
        $map['a.type'] = '0';
        $message = db('message')->alias('a')->join('user b','a.user = b.dealname','left')->where($map)->order('a.id desc')->field('a.*,b.header')->select();
        session('msgnum',count($message));
        foreach($message as $key=>$item){
            switch($item['model']){
                case 'need':
                    $need = db('need')->where('id',$item['linkID'])->field('needName,sendcontent')->find();
                    $item['linkName'] = $need['needName'];
                    if(strstr($item['status'],'拒绝')){
                        $item['tip'] = '决绝了你所提的需求，当前状态为['.$item['status'].']';
                    }elseif(strstr($item['status'],'[抄送]')){
                        if($need['sendcontent'] == ''){
                            $item['tip'] = '给你抄送了一个需求:';
                        }else{
                            $item['tip'] = '给你抄送了一个需求，抄送说明：'.$need['sendcontent'];
                        }
                    }else{
                        $item['tip'] = '给你分配了一个需求，当前状态为['.$item['status'].']';
                    }
                    break;
                case 'bug':
                    $bug = db('bug')->where('id',$item['linkID'])->field('bugName,dealmethod,sendtext')->find();
                    $item['linkName'] = $bug['bugName'];
                    if($item['status'] == '拒绝处理'){
                        $item['tip'] = '决绝处理你所提的BUG，当前状态为['.$item['status'].']，解决方法是：<em class="font-red">'.$bug['dealmethod'].'</em>';
                    }elseif($item['status']=='拒绝原因评审中'){
						$item['tip'] = '请你审批BUG，当前状态为['.$item['status'].']，解决方法是：<em class="font-red">'.$bug['dealmethod'].'</em>';
                    }elseif($item['status'] == '同意解决'){
						$item['tip'] = '接受了你分配的缺陷，当前状态为['.$item['status'].']';
					}elseif(strstr($item['status'],'[抄送]')){
                        if($bug['sendtext'] == ''){
                            $item['tip'] = '给你抄送了一个需求:';
                        }else{
                            $item['tip'] = '给你抄送了一个需求，抄送说明：'.$bug['sendtext'];
                        }
                    }else{
						$item['tip'] = '给你分配了一个的缺陷，当前状态为['.$item['status'].']';
					}
                    break;
				case 'testplan':
					$plan = db('testplan')->where('id',$item['linkID'])->field('planName')->find();
                    $item['linkName'] = $plan['planName'];
                    $item['tip'] = '向你提交了一个测试任务，请安排时间对此进行测试';
                    break;
                default:break;
            }
            $message[$key] = $item;
        }
        $this->assign('project',$this->getProject());
        $this->assign('message',$message);
        $this->assign('num',count($message));
        return $this->fetch('message');
    }

    public function getProject(){
        $name = session('loginname');
        $project = db('project')->where('status','进行中')->select();
        foreach($project as $key=>$item){
            $ad['ad'] = array('like',"%{$name['id']}%");
            $com['com'] = array('like',"%{$name['id']}%");
            $ad['projectID'] = $item['id'];
            $com['projectID'] = $item['id'];
            $pro = db('project_group')->where($ad)->find();
            if(!$pro){
                $pro = db('project_group')->where($com)->find();
            }
            if(!$pro){
                unset($project[$key]);
            }
        }
        return $project;
    }

	public	function clert_temp_cache()
	{
		array_map('unlink', glob(TEMP_PATH . '/*.php'));
		@rmdir(TEMP_PATH);
		$this->error('清除缓存成功');
	}
	
	
	 public function enterproject(){
        $projectID = input('get.projectId');
        $pro = db('project')->where('id',$projectID)->field('projectName,creatname')->find();
        session('projectID',$projectID);
        session('projectName',$pro['projectName']);
		session('projectCreate',$pro['creatname']);
		if(session('projectID')){
			$this->redirect('Need/index');
		}
	 }
	 
	 /********设置头像********/
    public function setHead(){
        $id = input('get.id');
        $step = input('get.step');
        if($step == 'save'){
            $data = $_POST;
            $e = db('user')->update($data);
            if($e){
				$loginname = db('user')->where('id',$data['id'])->find();
				session('loginname',$loginname);
                return array('status'=>1,'msg'=>'设置成功');
            }else{
                return array('status'=>0,'msg'=>'设置失败');
            }
        }else{
            $info = db('user')->where('id',$id)->field('id,header')->find();
            $this->assign('info',$info);
            return $this->fetch('upimg');
        }
        
    }
	
	
	/**************获取邮件*************/
	public function mailbox(){
		$step = input('get.step');
		$info = $_POST;
		if($step == 'del'){
			$e = db('mail')->where('id',$info['id'])->delete();
			if($e){
				return array('status'=>1,'msg'=>'删除成功');
			}else{
				return array('status'=>0,'msg'=>'删除失败');
			}
		}else if($step == 'detail'){
			$message = db('mail')->where('id',$info['id'])->find()['message'];
			return array('msg'=>$message);
		}else{
			$fromto = session('loginname.dealname').'<'.session('loginname.email').'>';
			$mail = db('mail')->where('fromto',$fromto)->field('id,sendto,subject,savetime,status')->select();
			$this->assign('num',count($mail));
			$this->assign('mail',$mail);
			return $this->fetch('mailbox');
		}
		
	}
   
   
	
   
  
}
