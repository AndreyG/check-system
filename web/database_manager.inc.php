<?php

class RegistrationResult {
    const OK               = 0;
    const ERR_LOGIN_EXISTS = 1;
    const ERR_EMAIL_EXISTS = 2;
    const ERR_DB_ERROR     = 3;
}

class UpdateUserResult {
    const OK               = 0;
    const ERR_EMAIL_EXISTS = 2;
    const ERR_DB_ERROR     = 3;
}

class UserCheckResult {
    const DB_ERROR           = -2;
    const USER_NOT_LOGGED_IN = -1;
    const USER_INVALID       = 0;
    //positive result is user id in database
    const MIN_VALID_USER_ID  = 1;
}

class UserInfo {
    public $login;
    public $firstName;
    public $lastName;
    public $groupNumber;
    public $email;
    public $md5;
    public $isTeacher;
    public $lastIP;

    function __construct($login, $firstName, $lastName, $groupNumber, $email, $md5, $isTeacher, $lastIP) {
        $this->login = $login;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->groupNumber = $groupNumber;
        $this->email = $email;
        $this->md5 = $md5;
        $this->isTeacher = $isTeacher;
        $this->lastIP = $lastIP;
    }
}

class DatabaseManager {
    private $mysqli;
    private $connError;

    private function query($q) {
        file_put_contents('sql_queries.log', $q . chr(10), FILE_APPEND);
        return $this->mysqli->query($q);
    }

    public function connect($db_server, $db_user, $db_passwd, $db_name) {
        $this->mysqli = new mysqli($db_server, $db_user, $db_passwd, $db_name);
        if ($this->mysqli->connect_errno) {
            $this->connError = array($this->mysqli->connect_errno, $this->mysqli->connect_error);
            return false;
        }
        return true;
    }

    public function getConnError() {
        return $this->connError;
    }
    
    public function checkUserMD5($login, $md5) {
        $login = htmlentities($login);
        $md5 = htmlentities($md5);
        
        if ($result = $this->query('SELECT id FROM users WHERE LOWER(login) = "' . strtolower($login) . '" AND md5 = "' . $md5 . '"')) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return $row['id'];
            } else {
                return UserCheckResult::USER_INVALID;
            }
        } else {
            return UserCheckResult::DB_ERROR;
        }
    }

    public function getUserInfo($id) {
        $id = htmlentities($id);

        if ($result = $this->query('SELECT * FROM users WHERE id = ' . $id)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return new UserInfo($row['login'], $row['firstName'], $row['lastName'], $row['groupNumber'], $row['email'], $row['md5'], ($row['isTeacher'] == 1) ? 1 : 0, $row['lastIP']);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function registerNewUser($login, $firstName, $lastName, $groupNumber, $email, $md5, $isTeacher, $ip) {
        $login = htmlentities($login);
        $firstName = htmlentities($firstName);
        $lastName = htmlentities($lastName);
        $groupNumber = htmlentities($groupNumber);
        $email = htmlentities($email);
        $md5 = htmlentities($md5);
        $ip = htmlentities($ip);
        
        //perform checks
        if ($result = $this->query('SELECT id FROM users WHERE LOWER(login) = "' . strtolower($login) . '"')) {
            if ($result->num_rows > 0)
                return RegistrationResult::ERR_LOGIN_EXISTS;
        } else {
            return RegistrationResult::ERR_DB_ERROR;
        }
        if ($result = $this->query('SELECT id FROM users WHERE LOWER(email) = "' . strtolower($email) . '"')) {
            if ($result->num_rows > 0)
                return RegistrationResult::ERR_EMAIL_EXISTS;
        } else {
            return RegistrationResult::ERR_DB_ERROR;
        }
        
        //insert to database
        if ($this->query('INSERT INTO users (login, firstName, lastName, groupNumber, email, md5, isTeacher, lastIP) VALUES ("' .
                                    $login . '", "' . $firstName . '", "' . $lastName . '", "' . $groupNumber . '", "'. $email .'", "' . $md5 .
                                    '", ' . ($isTeacher ? '1' : '0') . ', "' . $ip . '")') == true) {
            return RegistrationResult::OK;
        } else {
            return RegistrationResult::ERR_DB_ERROR;
        }
    }
    
    public function updateUserInfo($id, $firstName, $lastName, $groupNumber, $email, $md5) {
        $id = htmlentities($id);
        $firstName = htmlentities($firstName);
        $lastName = htmlentities($lastName);
        $groupNumber = htmlentities($groupNumber);
        $email = htmlentities($email);
        $md5 = htmlentities($md5);
        
        //perform check
        if ($result = $this->query('SELECT id FROM users WHERE LOWER(email) = "' . strtolower($email) . '" AND id != ' . $id)) {
            if ($result->num_rows > 0)
                return UpdateUserResult::ERR_EMAIL_EXISTS;
        } else {
            return UpdateUserResult::ERR_DB_ERROR;
        }
        
        //update in database
        if ($this->query('UPDATE users SET firstName = "' . $firstName . '", lastName = "' . $lastName . '", groupNumber = "' . $groupNumber .
                              '", email = "' . $email . '", md5 = "' . $md5 . '" WHERE id = ' . $id) == true) {
            return UpdateUserResult::OK;
        } else {
            return UpdateUserResult::ERR_DB_ERROR;
        }
    }
    
    public function updateUserLastIP($id, $ip) {
        $id = htmlentities($id);
        $ip = htmlentities($ip);
        
        return ($this->query('UPDATE users SET lastIP = "' . $ip . '" WHERE id = ' . $id));
    }
    
    public function close() {
        $this->mysqli->close();
    }
}

?>
