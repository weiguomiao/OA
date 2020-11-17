<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class ApplySetup extends Model
{
    protected $json = ['step'];
    protected $jsonAssoc = true;
}
