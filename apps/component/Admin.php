<?php
namespace app\component;
use think\Controller;
use think\Validate;
use think\Db;
class Admin extends Controller
{
	 function __construct()
    {	
        parent::__construct();
		if(session('loginname')===null){$this -> error('访问超时',SITE_URL);}
		$dealnum = db('message')->where('dealname',session('loginname.dealname'))->where('type','neq','-1')->where('status','NOT LIKE','%[抄送]')->count();
        $msgnum = db('message')->where('dealname',session('loginname.dealname'))->where('type','eq','0')->count();
		session('dealnum',$dealnum);
		session('msgnum',$msgnum);
		
		/* $sql="select role_auth_ac from tapd_user join tapd_role on tapd_user.qxid=tapd_role.role_id where tapd_user.namepy='".session('name')."'";
		$roles=db()->query($sql);
		$role=$roles[0]['role_auth_ac'];
		
		$now=request()->controller()."-".request()->action();
		$allow=array("Home-left","Home-right","Home-index","Home-head");
		if(!in_array($now,$allow) && stripos($role,$now)===false){
			$this -> error('没有权限访问',SITE_URL);
			}  */
		
    }

    /*******存入变更历史*****
     *
     * @data 变更前
     * @info 变更后
     */
    public function saveHistory($id,$table,$data,$info){
        $history = array();
        $after = array_diff_assoc($info,$data);
        $before = array_diff_assoc($data,$info);
        $aft = array_intersect_key($after,$before);
        $bef = array_intersect_key($before,$after);
        foreach($aft as $key=>$v){
            $result['after_sign'] = $v;
            $result['before_sign'] = $bef[$key];
            $result['column_sign'] = $key;
            $history[] = $result;
        }
        $comment = Db::query("SELECT column_name, column_comment FROM information_schema.columns WHERE table_name = 'tapd_".$table."' and table_schema='JiankeTapd'" );
        foreach($comment as $key=>$value){
            unset($comment[$key]);
            $key = $value['column_name'];
            $comment[$key] = $value['column_comment'];
		}
		 foreach($history as $key=>$item){
            $item['column_sign'] = $comment[$item['column_sign']];
            $item['changeID'] = $id;
            $item['run_people'] = session('loginname.dealname');
            $item['addTime'] = date("Y-m-d H:i:s",time());
            $item['changeTable'] = $table;
            $history[$key] = $item;
        }
        if($history){
            foreach($history as $v){
                db('history')->insert($v);
            }
        }
    }

   public function checkHistory($data){
        static $results = array();
        if($data){
            foreach($data as $key=>$value){
                switch($value['column_sign']){
                    case '附件':
                        $before_file = explode('|',$value['before_sign']);
                        $after_file = explode('|',$value['after_sign']);
                        $after_sign = '';
                        $before_sign = '';
                        foreach($before_file as $v){
                            $v= json_decode($v,true);
                            $before_sign == ''?$before_sign = $v['title']:$before_sign = $before_sign.'，'.$v['title'];
                        }
                        foreach($after_file as $v){
                            $v= json_decode($v,true);
                            $after_sign == ''?$after_sign = $v['title']:$after_sign = $after_sign.'，'.$v['title'];
                        }
                        $value['before_sign'] = $before_sign;
                        $value['after_sign'] = $after_sign;
                        break;
                    case '前置条件':
                    case '用例步骤':
                    case '预期结果':
                    case '步骤':
                    case '结果':
                    case '期望':
                    case '需求描述':
                    case '计划描述':
                        $value['before_sign'] = '查看详情';
                        $value['after_sign'] = '查看详情';
                        break;
                    default:
                        break;
                }
                $time = $value['addTime'];
                $people = $value['run_people'];
                $res['id'] = $value['id'];
                $res['addTime'] = $value['addTime'];
                $res['run_people'] = $value['run_people'];
                $child = $this->findchild($data,$time,$people);
                if($child['child']){
                    $res['result'] = $child['child'];
                }else{
                    $chil['id'] = $value['id'];
                    $chil['column_sign'] = $value['column_sign'];
                    $chil['before_sign'] = $value['before_sign'];
                    $chil['after_sign'] = $value['after_sign'];
                    $res['result'][] = $chil;
                }
                $results[] = $res;
                return $this->checkHistory($child['data']);
            }
        }
        return $results;
    }

