<?php
/**
 * 控制目标步骤管理 Model
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-11-14
 * Time: 15:03
 */
class GoalManageModel extends model{
    public $contactRule = array(
        array('name','require','步骤描述必填！', self::MUST_VALIDATE, 'regex'),

    );
}