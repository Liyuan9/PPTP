<?php

namespace app\index\controller;
use app\component\Admin;

class Action extends Admin
{
	//上传附件
   	public function uploads()
    {   
        if(!file_exists(LOAD_URL)){mkdir(LOAD_URL,0777,true);}           
        $success='';$fail='';$exist='';
        for($i=0;$i<count($_FILES["file-zh"]["name"]);$i++)
        {
            if ($_FILES["file-zh"]["size"][$i] < 15000000)
            {
                if ($_FILES["file-zh"]["error"][$i] > 0)
                {
                    echo "Return Code: " . $_FILES["file-zh"]["error"][$i] . "<br>";
					exit();
                }
                else
                {

                    if (file_exists(LOAD_URL.$_FILES["file-zh"]["name"][$i]))
                    {
                        $exist=$_FILES["file-zh"]["name"][$i] . "已经存在;";
						echo $exist;
						exit();                      
                    }
                }
            }
            else
            {   
                $fail=$_FILES["file-zh"]["name"][$i]."上传失败;";
				echo '<font color="red">'.$fail.'</font>';
				echo '<br>';
				exit();
            }
        }
		for($i=0;$i<count($_FILES["file-zh"]["name"]);$i++)
        {
            move_uploaded_file($_FILES["file-zh"]["tmp_name"][$i],LOAD_URL.$_FILES["file-zh"]["name"][$i]);
            $success=$success.$_FILES["file-zh"]["name"][$i].'上传成功;';
		}
        echo 1;	
    }


    //webuploader上传单个附件
    public function webuploader()
    {
        if(!empty($_FILES)){
            if($_FILES['file']['error']){
                exit(json_encode(array('status'=>0,'msg'=>$_FILES["file"]["error"])));
            }
            if(!file_exists(LOAD_URL)){
                if(!mkdir(LOAD_URL,0777,true)){
                    exit(json_encode(array('status'=>0,'msg'=>'文件上传目录创建失败')));
                }
            }
            if($_FILES['file']['size']>15728640){
                exit(json_encode(array('status'=>0,'msg'=>$_FILES["file"]["name"].'文件大小不能超过15M')));
            }
            if($_POST['ta']){
                if (file_exists(LOAD_URL.$_POST['ta'].'/'.$_FILES["file"]["name"])){
                    exit(json_encode(array('status'=>0,'msg'=>$_FILES["file"]["name"].'已存在，请换个文件名试试')));
                }
                $info = move_uploaded_file($_FILES["file"]["tmp_name"], LOAD_URL.$_FILES["file"]["name"]);
               $upfile['path'] = UPLOAD_URL;
            }else{
                if (file_exists(LOAD_URL.$_FILES["file"]["name"])){
                    exit(json_encode(array('status'=>0,'msg'=>$_FILES["file"]["name"].'已存在，请换个文件名试试')));
                }
                $info = move_uploaded_file($_FILES["file"]["tmp_name"], LOAD_URL.$_FILES["file"]["name"]);
                $upfile['path'] = UPLOAD_URL;
            }
            if($info){
                $upfile['title'] = $_FILES['file']['name'];
                $upfile['upby'] = session('loginname.dealname');
                if($_FILES['file']['size']<1024){
                    $size ='('. $_FILES['file']['size'].'B)';
                }elseif($_FILES['file']['size']>=1024 && $_FILES['file']['size']<1048576){
                    $size ='('.round($_FILES['file']['size']/1024,2).'kB)';
                }else{
                    $size ='('. round($_FILES['file']['size']/1048576,2).'M)';
                }
                $upfile['size'] = $size;
                $date = date('Y年n月d日 ag:i:s',time());
                $a = date('a',time());
                if($a == 'am'){
                    $upfile['uptime'] = str_replace('am','上午',$date);
                }else{
                    $upfile['uptime'] = str_replace('pm','下午',$date);
                }
                exit(json_encode(array('status'=>1,'msg'=>'','data'=>$upfile)));
            }else{
                exit(json_encode(array('status'=>0,'msg'=>$_FILES["file"]["name"].'上传出错！')));
            }
        }else{
            exit(json_encode(array('status'=>0,'msg'=>'请选择文件')));
        }
    }
}
