PPTP
===============
PPTP项目主要是用来跟踪产品开发流程的一个系统，后端用的是thinkphp5框架，前端用的是bootstrap3框架，数据库是mysql，运行环境linux+appache+mysql
此系统主要包含模块如下
 + 需求模块
 + 缺陷模块
 + 迭代模块
 + 测试计划模块
 + 测试用例模块
 + 消息模块
 + 报表模块
 + 设置模块
 + 人员管理模块

## 目录结构

初始的目录结构如下：

~~~
www  WEB部署目录（或者子目录）
├─apps           应用目录
│  ├─common             公共模块目录（可以更改）
│  ├─index        模块目录
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  └─validate        验证规则目录
│  │
│  ├─command.php        命令行工具配置文件
│  ├─common.php         公共函数文件
│  ├─config.php         公共配置文件
│  ├─route.php          路由配置文件
│  ├─tags.php           应用行为扩展定义文件
│  └─database.php       数据库配置文件
|
├─public                WEB目录（对外访问目录）
│  ├─index.php          入口文件
│  ├─router.php         快速测试文件
│  └─.htaccess          用于apache的重写
|
├─thinkphp              框架系统目录
│  ├─lang               语言文件目录
│  ├─library            框架类库目录
│  │  ├─think           Think类库包目录
│  │  └─traits          系统Trait目录
│  │
│  ├─tpl                系统模板目录
│  ├─base.php           基础定义文件
│  ├─console.php        控制台入口文件
│  ├─convention.php     框架惯例配置文件
│  ├─helper.php         助手函数文件
│  ├─phpunit.xml        phpunit配置文件
│  └─start.php          框架入口文件
│
├─extend                扩展类库目录
├─runtime               应用的运行时目录
├─vendor                第三方类库目录（Composer依赖库）
|  ├─PHPExcel           excel导入插件
├─build.php             自动生成定义文件（参考）
├─composer.json         composer 定义文件
├─LICENSE.txt           授权说明文件
├─README.md             README 文件
├─think                 命令行入口文件
~~~


## 界面视图
* 首页进入展示消息提醒
![](/tapdimage/消息提醒页.png)

* 选择我的项目<br>
![](/tapdimage/我的项目.png)

* 进入项目后，针对项目显示需求列表
![](/tapdimage/需求列表.png)

* 点击列表中的需求进入需求详情
![](/tapdimage/需求详情.png)

* 选择需求创建
![](/tapdimage/需求创建.png)

* 点击图标进入迭代列表
![](/tapdimage/迭代列表.png)

* 查看迭代详情
![](/tapdimage/迭代详情.png)

* 将需要迭代的需求和缺陷规划到迭代
![](/tapdimage/迭代规划需求.png)

* 迭代创建页
![](/tapdimage/迭代页.png)

* 测试计划的规划与执行
![](/tapdimage/测试计划的规划与执行.png)

* 测试计划详情页
![](/tapdimage/测试计划详情.png)

* 缺陷列表页
![](/tapdimage/缺陷列表.png)

* 成员与权限
![](/tapdimage/成员与权限.png)

* 批量加入成员
![](/tapdimage/批量加入成员.png)

* 测试报告编写
![](/tapdimage/测试报告编写.png)


