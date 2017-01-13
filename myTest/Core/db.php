<?php
//数据库类

namespace Core;

class db{
    private $hostLink;
    private $readLink;
    private $config;
    private $sqlOptions;
    private $sql;
    private $lastSql;
    private $parameters;
    private $paramTypes;
    private $error;
    private $lastInsertId;
    private $prefix;
    private $tableFields;

    public function __construct(){
        return $this->_connect();
    }

    private function __clone(){}

    private function _connect(){
        if(!class_exists('PDO')){
            $this->_errorMsg('PDO is not exists');
        }
        if($this->hostLink == null){
            if(empty($this->config['dbHost'])){
                $this->config['dbHost'] = config::_getConfig('Config/config', 'dbHost');
            }
            if(empty($this->config['dbHost'])){
                $this->_errorMsg('dbHost config is not exists');
            }
            $this->prefix = $this->config['dbHost']['prefix'];
            if(empty($this->config['dbHost']['dsn'])) $this->config['dbHost']['dsn'] = $this->config['dbHost']['dbtype'].':host='.$this->config['dbHost']['host'].';dbname='.$this->config['dbHost']['database'].';port='.$this->config['dbHost']['port'];
            try{
                $this->hostLink = new \PDO($this->config['dbHost']['dsn'], $this->config['dbHost']['user'], $this->config['dbHost']['password']);
            }catch(\PDOException $e){
                $this->_errorMsg($e->getMessage());
            }
            if(!$this->hostLink){
                $this->_errorMsg('dbHost connect error');
            }
            $this->hostLink->exec('SET NAMES '.$this->config['dbHost']['charset']);
        }
        if(true == USE_READ_DB){
            if($this->readLink == null){
                if(empty($this->config['dbRead'])){
                    $this->config['dbRead'] = config::_getConfig('Config/config', 'dbRead');
                }
                if(empty($this->config['dbRead'])){
                    $this->_errorMsg('dbRead config is not exists');
                }
                $this->prefix = $this->config['dbRead']['prefix'];
                if(empty($this->config['dbRead']['dsn'])) $this->config['dbRead']['dsn'] = $this->config['dbRead']['dbtype'].':host='.$this->config['dbRead']['host'].';dbname='.$this->config['dbRead']['database'].';port='.$this->config['dbRead']['port'];
                try{
                    $this->readLink = new \PDO($this->config['dbRead']['dsn'], $this->config['dbRead']['user'], $this->config['dbRead']['password']);
                }catch(\PDOException $e){
                    $this->_errorMsg($e->getMessage());
                }
                if(!$this->readLink){
                    $this->_errorMsg('dbRead connect error');
                }
                $this->readLink->exec('SET NAMES '.$this->config['dbRead']['charset']);
            }
        }
        return $this;
    }

    public function execSql($sql){
        $result = $this->hostLink->exec($sql);
        return $result;
    }

    public function select($type = 'getAll', $master = false){
        if(empty($this->sqlOptions)){
            $this->_errorMsg('The sql cannot be empty');
        }
        $this->_buildSelectSql();
        if(empty($this->sql)){
            $this->_errorMsg('The sql error');
        }
        if($master) $this->sql = '/*master*/' . $this->sql;
        $result = $this->_doSql('read');
        switch(strtolower($type)){
            case 'getall':
                $return = $result->fetchAll(constant('PDO::FETCH_ASSOC'));
                break;
            case 'getrow':
                $return = $result->fetch(constant('PDO::FETCH_ASSOC'), constant('PDO::FETCH_ORI_NEXT'));
                break;
            case 'getcol':
                $return = $result->fetchAll(constant('PDO::FETCH_COLUMN'));
                break;
            case 'getone':
                $return = $result->fetch(constant('PDO::FETCH_COLUMN'), constant('PDO::FETCH_ORI_NEXT'));
                break;
            default:
                $return = $result->fetchAll(constant('PDO::FETCH_ASSOC'));
        }
        return $return;
    }

    public function add(){

    }

    public function update(){

    }

    public function delete(){

    }

