<?php
//初始化config
return [
    'PROJECT_NAME' => 'Api', //项目名称
    'CONFIG_DIR_NAME' => 'Config', //config目录名称
    'MODEL_ARRAY' => [
        'Control',
        'Business',
        'Model'
    ], //模块组
    'DEFAULT_MODEL'=> 'Control', //默认模块
    'UNIT_ARRAY' => [
        'Index',
        'User',
        'Product',
        'Order',
        'Cart',
    ], //单元组
    'DEFAULT_UNIT'=> 'Index', //默认单元
    'DEFAULT_CLASS'=> 'index', //默认类
    'DEFAULT_ACTION' => 'index', //默认方法
    'DEFAULT_CONFIG' => 'config', //默认配置
    'USE_SESSION' => true, //是否使用session
    'USE_CACHE' => true, //是否使用缓存
    'USE_UNIT_CONFIG' => true, //是否使用单元配置
    'CLASS_SUFFIX' => '.php', //默认文件后缀
    'DEBUG_OPEN' => true, //是否开启报错
    'USE_READ_DB' => true, //是否是用读写分离
];