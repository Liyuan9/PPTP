<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-6-2
 * Time: 上午10:12
 */
namespace app\index\validate;
use think\Validate;
class Need extends Validate
{
    protected $rule = [
        'needName'  =>  'require|max:90',
        'type' => 'require',
        'value' => 'require',
    ];
    protected $message = [
        'needName.require'  =>  '需求名称不能为空',
        'needName.max'  =>  '需求名称最多不能超过30个字',
        'type.require'  =>  '需求类型不能为空',
        'value.require'  =>  '业务价值不能为空',
    ];
}
?>