    public function table($table){
        if(empty($table)) return false;
        if(is_string($table)){
            $table = explode(',', $table);
        }
        if(is_array($table)){
            foreach($table as $key => $value){
                $value = trim($value);
                if(empty($value)){
                    unset($table[$key]);
                    break;
                }
                $table[$key] = $this->prefix . $value;
                $tableName = $table[$key];
                if(!is_numeric($key)){
                    $table[$key] .= ' as ' . $key;
                    $tableName = $key;
                }
                $this->tableFields[$tableName] = $this->_getFields($table[$key]);
            }
            if(!empty($table)) $sqlTable = implode(',', $table);
        }
        if(empty($sqlTable)) return false;
        $this->sqlOptions['table'][] = $sqlTable;
        return $this;
    }

    public function field($fields = '*'){
        if(is_string($fields)) $fields = explode(',', $fields);
        if(is_array($fields)){
            foreach($fields as $key => $value){
                $value = trim($value);
                if($value == '*'){
                    $sqlFields = $value;
                    break;
                }
                if(empty($value)){
                    unset($fields[$key]);
                    break;
                }
                $this->_checkFields($value);
                if(!is_numeric($key)) $value .= ' as ' . $key;
                $fields[$key] = $value;
            }
            if(!empty($fields)) $sqlFields = implode(',', $fields);
        }
        $sqlFields = !empty($sqlFields) ? $sqlFields : '*';
        $this->sqlOptions['field'][] = $sqlFields;
        return $this;
    }

    public function where($where = ''){
        if(is_string($where)) $sqlWhere = $where;
        if(is_array($where)){
            $sqlWhere = '';
            if(!empty($where[0]) || !empty($where[1])){
                if(empty($where[2])) $where[2] = '=';
                if($where[2] == 'in' || $where[2] == 'not in'){
                    if(is_array($where[1])) $where[1] = implode(',', $where[1]);
                    $sqlWhere .= '\'' . $where[0] . '\' ' . $where[2] . ' (\'' . $where[1] . '\')';
                }else if($where[2] == 'between' || $where[2] == 'not between'){
                    $sqlWhere .= $where[2] . ' \'' . $where[0] . '\' and \'' . $where[1] . '\'';
                }else{
                    $sqlWhere .= '\'' . $where[0] . '\' ' . $where[2] . ' \'' . $where[1] . '\'';
                }
            }
        }
        if(!empty($sqlWhere)) $this->sqlOptions['where'] = $sqlWhere;
        return $this;
    }

    public function andWhere($where = '', $type = 'and'){
        if(is_string($where)) $sqlWhere = $where;
        if(is_array($where)){
            $sqlWhere = '';
            if(!empty($where[0]) || !empty($where[1])){
                if(empty($where[2])) $where[2] = '=';
                if($where[2] == 'in' || $where[2] == 'not in'){
                    if(is_array($where[1])) $where[1] = implode(',', $where[1]);
                    $sqlWhere .= '\'' . $where[0] . '\' ' . $where[2] . ' (\'' . $where[1] . '\')';
                }else if($where[2] == 'between' || $where[2] == 'not between'){
                    $sqlWhere .= $where[2] . ' \'' . $where[0] . '\' and \'' . $where[1] . '\'';
                }else{
                    $sqlWhere .= '\'' . $where[0] . '\' ' . $where[2] . ' \'' . $where[1] . '\'';
                }
            }
        }
        if(!empty($sqlWhere)) $this->sqlOptions['andWhere'][] = $type . ' ' . $sqlWhere;
        return $this;
    }

    public function orWhere($where = ''){
        $this->andWhere($where, 'or');
        return $this;
    }

    public function order($order = ''){
        if(is_string($order)) $sqlOrder = $order;
        if(is_array($order)){
            foreach($order as $key => $value){
                if(empty($value)){
                    unset($order[$key]);
                    break;
                }
                if(!is_numeric($key) && in_array($key, ['asc', 'desc'])) $order[$key] .= ' ' . $key;
            }
            $sqlOrder = implode(',', $order);
        }
        if(!empty($sqlOrder)) $this->sqlOptions['order'][] = $sqlOrder;
        return $this;
    }

    public function group($group = ''){
        if(is_string($group)) $sqlGroup = $group;
        if(is_array($group)){
            foreach($group as $key => $value){
                if(empty($value)) unset($group[$key]);
            }
            $sqlGroup = implode(',', $group);
        }
        if(!empty($sqlGroup)) $this->sqlOptions['group'][] = $sqlGroup;
        return $this;
    }

