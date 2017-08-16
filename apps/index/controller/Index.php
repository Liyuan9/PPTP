<?php
namespace app\index\controller;
use think\Controller;
use think\Validate;
class Index extends Controller
{
    public function index()
    {	
        $name=session('loginname');   
        if(!empty($name)){
            $this->redirect(url('Home/index'));
	    }else{
            $status='';
            $this->assign('status',$status);
            return $this->fetch();
        }
    }
	
	public function login(){
        $status='密码错误';
        $this->assign('status',$status);
        $validate = new Validate([
        'name'  => 'require|max:25|token',
        'password'=>'require',
        ]);

        if (!$validate->check($_POST)) {
            $this->error('您的输入有误,请重新登陆');
        }else{
            $name=$_POST["name"];
            $passwd=$_POST["password"];
            $User = db('user');
            $users = $User->field('namepy')->select();
            $array=array();
            foreach($users as $key=>$us){
            array_push($array,$users[$key]['namepy']);
        }
        if(in_array($name,$array)){
            /*if(self::ldapcheck($name,$passwd)){
                $userlogin=$User->where('namepy',$name)->select();
                $loginname=$userlogin['0'];
                session('loginname',$loginname);
                $this->redirect(url('Home/index'));
            }else{
                return $this->fetch('index');
            }*/
            $userlogin=$User->where('namepy',$name)->select();
            if($userlogin){
                $loginname=$userlogin['0'];
                session('loginname',$loginname);
                $this->redirect(url('Home/index'));
            }else{
                return $this->fetch('index');
            }
        }
        else{
            return $this->fetch('index');}
        }
    }


	private function ldapcheck($user,$pass){
        
    $ldapConnect = ldap_connect('172.16.240.4:389');
    $bind= @ldap_bind($ldapConnect,$user.'@jk.com',$pass);  
    if($bind )  
    {  
        return 1;
    }  
    else  
    {
        return 0;  
    }  
    ldap_close($ldapConnect); 
	}
   
    public function logout()
    {		
        if(session('loginname')) {session('loginname',null);}
        session('projectID',null);
		$this->redirect(url('Index/index'));
    }
   

}
