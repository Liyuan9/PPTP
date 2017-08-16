<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 应用入口文件 ]
date_default_timezone_set("ETC/GMT-8");
$head_time=date("Y-m-d",time());
$head_dir=dirname(__FILE__);
define('BIND_MODULE','index');
define("SITE_URL","http://".$_SERVER['HTTP_HOST']);
define("IND_URL",SITE_URL."/static/");
define("LOAD_URL",$head_dir."/upload/".$head_time."/");
define("UPLOAD_URL",SITE_URL."/upload/".$head_time."/");
// 定义应用目录
define('APP_PATH', __DIR__ . '/../apps/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
