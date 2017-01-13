<?php
//公共方法

function checkEmail(){

}

function getIp(){

}

//获取过滤的$_GET/$_POST字段
function _getParameter($parameter, $default = null, $method = 'get'){
    $methodData = ($method == 'post') ? $_POST : $_GET;
    $parameterData = isset($methodData[$parameter]) ? $methodData[$parameter] : $default;
    return _checkParameter($parameterData);
}

//获取过滤的$_GET/$_POST数据
function _getAllParameter($method = 'get'){
    $getData = array();
    $methodData = ($method == 'post') ? $_POST : $_GET;
    foreach($methodData as $key => $value){
        $getData[$key] = _checkParameter($value);
    }
    return $getData;
}

//处理接收字段
function _checkParameter($parameter){
    if(is_array($parameter)){
        foreach($parameter as $key => $value){
            $parameter[$key] = _checkParameter($value);
        }
        return $parameter;
    }else{
        return addslashes(trim($parameter));
    }
}