    public function having($having = ''){
        if(is_string($having)) $sqlHaving = $having;
        if(is_array($having)){
            $sqlHaving = '';
            if(!empty($having[0]) || !empty($having[1])){
                if(empty($having[2])) $having[2] = '=';
                if($having[2] == 'in' || $having[2] == 'not in'){
                    if(is_array($having[1])) $having[1] = implode(',', $having[1]);
                    $sqlHaving .= '\'' . $having[0] . '\' ' . $having[2] . ' (\'' . $having[1] . '\')';
                }else if($having[2] == 'between' || $having[2] == 'not between'){
                    $sqlHaving .= $having[2] . ' \'' . $having[0] . '\' and \'' . $having[1] . '\'';
                }else{
                    $sqlHaving .= '\'' . $having[0] . '\' ' . $having[2] . ' \'' . $having[1] . '\'';
                }
            }
        }
        if(!empty($sqlHaving)) $this->sqlOptions['having'] = $sqlHaving;
        return $this;
    }

    public function andHaving($having = '', $type = 'and'){
        if(is_string($having)) $sqlHaving = $having;
        if(is_array($having)){
            $sqlHaving = '';
            if(!empty($having[0]) || !empty($having[1])){
                if(empty($having[2])) $having[2] = '=';
                if($having[2] == 'in' || $having[2] == 'not in'){
                    if(is_array($having[1])) $having[1] = implode(',', $having[1]);
                    $sqlHaving .= '\'' . $having[0] . '\' ' . $having[2] . ' (\'' . $having[1] . '\')';
                }else if($having[2] == 'between' || $having[2] == 'not between'){
                    $sqlHaving .= $having[2] . ' \'' . $having[0] . '\' and \'' . $having[1] . '\'';
                }else{
                    $sqlHaving .= '\'' . $having[0] . '\' ' . $having[2] . ' \'' . $having[1] . '\'';
                }
            }
        }
        if(!empty($sqlHaving)) $this->sqlOptions['andHaving'][] = $type . ' ' . $sqlHaving;
        return $this;
    }

    public function orHaving($having = ''){
        $this->andHaving($having, 'or');
        return $this;
    }

    public function limit($limit = '', $second = ''){
        if(is_string($limit) || is_int($limit)){
            $sqlLimit = $limit;
            if(is_numeric($second) && $second >= 0 && !stripos($sqlLimit, ',')) $sqlLimit .= ', ' . $second;
        }
        if(!empty($sqlLimit)) $this->sqlOptions['limit'] = $sqlLimit;
        return $this;
    }

    public function join($join = ''){
        $joinType = ['inner', 'left', 'right', 'full'];
        if(is_string($join)){
            //最好做正则匹配
            $sqlJoin = $join;
        }
        if(is_array($join)){
            if(!empty($join['table']) && !empty($join['on']) && in_array($join['type'], $joinType)){
                $sqlJoin = $join['type'] . ' join';
                $join['table'] = $this->prefix . $join['table'];
                $tableName = $join['table'];
                $sqlJoin .= ' ' . $join['table'];
                if(!empty($join['as'])){
                    $sqlJoin .= ' as ' . $join['as'];
                    $tableName = $join['as'];
                }
                $sqlJoin .= ' on ' . $join['on'];
                $this->tableFields[$tableName] = $this->_getFields($join['table']);
            }
        }
        if(!empty($sqlJoin)) $this->sqlOptions['join'][] = $sqlJoin;
        return $this;
    }

    public function setParameter($key, $value, $type = null){
        if(null !== $type) $this->paramTypes[$key] = $type;
        $this->parameters[$key] = $value;
        return $this;
    }

