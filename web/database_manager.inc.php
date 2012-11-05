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

class SaveFileResult {
    const ERR_NO_FILE       = -3;
    const ERR_DB_ERROR      = -2;
    const ERR_FILE_TOO_BIG  = -1;
    const ERR_UPLOAD_ERROR  = 0;
    //positive result is file id in database
    const MIN_VALID_FILE_ID = 1;
}

class FileStruct {
    public $name;
    public $contents;
    
    function __construct($name, $contents) {
        $this->name = $name;
        $this->contents = $contents;
    }
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
    public $maxUploadFileSize;

    private $mysqli;
    private $connError;

    function __construct($maxUploadFileSize) {
        $this->maxUploadFileSize = $maxUploadFileSize;
    }

    private function query($q) {
        if (strlen($q) < 1024) {
            file_put_contents('sql_queries.log', $q . chr(10), FILE_APPEND);
        }
        return $this->mysqli->query($q);
    }
    
    private function escapeStr($s) {
        return $this->mysqli->real_escape_string($s);
    }

    // returns true or false
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

    // returns UserCheckResult
    public function checkUserMD5($login, $md5) {
        $login = $this->escapeStr($login);
        $md5   = $this->escapeStr($md5);
        
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

    // returns UserInfo or false
    public function getUserInfo($id) {
        $id = $this->escapeStr($id);

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
    
    // returns RegistrationResult
    public function registerNewUser($login, $firstName, $lastName, $groupNumber, $email, $md5, $isTeacher, $ip) {
        $login       = $this->escapeStr($login);
        $firstName   = $this->escapeStr($firstName);
        $lastName    = $this->escapeStr($lastName);
        $groupNumber = $this->escapeStr($groupNumber);
        $email       = $this->escapeStr($email);
        $md5         = $this->escapeStr($md5);
        $ip          = $this->escapeStr($ip);
        
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
                                    '", ' . (($isTeacher === true) ? '1' : '0') . ', "' . $ip . '")') == true) {
            return RegistrationResult::OK;
        } else {
            return RegistrationResult::ERR_DB_ERROR;
        }
    }
    
    // returns UpdateUserResult
    public function updateUserInfo($id, $firstName, $lastName, $groupNumber, $email, $md5) {
        $id          = $this->escapeStr($id);
        $firstName   = $this->escapeStr($firstName);
        $lastName    = $this->escapeStr($lastName);
        $groupNumber = $this->escapeStr($groupNumber);
        $email       = $this->escapeStr($email);
        $md5         = $this->escapeStr($md5);
        
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

    // returns true or false
    public function updateUserLastIP($id, $ip) {
        $id = $this->escapeStr($id);
        $ip = $this->escapeStr($ip);
        
        return ($this->query('UPDATE users SET lastIP = "' . $ip . '" WHERE id = ' . $id));
    }
    
    // returns SaveFileResult
    public function saveFile($fileInfo) {
        if ($fileInfo['size'] > $this->maxUploadFileSize)
            return SaveFileResult::ERR_FILE_TOO_BIG;
        if ($fileInfo['error'] == UPLOAD_ERR_NO_FILE)
            return SaveFileResult::ERR_NO_FILE;
        if ($fileInfo['error'] != UPLOAD_ERR_OK)
            return SaveFileResult::ERR_UPLOAD_ERROR;
        if (!is_uploaded_file($fileInfo['tmp_name']))
            return SaveFileResult::ERR_UPLOAD_ERROR;

        $fileName = $this->escapeStr(basename($fileInfo['name']));
        $fileSize = $this->escapeStr($fileInfo['size']);
        $fileData = $this->escapeStr(file_get_contents($fileInfo['tmp_name']));
        $fileDataMD5 = md5($fileData);
        
        //insert to database
        if ($this->query('INSERT INTO files (name, size, data, data_md5) VALUES ("' . $fileName . '", ' . $fileSize . ', "' . $fileData . '", "' . $fileDataMD5 . '")')) {
            return $this->mysqli->insert_id;
        } else {
            return SaveFileResult::ERR_DB_ERROR;
        }
    }

    // returns true or false
    public function addNewTask($name, $description, $taskFileId, $envFileId) {
        $name        = $this->escapeStr($name);
        $description = $this->escapeStr($description);
        
        $q_p1 = "";
        $q_p2 = "";
        $q_p3 = "";
        $q_p4 = "";
        
        if ($taskFileId >= SaveFileResult::MIN_VALID_FILE_ID) {
            $q_p1 = ', task_file_id';
            $q_p3 = ', ' . $this->escapeStr($taskFileId);
        }
        if ($envFileId >= SaveFileResult::MIN_VALID_FILE_ID) {
            $envFileId = $this->escapeStr($envFileId);
            $q_p2 = ', env_file_id';
            $q_p4 = ', ' . $this->escapeStr($envFileId);
        }

        return ($this->query('INSERT INTO tasks (name, description' . $q_p1 . $q_p2 . ') VALUES ("' . $name . '", "' . $description . '"' . $q_p3 . $q_p4 . ')'));
    }
    
    const SQL_SELECT_ALL_TASKS = 'SELECT tasks.*, task_f.name, task_f.size, task_f.data_md5, env_f.name, env_f.size, env_f.data_md5 FROM tasks, files as task_f, files as env_f WHERE tasks.task_file_id = task_f.id AND tasks.env_file_id = env_f.id
UNION
SELECT tasks.*, files.name, files.size, files.data_md5, "-", -1, NULL FROM tasks, files WHERE tasks.task_file_id = files.id AND tasks.env_file_id IS NULL
UNION
SELECT tasks.*, "-", -1, NULL, files.name, files.size, files.data_md5 FROM tasks, files WHERE tasks.task_file_id IS NULL AND tasks.env_file_id = files.id
UNION
SELECT tasks.*, "-", -1, NULL, "-", -1, NULL FROM tasks WHERE tasks.task_file_id IS NULL AND tasks.env_file_id IS NULL
ORDER BY 1';

    public function getAllTasks() {
        if ($result = $this->query($this::SQL_SELECT_ALL_TASKS)) {
            $ans = array();
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                array_push($ans, $row);
            }
            return $ans;
        } else {
            return false;
        }
    }

    // returns FileStruct or false
    public function getFile($id, $md5) {
        $id = $this->escapeStr($id);
        $md5 = $this->escapeStr($md5);
        
        if ($result = $this->query('SELECT name, data FROM files WHERE id = ' . $id . ' AND data_md5 = "' . $md5 . '"')) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return new FileStruct($row['name'], $row['data']);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function close() {
        $this->mysqli->close();
    }
}

?>
