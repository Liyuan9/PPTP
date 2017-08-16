<?php
namespace app\index\controller;
use think\Controller;
class Work extends Controller
{
    public function index(){
        $_SESSION['projectID'] = '';
        return $this->fetch();
    }

    public function message(){
        $_SESSION['projectID'] = '';
        return $this->fetch();
    }
}