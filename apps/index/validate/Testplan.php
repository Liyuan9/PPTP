<?php
namespace app\index\validate;
use think\Validate;
class Testplan extends Validate
{
	protected $rule = [
        'projectID'  =>  'require',
		//'startTime'  =>  'require',
		'responsible'  =>  'require',
		'status'  =>  'require',
		//'overTime'  =>  'require',
		'planName'  =>  'require|max:90',
    ];
	protected $message = [
        'projectID.require'  =>  '项目ID不能为空',
		'status.require'  =>  '状态不能为空',
		'responsible.require'  =>  '测试负责人不能为空',
		//'startTime.require'  =>  '开始时间不能为空',
		//'overTime.require'  =>  '结束时间不能为空',
		'planName.require'  =>  '计划名称不能为空',
		'planName.max'  =>  '计划名称最多不能超过30个字',
    ];
}
?>