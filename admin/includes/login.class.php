<?php

class loginModule {
    public $sessionKey = 'usersession_';
    public $proname = 'cms';
    public $tableName = 'admin';
    public $loginIndex = 'login_name';
    public $passwordIndex = 'password';
    public $lastlogintimeIndex = 'lastlogintime';
    private $sessionInfo = array(
        'admin_id',
        'status',
        'firstname',
        'lastname',
        'admin_group_id',
        'email'
    );
    public $passEncrypt = true;
    public $errorMessage = array(
        'missLoginName' => 'please provide username',
        'missPassword' => 'please provide password',
        'invalidPassword' => 'invaild username and password',
        'inactive' => 'inActive user',
        'invalidToomuch' => 'Maximum invalid login attempts in day'
    );
    public $logoutTag = 0;
    public $permission = null;
    public $inputName = null; // 添加了变量定义
    public $inputPass = null; // 添加了变量定义
    public $remember_me = false; // 添加了变量定义
    public $mysqlHandle = false;

    function __construct(){}
    public function check($inname, $inpass, $incheckbox){
        if ($this->mysqlHandle == false) die ('mysql handle not found');
        global $encryptKey;

        $this->inputName = $inname;
        $this->inputPass = $inpass;
        $this->remember_me = $incheckbox;

        if (isset($_COOKIE['login_session']) && $_COOKIE['login_session'] && !$this->inputName && !$this->inputPass) {
            $userInfo = $this->getUserInfoBySessionKey();
        } else {
            $userInfo = $this->getUserInfo();
        }

        $flag = false;

        
        if(strtotime($userInfo[$this->lastlogintimeIndex])+60*60*24 < time()){
            $userInfo['salt']=0;
        }
        if($userInfo['salt']>20){
            $this->loginHtml($this->errorMessage['invalidToomuch']);
        }
        if ($this->inputName == false && (!isset($_SESSION[$this->sessionKey . $this->sessionInfo[0]]) || $_SESSION[$this->sessionKey . $this->sessionInfo[0]] == false)) {
            $this->loginHtml($this->errorMessage['missLoginName']);
        } else if ($this->inputPass == false) {
            if ($_SESSION[$this->sessionKey . $this->sessionInfo[0]] == true) {
                $flag = true;
            } else {
                $this->loginHtml($this->errorMessage['missPassword']);
            }
        }
        if ($flag == false) {
            if ($this->inputPass != $userInfo[$this->passwordIndex] && $this->passEncrypt == false) {
                $userInfo['salt']++;
                $this->mysqlHandle->update($this->tableName, array($this->loginIndex=>$this->inputName), array('salt' => $userInfo['salt'], $this->lastlogintimeIndex => date('Y-m-d H:i:s')));
                $this->loginHtml($this->errorMessage['invalidPassword']);
            } elseif (md5($this->inputPass . $encryptKey) != $userInfo[$this->passwordIndex] && $this->passEncrypt == true) {
                $userInfo['salt']++;
                $this->mysqlHandle->update($this->tableName, array($this->loginIndex=>$this->inputName), array('salt' => $userInfo['salt'], $this->lastlogintimeIndex => date('Y-m-d H:i:s')));
                $this->loginHtml($this->errorMessage['invalidPassword']);
            }
            if ($userInfo['status'] == 0) {
                $this->loginHtml($this->errorMessage['inactive']);
            }
        }
        if ($this->logoutTag) {
            log_admin_activity('logout');
            $this->logOut();
            $this->loginHtml('');
        } else {
            $this->setSession($userInfo);
            if(isset($_SESSION['redirect_url']) && false){
                $redirect_url = $_SESSION['redirect_url'];
                $_SESSION['redirect_url'] = '';
                unset($_SESSION['redirect_url']);
                //header('location: '.$redirect_url);
                ?><script type="text/javascript">window.location='<?php echo $redirect_url; ?>';</script><?php
                exit();
            }
        }
    }

