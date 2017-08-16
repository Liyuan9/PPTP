<?php
namespace app\index\validate;
use think\Validate;
class Search extends Validate
{
	protected $rule = [
        'needID'  =>  'number',
    ];
	protected $message = [
        'needID.number'  =>  '需求ID只能为一个数字',
    ];
}
?>