    private function _buildSelectSql(){
        $selectSql = 'select';
        $field = (empty($this->sqlOptions['field']) || in_array('*', $this->sqlOptions['field'])) ? '*' : implode(',', array_unique($this->sqlOptions['field']));
        $selectSql .= ' ' . $field;
        $table = implode(',', array_unique($this->sqlOptions['table']));
        if(empty($table)) return false;
        $selectSql .= ' from ' . $table;
        if(!empty($this->sqlOptions['join'])){
            $join = implode(' ', array_unique($this->sqlOptions['join']));
            $selectSql .= ' ' . $join;
        }
        if(!empty($this->sqlOptions['where'])){
            $where[] = $this->sqlOptions['where'];
            if(!empty($this->sqlOptions['andWhere'])){
                $where = array_merge($where, $this->sqlOptions['andWhere']);
            }
            $where = implode(' ', array_unique($where));
            $selectSql .= ' where ' . $where;
        }
        if(!empty($this->sqlOptions['group'])){
            $group = implode(',', array_unique($this->sqlOptions['group']));
            $selectSql .= ' group by ' . $group;
        }
        if(!empty($this->sqlOptions['having'])){
            $having[] = $this->sqlOptions['having'];
            if(!empty($this->sqlOptions['andHaving'])){
                $having = array_merge($having, $this->sqlOptions['andHaving']);
            }
            $having = implode(' ', array_unique($having));
            $selectSql .= ' having ' . $having;
        }
        if(!empty($this->sqlOptions['order'])){
            $order = implode(',', array_unique($this->sqlOptions['order']));
            $selectSql .= ' order by ' . $order;
        }
        if(!empty($this->sqlOptions['limit'])){
            $limit = $this->sqlOptions['limit'];
            $selectSql .= ' limit ' . $limit;
        }
        $this->sql = $selectSql;
        return $selectSql;
    }

    private function _doSql($type = 'host'){
        if(empty($this->sql)){
            $this->_errorMsg('The sql error');
        }
        $result = null;
        switch($type){
            case 'host':
                $result = $this->hostLink->prepare($this->sql);
                break;
            case 'read':
                $result = $this->readLink->prepare($this->sql);
                break;
            default:
                $result = $this->hostLink->prepare($this->sql);
        }
        if(!empty($this->parameters)){
            foreach($this->parameters as $key => $value){
                if(!empty($this->paramTypes[$key])){
                    $result->bindValue($key, $this->parameters[$key], $this->paramTypes[$key]);
                }else{
                    $result->bindValue($key, $this->parameters[$key]);
                }
            }
        }
        $res = $result->execute();
        if(!$res){
            $error_arr = $result->errorInfo();
            $message = implode('|', $error_arr);
            $this->_errorMsg($message);
        }
        $this->lastSql = $result->queryString;
        $this->sql = '';
        return $result;
    }

    private function _errorMsg($error){
        $this->error = $error;
        throw new \Exception('MySQL Error: ' . $this->error);
    }

    private function getPDOError()
    {
        if($this->hostLink->errorCode() != '00000'){
            $error=$this->hostLink->errorInfo();
            $this->_errorMsg($error[2]);
        }
    }

    private function _beginTransaction(){
        $this->hostLink->beginTransaction();
    }

    private function _commit(){
        $this->hostLink->commit();
    }

    private function _rollback(){
        $this->hostLink->rollback();
    }

    public function getTableEngine(){
        $engine = [];
        if(!empty($this->sqlOptions['table']) && !empty($this->config['dbHost']['database'])){
            foreach($this->sqlOptions['table'] as $key => $value){
                $sql = 'SHOW TABLE STATUS FROM ' . $this->config['dbHost']['database'] . ' WHERE NAME = \'' . $value . '\'';
                $tableInfo = $this->execSql($sql);
                $this->getPDOError();
                $engine[$value] = $tableInfo[0]['Engine'];
            }
        }
        return $engine;
    }

    public function getInsertId(){
        return $this->lastInsertId;
    }

    private function _getFields($table){
        $fields = [];
        $resource = $this->execSql('SHOW COLUMNS FROM ' . $table);
        $this->getPDOError();
        $resource->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $resource->fetchAll();
        foreach($result as $key => $value){
            $fields[] = $value['Field'];
        }
        return $fields;
    }

    private function _checkFields($field){
        if(strpos('.', $field)){
            $fieldArr = explode('.', $field);
            if(isset($this->tableFields[$fieldArr[0]]) && !in_array($fieldArr[1], $this->tableFields[$fieldArr[0]])){
                $this->_errorMsg('Unknown column ' . $field . ' in field list.');
            }
        }else{
            if(!empty($this->tableFields)){
                $atList = false;
                foreach($this->tableFields as $key => $value){
                    if(in_array($field, $value)){
                        $atList = true;
                        break;
                    }
                }
                if($atList == false) $this->_errorMsg('Unknown column ' . $field . ' in field list.');
            }
        }
    }
}