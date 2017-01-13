<?php
/* 核心类 */

namespace Core;

class core{
    //已读取的对象组
    private static $loadedClasses = [];
    //类路径映射组
    private static $classMap = [];
    //模块组
    private static $modelMap = [];
    //单元组
    private static $unitMap = [];

    //静态类不可实例化
    private function __construct(){}

    //框架初始化
    public static function coreInitialize(){
        //载入并执行初始化
        require_once CORE_DIR . 'config.php';
        config::_frameInitialize();
        //载入模块组
        if(empty(self::$modelMap)) self::$modelMap = config::_getModelList();
        //载入单元组
        if(empty(self::$unitMap)) self::$unitMap = config::_getUnitList();
        //载入类映射
        if(empty(self::$classMap)) self::$classMap = config::_getConfig('Config/classMap');
        //自动加载类
        self::_implementAction();
    }

     //自动加载类方法
     public static function _loadClass($className){
         //读取类映射
         if(isset(self::$classMap[$className])){
             $classPath = self::$classMap[$className];
         }
         $classNameArr = explode('/', $className);
         //未填MODEL，默认DEFAULT_MODEL
         if(in_array($classNameArr[0], self::$unitMap)){
             $classPath = DEFAULT_MODEL . '/' . $className;
         }
         //指定MODEL
         if(in_array($classNameArr[0], self::$modelMap)){
             $classPath = $className;
         }
         //指定核心库类
         if(in_array($classNameArr[0], ['Core'])){
             $classPath = $className;
         }
         if(!isset($classPath)){
             //搜索核心库
             $fullClassPath = self::_searchClass($className, CORE_DIR);
             if(!$fullClassPath){
                 //搜索模块
                 $fullClassPath = self::_searchClass($className);
             }
             //从完整路径取得类路径
             $classPath = self::_getClassPathFromFull($fullClassPath);
         }
         if(empty($classPath)) return null;
         //如果已有直接返回
         if(isset(self::$loadedClasses[$classPath])) return self::$loadedClasses[$classPath];
         $classPathArr = explode('/', $classPath);
         $startDir = PROJECT_DIR;
         if(in_array($classPathArr[0], ['Core'])) $startDir = ROOT_DIR;
         $filePath = $startDir . $classPath . CLASS_SUFFIX;
         if(file_exists($filePath)){
             //实例化类并保存
             require_once $filePath;
             $class = '\\' . str_replace('/', '\\', $classPath);
             self::$loadedClasses[$classPath] = new $class();
             return self::$loadedClasses[$classPath];
         }
         return null;
     }

    //搜索类方法
    protected static function _searchClass($className, $searchFrom = PROJECT_DIR){
        if(substr($searchFrom, -1, 1) != '/' && substr($searchFrom, -1, 1) != '\\') $searchFrom .= '/';
        $classPath = $searchFrom . $className . CLASS_SUFFIX;
        if(file_exists($classPath)){
            return $classPath;
        }
        $dirRes = opendir($searchFrom);
        while(($dir = readdir($dirRes)) != false){
            if($dir == '.' || $dir == '..'){
                continue;
            }
            if(is_dir($searchFrom . $dir)){
                $return = self::_searchClass($className, $searchFrom . $dir);
                if($return) return $return;
            }
        }
        return false;
    }

    //从完整路径截取映射路径
    private static function _getClassPathFromFull($fullClassPath){
        $fullClassPath = str_replace('\\', '/', $fullClassPath);
        $pathArr = explode('/', $fullClassPath);
        $classPathArr = [];
        foreach($pathArr as $key => $value){
            if(in_array($value, ['Core'], true) || in_array($value, self::$modelMap, true)){
                $classPathArr = array_slice($pathArr, $key);
                break;
            }
        }
        $classPath = '';
        if(!empty($classPathArr)) $classPath = implode('/', $classPathArr);
        $classPath = str_replace([CLASS_SUFFIX], [''], $classPath);
        return $classPath;
    }

    //单入口自动加载类
    private static function _implementAction(){
        $model = _getParameter('model', DEFAULT_MODEL);
        $action = _getParameter('action', DEFAULT_ACTION);
        $model = str_replace('.', '/', $model);
        self::_loadClass($model)->$action();
        exit;
    }
}
spl_autoload_register('Core\core::_loadClass');

