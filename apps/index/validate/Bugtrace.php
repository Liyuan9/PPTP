<?php
/**
 * Created by PhpStorm.
 * User: lihuangyuan
 * Date: 17-6-9
 * Time: 上午10:02
 */
namespace app\index\validate;
use think\Validate;
class Bugtrace extends Validate
{
    protected $rule = [
        'projectID'  =>  'require',
        'bugName'  =>  'require|max:90',
        'dealby'  =>  'require',
        'step'  =>  'require',
        'serious'  =>  'require',
        'type'  =>  'require',
        'findversion'  =>  'require',
    ];
    protected $message = [
        'projectID.require'  =>  '项目ID不能为空',
        'bugName.require'  =>  '缺陷名称不能为空',
        'bugName.max'  =>  '缺陷名称最多不能超过30个字',
        'dealby.require'  =>  '缺陷处理人不能为空',
        'step.require'  =>  '重现步骤不能为空',
        'serious.require'  =>  '缺陷严重程度不能为空',
        'type.require'  =>  '缺陷类型不能为空',
        'findversion.require'  =>  '发现版本不能为空',
    ];
}
?>