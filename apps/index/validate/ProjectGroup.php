<?php
namespace app\index\validate;
use think\Validate;
class ProjectGroup extends Validate
{
	protected $rule = [
        'projectID'  =>  'require',
    ];
	protected $message = [
        'projectID.require'  =>  '项目ID不能为空',
    ];
}
?>