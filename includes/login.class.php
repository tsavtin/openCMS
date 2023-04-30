<?php

class loginModule {
    var $sessionKey = 'usersession_';
    var $proname = 'cms';
    var $tableName = 'customer';
    var $loginIndex = 'email';
    var $passwordIndex = 'password';
    var $lastlogintimeIndex = '';
    var $sessionInfo = array(
        'customer_id',
        'status',
        'firstname',
        'lastname',
        'customer_group_id'
    );
    var $passEncrypt = true;
    var $errorMessage = array(
        'missLoginName' => 'please provide username',
        'missPassword' => 'please provide password',
        'invalidPassword' => 'invaild username and password',
        'inactive' => 'inActive user'
    );
    var $logoutTag = 0;
    function check($inname, $inpass, $incheckbox)
    {   
        $this->inputName = $inname;
        $this->inputPass = $inpass;
        $this->remember_me = $incheckbox;

        if (isset($_COOKIE['login_session']) && $_COOKIE['login_session'] && !$this->inputName && !$this->inputPass) {
            $userInfo = $this->getUserInfoBySessionKey();
        } else {
            $userInfo = $this->getUserInfo();
        }


        $flag = false;

        global $languageText, $encryptKey;
        if ($this->inputName == false && $_SESSION[$this->sessionKey . $this->sessionInfo[0]] == false) {
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
                $this->loginHtml($this->errorMessage['invalidPassword']);
            } elseif ($this->encrypt($this->inputPass, $userInfo['salt']) != $userInfo[$this->passwordIndex] && $this->passEncrypt == true) {
                $this->loginHtml($this->errorMessage['invalidPassword']);
            }
            if ($userInfo['status'] == 0) {
                $this->loginHtml($this->errorMessage['inactive']);
            }
        }
        if ($this->logoutTag) {
            $this->logOut();
            $this->loginHtml('');
        } else {
            $this->setSession($userInfo);
        }
    }

    function setSession($userInfo)
    {   
        if ($this->inputName && (!isset($_SESSION[$this->sessionKey . $this->sessionInfo[0]]) || !$_SESSION[$this->sessionKey . $this->sessionInfo[0]])) {
            $session_id = md5(str_pad($userInfo['customer_id'], 10, "0", STR_PAD_LEFT) . session_id());
            foreach ($this->sessionInfo as $value) {
                $_SESSION[$this->sessionKey . $value] = $userInfo[$value];
            }

            if ($this->lastlogintimeIndex == true) {
                if ($this->mysqlHandle == false) die ('mysql handle not found');
                $data = array(
                    'login_session' => $session_id,
                    $this->lastlogintimeIndex => date('Y-m-d H:i:s')
                );
                $this->mysqlHandle->update($this->tableName, array($this->loginIndex=>$this->inputName), $data);
            }
            if ($this->remember_me) {
                setcookie('login_session', $session_id, time() + (86400 * 30), "/");
            }
        }
    }

    function loginHtml($message)
    {
        if (!$_POST['stage']) {
            $message = '';
        }
        header('location: ?file=m_signin&message=' . rawurldecode($message));
        exit;
    }

    function logOut()
    {
        foreach ($this->sessionInfo as $value) {
            $_SESSION[$this->sessionKey . $value] = '';
            session_unset($this->sessionKey . $value);
        }
        setcookie('login_session', '', time() + (86400 * 30), "/");
        header('location: index.php');
        exit;
    }

    function getUserInfoBySessionKey()
    {
        if ($this->mysqlHandle == false) die ('mysql handle not found');
        // $stmt = $this->mysqlHandle->prepare("select * from $this->tableName where login_session = :login_session");
        // $stmt->bindParam(':login_session', $_COOKIE['login_session'], PDO::PARAM_STR);
        // $stmt->execute();
        // $result = $stmt->fetchAll();

        $result = $this->mysqlHandle->getList($this->tableName, array('login_session'=>$_COOKIE['login_session']));
        if($result){
            $userInfo = $result[0];
            return $userInfo;
        } else {
            return null;
        }
    }

    function getUserInfo()
    {
        if ($this->mysqlHandle == false) die ('mysql handle not found');
        // $stmt = $this->mysqlHandle->prepare("select * from $this->tableName where $this->loginIndex = :inputName");
        // $stmt->bindParam(':inputName', $this->inputName, PDO::PARAM_STR);
        // $stmt->execute();
        // $result = $stmt->fetchAll();

        $result = $this->mysqlHandle->getList($this->tableName, array($this->loginIndex=>$this->inputName));
        if($result){
            $userInfo = $result[0];
            return $userInfo;
        } else {
            return null;
        }
    }
    function encrypt($password, $salt){
        return md5($password.$salt);
    }
    function genEncrypt($password){
        $salt = rand();
        return array($salt, $this->encrypt($password, $salt));
    }

    function getCustomerID(){
        return $_SESSION[$this->sessionKey . 'customer_id'];
    }
    function getGroupID(){
        return $_SESSION[$this->sessionKey . 'customer_group_id'];
    }
    function setMysql($mysql)
    {
        $this->mysqlHandle = $mysql;
    }
}

?>