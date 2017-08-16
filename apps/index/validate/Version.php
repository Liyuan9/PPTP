<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-7-12
 * Time: 下午5:18
 */
namespace app\index\validate;
use think\Validate;
class Version extends Validate
{
    protected $rule = [
        'title'  =>  'require',
        'iterationID'  =>  'require|max:90',
        'depict'  =>  'require',
        'type'  =>  'require',
    ];
    protected $message = [
        'title.require'  =>  '版本名称不能为空',
        'iterationID.require'  =>  '迭代不能为空',
        'depict'  =>  '版本描述不能为空',
        'type.require'  =>  '测试类型不能为空',
    ];
}
?>