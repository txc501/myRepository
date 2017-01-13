<?php
/* 入口文件 */

//根目录路径
define('ROOT_DIR', __DIR__ . '/');
//核心目录路径
define('CORE_DIR', ROOT_DIR . 'Core/');
//引入核心类
require_once CORE_DIR . 'core.php';
//框架初始化
\Core\core::coreInitialize();