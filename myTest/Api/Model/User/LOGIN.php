<?php

namespace Model\User;

use Core\core;

class LOGIN{
    public function doRegister($email, $password){
        $result = core::_loadClass('Core/db')
            ->table(['m' => 'member'])
//            ->join(['table'=>'order','as'=>'o','on'=>'o.id=m.id','type'=>'left'])
            ->field('member_id,email,password as ps')
            ->field(['nickname', 'ip'])
            ->where('email = ?')
            ->andWhere('is_del = ?')
            ->setParameter(1, '263526691@qq.com')
            ->setParameter(2, 0)
//            ->group('password')
//            ->group('email')
//            ->having('name != \'\'')
//            ->having('id != \'\'')
//            ->andHaving('name != \'\'')
//            ->orHaving(['nickname','pangji','!='])
            ->order('member_id desc')
//            ->order(['asc'=>'name as na'])
            ->limit(0,100)
            ->select('getAll');
        return $result;
    }
}