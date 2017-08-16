<?php
namespace app\index\validate;
use think\Validate;
class Cases extends Validate
{
	protected $rule = [
        'projectID'  =>  'require',
		'casesName'  =>  'require|max:90',
		'grade'  =>  'require',
		'status'  =>  'require',
		'type'  =>  'require',
    ];
	protected $message = [
        'projectID.require'  =>  '项目ID不能为空',
		'casesName.require'  =>  '用例名称不能为空',
		'casesName.max'  =>  '用例名称最多不能超过30个字',
		'grade.require'  =>  '用例等级不能为空',
		'status.require'  =>  '用例状态不能为空',

    ];
}
?>