    public function findchild($data,$time,$people){
        $result = array();
        foreach($data as $key=>$value){
            switch($value['column_sign']){
                case '附件':
                    $before_file = explode('|',$value['before_sign']);
                    $after_file = explode('|',$value['after_sign']);
                    $after_sign = '';
                    $before_sign = '';
                    foreach($before_file as $v){
                        $v= json_decode($v,true);
                        $before_sign == ''?$before_sign = $v['title']:$before_sign = $before_sign.'，'.$v['title'];
                    }
                    foreach($after_file as $v){
                        $v= json_decode($v,true);
                        $after_sign == ''?$after_sign = $v['title']:$after_sign = $after_sign.'，'.$v['title'];
                    }
                    $value['before_sign'] = $before_sign;
                    $value['after_sign'] = $after_sign;
                    break;
                case '前置条件':
                case '用例步骤':
                case '预期结果':
                case '步骤':
                case '结果':
                case '期望':
                case '需求描述':
                case '计划描述':
                    $value['before_sign'] = '查看详情';
                    $value['after_sign'] = '查看详情';
                    break;
                default:
                    break;
            }
            if($value['addTime'] == $time && $value['run_people'] == $people){
                $child['id'] = $value['id'];
                $child['column_sign'] = $value['column_sign'];
                $child['before_sign'] = $value['before_sign'];
                $child['after_sign'] = $value['after_sign'];
                $result['child'][] = $child;
                unset($data[$key]);
            }else{
                $data[$key] = $value;
            }
        }
        $result['data'] = $data;
        return $result;
    }

    public function getFirstChar($str){
        $s0 = mb_substr($str,0,1);             //获取名字的姓
        $s = iconv('UTF-8','gb2312', $s0);       //将UTF-8转换成GB2312编码
        if (ord($s)>=97 and ord($s)<=122) {    //小写英文开头
            if(ord($s) == 97)return "A";
            if(ord($s) == 98)return "B";
            if(ord($s) == 99)return "C";
            if(ord($s) == 100)return "D";
            if(ord($s) == 101)return "E";
            if(ord($s) == 102)return "F";
            if(ord($s) == 103)return "G";
            if(ord($s) == 104)return "H";
            if(ord($s) == 105)return "I";
            if(ord($s) == 106)return "J";
            if(ord($s) == 107)return "K";
            if(ord($s) == 108)return "L";
            if(ord($s) == 109)return "M";
            if(ord($s) == 110)return "N";
            if(ord($s) == 111)return "O";
            if(ord($s) == 112)return "P";
            if(ord($s) == 113)return "Q";
            if(ord($s) == 114)return "R";
            if(ord($s) == 115)return "S";
            if(ord($s) == 116)return "T";
            if(ord($s) == 117)return "U";
            if(ord($s) == 118)return "V";
            if(ord($s) == 119)return "W";
            if(ord($s) == 120)return "X";
            if(ord($s) == 121)return "Y";
            if(ord($s) == 122)return "Z";
        }
    }

    public function checkName($data){
        $user = array();
        foreach($data as $v){
            $sign = $this->getFirstChar($v['namepy']);
            $user[$sign][]=$v;
        }
        ksort($user);
        return $user;
    }

    public function getNames($projectID){
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
            $names[$value]=$ss['dealname'];
        }
        return $names;
    }
	
	 public function addMessage($id,$model,$dealname,$status,$type,$comment=''){
		$data['model'] = $model;
		$data['user'] = session('loginname.dealname');
		$data['time'] = date('Y-m-d H:i:s',time());
		$data['linkID'] = $id;
		$data['dealname'] = $dealname;
		$data['projectID'] = session('projectID');
		$data['status'] = $status;
		$data['endtime'] = date('Y-m-d H:i:s',time()+432000);
		$data['comment'] = $comment;
		$info = db('message')->where('linkID',$id)->where('status','NOT LIKE','%[抄送]')->find();
		if($info){
			$data['type'] = $type;
			db('message')->where('id',$info['id'])->update($data);
		}else{
			$data['type'] = $type;
			db('message')->insert($data);
		}
        
    }




}