    public function setSession($userInfo){   
        if ($this->inputName && (!isset($_SESSION[$this->sessionKey . $this->sessionInfo[0]]) || !$_SESSION[$this->sessionKey . $this->sessionInfo[0]])) {
            $session_id = md5(str_pad($userInfo['admin_id'], 10, "0", STR_PAD_LEFT) . session_id());
            foreach ($this->sessionInfo as $value) {
                $_SESSION[$this->sessionKey . $value] = $userInfo[$value];
            }

            if ($this->lastlogintimeIndex == true) {
                if ($this->mysqlHandle == false) die ('mysql handle not found');
                $data = array(
                    'ip' => $this->get_client_ip_server(),
                    'login_session' => $session_id,
                    $this->lastlogintimeIndex => date('Y-m-d H:i:s'),
                    'salt' => 0
                );
                $this->mysqlHandle->update($this->tableName, array($this->loginIndex=>$this->inputName), $data);
            }
            if ($this->remember_me) {
                setcookie('login_session', $session_id, time() + (86400 * 30), "/");
            }
            if (function_exists('log_admin_activity')) {
                log_admin_activity('login');
            }
            
        }
    }
    public function get_client_ip_server() {
        $ipaddress = '';
        if ($_SERVER['HTTP_CLIENT_IP'])
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if($_SERVER['HTTP_X_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if($_SERVER['HTTP_X_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if($_SERVER['HTTP_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if($_SERVER['HTTP_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if($_SERVER['REMOTE_ADDR'])
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
     
        return $ipaddress;
    }

    public function loginHtml($message){
        if (!isset($_POST['stage']) || !$_POST['stage']) {
            $message = '';
        }
        // if(!isset($_SESSION['redirect_url']) || !$_SESSION['redirect_url']){
        //     $_SESSION['redirect_url'] = (isset($_SERVER['HTTPS']) ? "https" : "http")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        // }
        header('location: login.php?message=' . rawurldecode($message));
        exit;
    }

    public function logOut(){
        foreach ($this->sessionInfo as $value) {
            $_SESSION[$this->sessionKey . $value] = '';
            session_unset($this->sessionKey . $value);
        }
        setcookie('login_session', '', time() + (86400 * 30), "/");
        header('location: index.php');
        exit;
    }

    public function getUserInfoBySessionKey(){
        if ($this->mysqlHandle == false) die ('mysql handle not found');
        $result = $this->mysqlHandle->getList('admin', array('login_session'=>$_COOKIE['login_session']));
        if($result){
            $userInfo = $result[0];
            return $userInfo;
        } else {
            return null;
        }
    }

    public function getUserInfo(){
        if ($this->mysqlHandle == false) die ('mysql handle not found');
        $result = $this->mysqlHandle->getList('admin', array($this->loginIndex=>$this->inputName));
        if($result){
            $userInfo = $result[0];
            return $userInfo;
        } else {
            return null;
        }
    }

    public function getAdminID(){
        return $_SESSION[$this->sessionKey . 'admin_id'];
    }
    public function getEmail(){
        return $_SESSION[$this->sessionKey . 'email'];
    }
    public function getGroupID(){
        return $_SESSION[$this->sessionKey . 'admin_group_id'];
    }
    public function setMysql($mysql){
        $this->mysqlHandle = $mysql;
    }

    public function getPermission(){
        if ($this->mysqlHandle == false) die ('mysql handle not found');
        $this->permission = array();
        $gpInfo = $this->mysqlHandle->getData('admin_group', array('admin_group_id'=>$this->getGroupID()));
        foreach (explode(',', $gpInfo['permission']) as $ap) {
            if (!$ap) {
                continue;
            }
            $this->permission[$ap] = true;
        }
    }
    public function isAllowPermission($tid, $action){
        global $PRIS, $exist_opt;
        if($this->permission === null){
            $this->getPermission();
        }

        return $PRIS[$tid][$action] && $this->permission[$exist_opt[$tid][$action]];
    }
}

?>