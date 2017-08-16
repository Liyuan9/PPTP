<?php
namespace app\index\validate;
use think\Validate;
class Project extends Validate
{
	protected $rule = [
        'projectName'  =>  'require|max:90',
    ];
	protected $message = [
        'projectName.require'  =>  '项目名不能为空',
        'projectName.max'  =>  '项目名称最多不能超过30个字',
    ];
}
?>