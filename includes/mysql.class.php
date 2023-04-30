<?php

class mysqlclass {

    public $cid = null;
    private $sql_host = "localhost";
    private $sql_user = "demo";
    private $sql_pass = "demo";
    private $sql_db = "demo";
    public $sql_prefix = "demo_";
    private $error_msg = '';
    private $is_debug = true;

    function __construct($mysqlhost=null, $mysqluser=null, $mysqlpass=null, $mysqldb=null, $dbprefix=null){
        $this->sql_host = $mysqlhost;
        $this->sql_user = $mysqluser;
        $this->sql_pass = $mysqlpass;
        $this->sql_db = $mysqldb;
        $this->sql_prefix = $dbprefix;
        try {
            $this->cid = new PDO("mysql:dbname=$this->sql_db;host=$this->sql_host;charset=utf8mb4", $this->sql_user, $this->sql_pass);
            $this->cid->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->cid->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }
    public function change_db($db, $prefix){
        $this->sql_db = $db;
        $this->sql_prefix = $db.'.'.$prefix;
    }
    public function prepare($prepare){
        try {
            $this->cid->exec("set names utf8mb4");
            return $this->cid->prepare($prepare);
        } catch (Exception $e) {
            if(!$this->is_debug){exit;}
            die("error in the query:$prepare<br>".$e);
        }
    }
    public function getData($table_name, $query_data = null, $select = '*', $order = null, $order_direct = null, $limit = 1){
        $list = $this->getList($table_name, $query_data, $select, $order, $order_direct, $limit);
        return (isset($list[0]) ? $list[0] : false);
    }

/**
 * new version from 2023-03-29
 * Sample query array: for $mysql->getList($table_name, $query_data);
 * $query_array = [
 *     "and" => [
 *         [ "name", "=", "John Doe" ], // Simple comparison
 *         [
 *             "or" => [
 *                 [ "age", ">", 30 ], // Simple comparison
 *                 [ "city", "IN", ["New York", "Los Angeles"] ], // IN condition
 *                 [
 *                     "and" => [
 *                         [ "country", "=", "USA" ], // Simple comparison
 *                         [ "state", "NOT IN", ["NY", "CA"] ], // NOT IN condition
 *                         [ "last_login", "IS NULL" ], // IS NULL condition
 *                         [ "created_at", "BETWEEN", ["2022-01-01", "2022-12-31"] ] // BETWEEN condition
 *                     ]
 *                 ]
 *             ]
 *         ]
 *     ]
 * ];
 */

    public function getList($table_name, $query_data = null, $select = '*', $order = null, $order_direct = null, $limit = null, $idOnly=FALSE){
        global $login;

        $order = preg_replace("/[^A-Za-z \.\_\,]/", '', $order);
        in_array(strtoupper($order_direct), array('ASC', 'DESC')) || !$order_direct?'':exit;
        
        $order_statment = "";
        $limit_statment = "";

        
        if($query_data !== null && in_array(strtoupper(key($query_data)), ['AND', 'OR'])){
            list($where_statment, $querys) = $this->recursion_conditions($query_data);
            $where_statment = ' WHERE ' . $where_statment;
        } else if(is_array($query_data)){
            unset($query_data['']);
            $where_statment = $this->getWhereStatment($query_data);
            $querys = $this->getQuerys($query_data);
        }
        
        if ($select != null) {
            if (is_string($select)) {
                $select_statment = $select;
            } else if (is_array($select)) {
                $select_statment = join(', ', $select);
            }
        }
        if ($limit != null) {
            if (is_string($limit) || is_numeric($limit)) {
                $limit_statment = $limit;
            } else if (is_array($limit)) {
                $limit_statment = join(', ', $limit);
            }
            $limit_statment = 'LIMIT ' . $limit_statment;
        }
        $order_statment = $this->order_statment($order, $order_direct);
        try {
            $stmt = $this->prepare("SELECT $select_statment FROM ".$this->sql_prefix."$table_name $where_statment $order_statment $limit_statment");

            if($querys){
                $stmt->execute($querys);
            } else {
                $stmt->execute();
            }
            if ($idOnly) {
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            } else {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
        return $result;
    }
    function get_param_key($field, $value, &$querys){
        $param_key = ':' . preg_replace('/[^a-zA-Z0-9]/', '_', $field) . '_' . count($querys);
        $querys[$param_key] = $value;
        return $param_key;
    }
    function recursion_conditions($conditions, $querys = []) {
        $sql_conditions = [];
        $op = key($conditions);
        $vs = $conditions[$op];
        foreach ($vs as $v) {
            if (in_array(strtoupper(key($v)), ['AND', 'OR'])) {
                list($sql_condition, $querys) = $this->recursion_conditions($v, $querys);
                $sql_conditions[] = $sql_condition;
            } else {
                $operator = strtoupper($v[1]);
                switch ($operator) {
                    case 'IS NULL':
                    case 'IS NOT NULL':
                        $sql_conditions[] = $v[0] . ' ' . $operator;
                        break;
                    case 'IN':
                    case 'NOT IN':
                        $placeholders = [];
                        foreach ($v[2] as $index => $value) {
                            $placeholders[] = $this->get_param_key($v[0], $value, $querys);
                        }
                        $sql_conditions[] = $v[0] . ' ' . $operator . ' (' . implode(', ', $placeholders) . ')';
                        break;
                    case 'BETWEEN':
                        $param_key1 = $this->get_param_key($v[0], $v[2][0], $querys);
                        $param_key2 = $this->get_param_key($v[0], $v[2][1], $querys);
                        $sql_conditions[] = $v[0] . ' ' . $operator . ' ' . $param_key1 . ' AND ' . $param_key2;
                        break;
                    default:
                        $param_key = $this->get_param_key($v[0], $v[2], $querys);
                        $sql_conditions[] = $v[0] . ' ' . $v[1] . ' ' . $param_key;
                        break;
                }
            }
        }
        return ['(' . implode(' ' . $op . ' ', $sql_conditions) . ')', $querys];
    }

    public function search($table_name, $search_data = null, $query_data = null, $select = '*', $order = null, $order_direct = null, $limit = null)    {

        $where_statment = $this->getSearchStatment($search_data, $query_data);
        if ($select != null) {
            if (is_string($select)) {
                $select_statment = $select;
            } else if (is_array($select)) {
                $select_statment = join(', ', $select);
            }
        }
        if ($limit != null) {
            if (is_string($limit) || is_numeric($limit)) {
                $limit_statment = $limit;
            } else if (is_array($limit)) {
                $limit_statment = join(', ', $limit);
            }
            $limit_statment = 'LIMIT ' . $limit_statment;
        }
        $order_statment = $this->order_statment($order, $order_direct);
        try {
            if($select != '*' && strpos($select, ',') == ''){
                $stmt = $this->prepare("SELECT GROUP_CONCAT($select) as $select FROM ".$this->sql_prefix."$table_name $where_statment $order_statment $limit_statment");
            } else {
                $stmt = $this->prepare("SELECT $select_statment FROM ".$this->sql_prefix."$table_name $where_statment $order_statment $limit_statment");
            }
            $querys = array();
            if ($query_data != null && is_array($query_data)) {
                $query_data_n = array();
                foreach ($query_data as $qk => $qv) {
                    $query_data_n['query_'.$qk] = $qv;
                }
                $querys = $this->getQuerys($query_data_n);
            }
            if ($search_data != null && is_array($search_data)) {
                $search_data_n = array();
                foreach ($search_data as $sk => $sv) {
                    $search_data_n['search_'.$sk] = $sv;
                }
                $querys = array_merge($querys, $this->getQuerys($search_data_n));
            }
            $stmt->execute($querys);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if($select != '*' && strpos($select, ',') == ''){
                if($result[0][$select]){
                    $result = explode(',', $result[0][$select]);
                } else {
                    $result = false;
                }
            }
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
        return $result;
    }
    function searchCount($table_name, $search_data = null, $query_data = null) {

        $where_statment = $this->getSearchStatment($search_data, $query_data);
        try {
            if($where_statment){
                $stmt = $this->prepare("SELECT count(*) AS rowCount FROM ".$this->sql_prefix."$table_name $where_statment");
                $querys = array();
                if ($query_data != null && is_array($query_data)) {
                    $query_data_n = array();
                    foreach ($query_data as $qk => $qv) {
                        $query_data_n['query_'.$qk] = $qv;
                    }
                    $querys = $this->getQuerys($query_data_n);
                }
                if ($search_data != null && is_array($search_data)) {
                    $search_data_n = array();
                    foreach ($search_data as $sk => $sv) {
                        $search_data_n['search_'.$sk] = $sv;
                    }
                    $querys = array_merge($querys, $this->getQuerys($search_data_n));
                }
                $stmt->execute($querys);
                $result = $stmt->fetchAll();
            } else {
                $stmt = $this->prepare("show table status");
                $stmt->execute();
                foreach ($stmt->fetchAll() as $table) {
                    if($table['Name'] == $this->sql_prefix.$table_name){
                        return $table['Rows'];
                    }
                }
            }
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
        return $result[0]['rowCount'];
    }

    function filter($table_name, $filter_data = null, $filter_type = null, $query_data = null, $select = '*', $order = null, $order_direct = null, $limit = null)    {

        $where_statment = $this->getFilterStatment($filter_data, $filter_type, $query_data);
        if ($select != null) {
            if (is_string($select)) {
                $select_statment = $select;
            } else if (is_array($select)) {
                $select_statment = join(', ', $select);
            }
        }
        if ($limit != null) {
            if (is_string($limit) || is_numeric($limit)) {
                $limit_statment = $limit;
            } else if (is_array($limit)) {
                $limit_statment = join(', ', $limit);
            }
            $limit_statment = 'LIMIT ' . $limit_statment;
        }
        $order_statment = $this->order_statment($order, $order_direct);
        
        try {
            $stmt = $this->prepare("SELECT $select_statment FROM ".$this->sql_prefix."$table_name $where_statment $order_statment $limit_statment");
            $querys = array();
            if ($query_data != null && is_array($query_data)) {
                foreach ($query_data as $key => $value) {
                    $key = str_replace('.', '__',$key);
                    $querys[":$key"] = $value;
                }
            }
            if ($filter_data != null && is_array($filter_data)) {
                foreach ($filter_data as $key => $value) {
                    if($filter_type[$key] == 'date'){
                        $querys[":$key".'_f'] = "$value 00:00:00";
                        $querys[":$key".'_t'] = "$value 23:59:59";
                    } else {
                        $querys[":$key"] = $value;
                    }
                }
            }
            $stmt->execute($querys);
            $result = $stmt->fetchAll();
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
        return $result;
    }
    function filterCount($table_name, $filter_data = null, $filter_type = null, $query_data = null) {

        $where_statment = $this->getFilterStatment($filter_data, $filter_type, $query_data);

        try {
            $stmt = $this->prepare("SELECT count(*) AS rowCount FROM ".$this->sql_prefix."$table_name $where_statment");
            if ($query_data != null) {
                foreach ($query_data as $key => $value) {
                    $stmt->bindParam(":$key", $value, PDO::PARAM_STR);
                }
            }
            if ($filter_data != null) {
                foreach ($filter_data as $key => $value) {
                    if($filter_type[$key] == 'date'){
                        $kv = "$value 00:00:00";
                        $stmt->bindParam(":$key".'_f', $kv, PDO::PARAM_STR);
                        $kv = "$value 23:59:59";
                        $stmt->bindParam(":$key".'_t', $kv, PDO::PARAM_STR);
                    } else {
                        $stmt->bindParam(":$key", $value, PDO::PARAM_STR);
                    }
                }
            }
            $stmt->execute();
            $result = $stmt->fetchAll();
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
        return $result[0]['rowCount'];
    }
    function getListJoinGroup($join_type, $table_name, $join_key, $query_data = null, $select = '*', $order = null, $order_direct = null, $limit = null, $idOnly=FALSE){

        if (is_string($table_name)) {
            $table_statment = $this->sql_prefix.$table_name;
        } else if (is_array($table_name)) {
            foreach ($table_name as $key => $table) {
                if(!$key){continue;}
                $range = range('a', 'z');
                $table_statment = $this->sql_prefix.$table_name[0]. " AS $range[0] $join_type ".$this->sql_prefix.$table . ' AS ' . $range[$key]. ' ON '.$range[0] . '.' . $join_key[0].'='.$range[$key] . '.' . $join_key[$key];
            }
        }
        $where_statment = '';
        $order_statment = '';
        $limit_statment = '';
        if ($query_data != null) {
            if (is_string($query_data)) {
                $where_statment = "WHERE " . $query_data;
            } else if (is_array($query_data)) {
                $wheres = array();
                foreach ($query_data as $k1 => $row) {
                    foreach ($row as $key => $value) {
                        $range = range('a', 'z');
                        $condition = $this->getCondition($key, $value, $range[$k1]);
                        $wheres[] = $range[$k1] . '.' . $condition;
                    }
                }
                if($wheres){
                    $where_statment = join(' AND ', $wheres);
                    $where_statment = "WHERE " . $where_statment;
                }
            }
        }
        if ($select != null) {
            if (is_string($select)) {
                $select_statment = $select;
            } else if (is_array($select)) {
                $selects = array();
                foreach ($select as $index => $sel) {
                    if (is_string($sel)) {
                        $range = range('a', 'z');
                        if(preg_match('/,/is', $sel)){
                            $selia = array();
                            foreach (explode(',', $sel) as $seli) {
                                $selia[] = $range[$index] . '.' . preg_replace('/^ /', '', $seli);
                            }
                            $selects[] = join(',', $selia);
                        } else {
                            $selects[] = $range[$index] . '.' . $sel;
                        }
                        
                    } else {
                        foreach ($sel as $value) {
                            $range = range('a', 'z');
                            $selects[] = $range[$index] . '.' . $value;
                        }
                    }
                }
                $select_statment = join(', ', $selects);
            }
        }
        if ($limit != null) {
            if (is_string($limit) || is_numeric($limit)) {
                $limit_statment = $limit;
            } else if (is_array($limit)) {
                $limit_statment = join(', ', $limit);
            }
            $limit_statment = 'LIMIT ' . $limit_statment;
        }
        if ($order != null) {
            if (is_string($order)) {
                //$order_statment = "`$order`";
                $order_statment = '`'.str_replace(',', '`, `', $order).'`';
                if ($order_direct != null) {
                    $order_statment .= ' ' . $order_direct;
                }
            } else if (is_array($order)) {
                $orders = array();
                foreach ($order as $index => $ord) {
                    if (is_string($ord)) {
                        if ($order_direct[$index] != null) {
                            $range = range('a', 'z');
                            $orders[] = $range[$index] . '.' . $ord . ' ' . $order_direct[$index];
                        } else {
                            $range = range('a', 'z');
                            $orders[] = $range[$index] . '.' . $ord;
                        }
                    } else {
                        foreach ($ord as $key => $value) {
                            if ($order_direct[$index][$key] != null) {
                                $range = range('a', 'z');
                                $orders[] = $range[$index] . '.' . $value . ' ' . $order_direct[$index][$key];
                            } else {
                                $range = range('a', 'z');
                                $orders[] = $range[$index] . '.' . $value;
                            }
                        }
                    }
                }
                $order_statment = join(', ', $orders);
            }
            $order_statment = 'ORDER BY ' . $order_statment;
        }
        try {
            $stmt = $this->prepare("SELECT $select_statment FROM $table_statment $where_statment GROUP BY $join_key[0] $order_statment $limit_statment");
            
            $querys = $this->getQuerys($query_data);

            if($querys){
                $stmt->execute($querys);
            } else {
                $stmt->execute();
            }
            if ($idOnly) {
                return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            } else {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
    }
    function getListJoin($join_type, $table_name, $join_key, $query_data = null, $select = '*', $order = null, $order_direct = null, $limit = null, $idOnly=FALSE)    {

        if (is_string($table_name)) {
            $table_statment = $this->sql_prefix.$table_name;
        } else if (is_array($table_name)) {
            foreach ($table_name as $key => $table) {
                if(!$key){continue;}
                $range = range('a', 'z');
                $table_statment = $this->sql_prefix.$table_name[0]. " AS $range[0] $join_type ".$this->sql_prefix.$table . ' AS ' . $range[$key]. ' ON '.$range[0] . '.' . $join_key[0].'='.$range[$key] . '.' . $join_key[$key];
            }
        }
        $where_statment = '';
        $order_statment = '';
        $limit_statment = '';
        if ($query_data != null) {
            if (is_string($query_data)) {
                $where_statment = "WHERE " . $query_data;
            } else if (is_array($query_data)) {
                $wheres = array();
                foreach ($query_data as $k1 => $row) {
                    foreach ($row as $key => $value) {
                        $range = range('a', 'z');
                        $condition = $this->getCondition($key, $value, $range[$k1]);
                        $wheres[] = $range[$k1] . '.' . $condition;
                    }
                }
                if($wheres){
                    $where_statment = join(' AND ', $wheres);
                    $where_statment = "WHERE " . $where_statment;
                }
            }
        }
        if ($select != null) {
            if (is_string($select)) {
                $select_statment = $select;
            } else if (is_array($select)) {
                $selects = array();
                foreach ($select as $index => $sel) {
                    if (is_string($sel)) {
                        $range = range('a', 'z');
                        if(preg_match('/,/is', $sel)){
                            $selia = array();
                            foreach (explode(',', $sel) as $seli) {
                                $selia[] = $range[$index] . '.' . preg_replace('/^ /', '', $seli);
                            }
                            $selects[] = join(',', $selia);
                        } else {
                            $selects[] = $range[$index] . '.' . $sel;
                        }
                        
                    } else {
                        foreach ($sel as $value) {
                            $range = range('a', 'z');
                            $selects[] = $range[$index] . '.' . $value;
                        }
                    }
                }
                $select_statment = join(', ', $selects);
            }
        }
        if ($limit != null) {
            if (is_string($limit) || is_numeric($limit)) {
                $limit_statment = $limit;
            } else if (is_array($limit)) {
                $limit_statment = join(', ', $limit);
            }
            $limit_statment = 'LIMIT ' . $limit_statment;
        }
        if ($order != null) {
            if (is_string($order)) {
                $order_statment = '`'.str_replace(',', '`, `', $order).'`';
                if ($order_direct != null) {
                    $order_statment .= ' ' . $order_direct;
                }
            } else if (is_array($order)) {
                $orders = array();
                foreach ($order as $index => $ord) {
                    if (is_string($ord)) {
                        if ($order_direct[$index] != null) {
                            $range = range('a', 'z');
                            $orders[] = $range[$index] . '.' . $ord . ' ' . $order_direct[$index];
                        } else {
                            $range = range('a', 'z');
                            $orders[] = $range[$index] . '.' . $ord;
                        }
                    } else {
                        foreach ($ord as $key => $value) {
                            if ($order_direct[$index][$key] != null) {
                                $range = range('a', 'z');
                                $orders[] = $range[$index] . '.' . $value . ' ' . $order_direct[$index][$key];
                            } else {
                                $range = range('a', 'z');
                                $orders[] = $range[$index] . '.' . $value;
                            }
                        }
                    }
                }
                $order_statment = join(', ', $orders);
            }
            $order_statment = 'ORDER BY ' . $order_statment;
        }
        try {
            $stmt = $this->prepare("SELECT $select_statment FROM $table_statment $where_statment $order_statment $limit_statment");
            
            $querys = $this->getQuerys($query_data);

            if($querys){
                $stmt->execute($querys);
            } else {
                $stmt->execute();
            }
            if ($idOnly) {
                return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            } else {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
    }
    function order_statment($order, $order_direct){
        if ($order != null) {
            if (is_string($order)) {
                $order_statment = '`'.str_replace(',', '`, `', $order).'`';
            } else if (is_array($order)) {
                $order_statment = join(', ', $order);
            }
            if ($order_direct != null) {
                $order_statment .= ' ' . $order_direct;
            }
            $order_statment = 'ORDER BY ' . $order_statment;
            return $order_statment;
        }
        return '';
    }
    function getCondition($key, $value, $prefix = false){
        $condition = '';
        if (strpos($key, '.') !== FALSE) {
            $key2 = str_replace('.', '__', $key);
            $condition = $prefix?"$key=:$prefix".'_'."$key2":"$key=:$key2";
        } else if(is_array($value)){
            $vs = array();
            foreach($value as $k => $v){
                $vs[] = $prefix?":$prefix".'_'."$key$k":":$key$k";
            }
            $condition = "`$key` IN(" .join(',', $vs). ")";
        } else {
            $condition_type = array(
                'between' => 'BETWEEN',
                'notnull' => 'IS NOT NULL',
                'notlike' => 'NOT LIKE',
                'null' => 'IS NULL',
                'like' => 'LIKE',
                '!=' => '!=',
                '>=' => '>=',
                '<=' => '<=',
                '>' => '>',
                '<' => '<',
                '=' => '='
            );
            foreach ($condition_type as $ctk => $ctv) {
                if(is_string($ctk) && substr($value, 0, strlen($ctk)) == $ctk) {
                    if($ctk == 'notnull' || $ctk == 'null'){
                        $condition = "`$key` $ctv ";
                    } else if($ctk == 'between'){
                        $condition = $prefix?"`$key` $ctv :$prefix".'_'."$key".'0 AND '.":$prefix".'_'."$key".'1':"`$key` $ctv :$key".'0 AND '.":$key".'1';
                    } else {
                        $condition = $prefix?"`$key` $ctv :$prefix".'_'."$key":"`$key` $ctv :$key";
                    }
                    break;
                }
            }
            $condition = $condition?$condition:($prefix?"`$key`=:$prefix".'_'."$key":"`$key`=:$key");
        }
        return $condition;
    }
    function getConditionValue($value){
        $condition_type = array(
            'between' => 'BETWEEN',
            'notnull' => 'IS NOT NULL',
            'notlike' => 'NOT LIKE',
            'null' => 'IS NULL',
            'like' => 'LIKE',
            '!=' => '!=',
            '>=' => '>=',
            '<=' => '<=',
            '>' => '>',
            '<' => '<',
            '=' => '='
        );
        foreach ($condition_type as $ctk => $ctv) {
            if(is_string($ctk) && substr($value, 0, strlen($ctk)) == $ctk) {
                if($ctk == 'notnull' || $ctk == 'null'){
                    return false;
                } else if($ctk == 'between'){
                    $v = substr($value, strlen($ctk), strlen($value)-strlen($ctk));
                    return explode(',', $v);
                } else {
                    return substr($value, strlen($ctk), strlen($value)-strlen($ctk));
                }
            }
        }
        return $value;
    }
    function getQuerys($query_data){
        if (is_array($query_data)) {
            $tmp = array_keys($query_data);
        }
        $querys = array();
        if ($query_data != null && is_array($query_data) && !is_numeric(reset($tmp))) {
            foreach ($query_data as $k1 => $row) {
                $k1 = str_replace('.', '__', $k1);
                if(is_array($row)){
                    $vs = array();
                    foreach($row as $k => $v){
                        $querys[":$k1$k"] = $v;
                    }
                } else {
                    if($this->getConditionValue($row) !== false){
                        $values = $this->getConditionValue($row);
                        if (is_array($values)) {
                            foreach ($values as $k0 => $v0) {
                                $querys[":$k1".$k0] = $v0;
                            }
                        } else {
                            $querys[":$k1"] = $values;
                        }
                    }
                }
            }
        } else if ($query_data != null && is_array($query_data)){
            $range = range('a', 'z');
            foreach ($query_data as $k1 => $row) {
                if (is_array($row)) {
                    foreach ($row as $key => $value) {
                        $key = str_replace('.', '__', $key);
                        if (is_array($value)) {
                            $vs = array();
                            foreach ($value as $k => $v) {
                                $querys[":".$range[$k1]."_$key$k"] = $v;
                            }
                        } else {
                            if($this->getConditionValue($value) !== false){
                                $values = $this->getConditionValue($value);
                                if (is_array($values)){
                                    foreach ($values as $k0 => $v0) {
                                        $querys[":".$range[$k1].$k0."_$key"] = $v0;
                                    }
                                    
                                } else {
                                    $querys[":".$range[$k1]."_$key"] = $this->getConditionValue($value);
                                }
                                
                            }
                        }
                    }
                }
            }
        }
        return $querys;
    }
    function rowCount($table_name, $query_data = null) {
        return $this->searchCount($table_name, null, $query_data);
    }
    function update($table_name, $query_data, $dynamic_data, $static_data = null)    {

        $where_statment = $this->getWhereStatment($query_data);
        $sqlUpdate = array();
        $sqlData = $this->getQuerys($query_data);
        $i = 0; // Prevent fields used twice
        foreach ($dynamic_data as $key => $value) {
            $i++;
            $sqlUpdate[] = "`$key`=:{$key}__{$i}";
            $sqlData[":{$key}__{$i}"] = $value;
        }
        if ($static_data != null) {
            foreach ($static_data as $key => $value) {
                $sqlUpdate[] = "`{$key}`=".$this->quote($value);
            }
        }
        $sqlUpdate = join(',', $sqlUpdate);
        try {
            $stmt = $this->prepare("UPDATE ".$this->sql_prefix."$table_name SET $sqlUpdate $where_statment;");
            $stmt->execute($sqlData);
            return true;
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            $this->error_msg = 'Sql error: ' . $e->getMessage();
            //echo $this->error_msg;
            return false;
        }
    }
    function create($table_name, $dynamic_data, $static_data = null){

        $sqlSet = array();
        $sqlKey = array();
        foreach ($dynamic_data as $key => $value) {
            $sqlSet[] = ":$key";
            $sqlKey[] = "`$key`";
            $sqlData[":$key"] = $value;
        }
        if ($static_data != null) {
            foreach ($static_data as $key => $value) {
                $sqlSet[] = $value;
                $sqlKey[] = "`$key`";
            }
        }
        $sqlSet = join(',', $sqlSet);
        $sqlKey = join(',', $sqlKey);
        try {
            $stmt = $this->prepare("INSERT INTO ".$this->sql_prefix."$table_name ($sqlKey) values ($sqlSet)");
            $stmt->execute($sqlData);
            return $this->lastInsertId();
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            $this->error_msg = 'Sql error: ' . $e->getMessage();
            return false;
        }
    }
    function lastInsertId() {
        return $this->cid->lastInsertId();
    }
    function delete($table_name, $query_data)    {

        $where_statment = $this->getWhereStatment($query_data);
        try {
            $stmt = $this->prepare("DELETE FROM ".$this->sql_prefix."$table_name $where_statment;");
            $stmt->execute($this->getQuerys($query_data));
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
    }
    function deleteSelected($table_name, $key, $datas)    {

        $questionmarks = str_repeat("?,", count($datas)-1) . "?";
        $where_statment = "WHERE `$key` IN ($questionmarks)";
        try {
            $stmt = $this->prepare("DELETE FROM ".$this->sql_prefix."$table_name $where_statment;");
            $stmt->execute($datas);
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
        
    }
    function getWhereStatment($query_data, $andor = 'AND')    {
        $where_statment = '';
        if ($query_data != null) {
            if (is_string($query_data)) {
                $where_statment = "WHERE " . $query_data;
            } else if (is_array($query_data)) {
                $tmp = array_keys($query_data);
                if ($query_data && is_numeric(reset($tmp))) {
                    $where_statment = join(" $andor ", $query_data);
                    $where_statment = "WHERE " . $where_statment;
                }
                else {
                    $wheres = array();
                    foreach ($query_data as $key => $value) {
                        $wheres[] = $this->getCondition($key, $value);
                    }
                    $where_statment = join(" $andor ", $wheres);
                    $where_statment = "WHERE " . $where_statment;
                }
            }
        }
        return $where_statment;
    }
    function getSearchStatment($search_data, $query_data)    {
        $search_statment = $where_statment = '';
        if($search_data != null){
            if (is_array($search_data)) {
                $wheres = array();
                foreach ($search_data as $key => $value) {
                    $wheres[] = $this->getCondition($key, $value, 'search');
                }
                $search_statment = join(" OR ", $wheres);
            }
        }
        if ($query_data != null) {
            if (is_array($query_data)) {
                $wheres = array();
                foreach ($query_data as $key => $value) {
                    $wheres[] = $this->getCondition($key, $value, 'query');
                }
                $where_statment = join(" AND ", $wheres);
            }
        }
        if($search_statment && !$where_statment){
            return "WHERE " . $search_statment;
        } else if(!$search_statment && $where_statment){
            return "WHERE " . $where_statment;
        } else if($search_statment && $where_statment){
            return "WHERE ($search_statment) AND ($where_statment)";
        }
    }
    function getFilterStatment($filter_data, $filter_type, $query_data)    {
        $search_statment = $where_statment = '';
        if($filter_data != null){
            if (is_array($filter_data)) {
                $wheres = array();
                foreach ($filter_data as $key => $value) {
                    if($filter_type[$key] == 'date'){
                        $wheres[] = "`$key`>=:$key".'_f'." AND `$key`<=:$key".'_t';
                    } else if($filter_type[$key] == 'select'){
                        $wheres[] = "`$key`=:$key";
                    }
                }
                $search_statment = join(" AND ", $wheres);
            }
        }
        if ($query_data != null) {
            if (is_array($query_data)) {
                $wheres = array();
                foreach ($query_data as $key => $value) {
                    $wheres[] = $this->getCondition($key, $value);
                }
                $where_statment = join(" AND ", $wheres);
            }
        }
        if($search_statment && !$where_statment){
            return "WHERE " . $search_statment;
        } else if(!$search_statment && $where_statment){
            return "WHERE " . $where_statment;
        } else if($search_statment && $where_statment){
            return "WHERE ($search_statment) AND ($where_statment)";
        }
    }
    function quote($value) {

        try {
            return $this->cid->quote($value);
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
    }
    public function getPrefix(){
        return $this->sql_prefix;
    }
    public function tableExists($table_name){
        try {
            $stmt = $this->cid->prepare("SELECT 1 FROM ".$this->sql_prefix."$table_name");
            $stmt->execute(); 
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    function getFields($table_name) {

        try {
            $stmt = $this->prepare("SHOW KEYS FROM ".$this->sql_prefix."$table_name");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
    }
    function getColumns($table_name) {

        try {
            $stmt = $this->prepare("SHOW COLUMNS FROM ".$this->sql_prefix."$table_name");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if(!$this->is_debug){exit;}
            echo 'Sql error: ' . $e->getMessage();
        }
    }
    public function columnExists($table_name, $field_name){
        $Columns = $this->getColumns($table_name);
        foreach ($Columns as $field) {
            if($field['Field'] == $field_name){
                return true;
            }
        }
        return false;
    }
    public function getErrorMsg(){
        return $this->error_msg;
    }
    private function isAssoc(array $arr){
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
?>