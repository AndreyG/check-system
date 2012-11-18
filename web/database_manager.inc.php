<?php

class RegistrationResult {
    const OK                = 0;
    const ERR_LOGIN_EXISTS  = 1;
    const ERR_EMAIL_EXISTS  = 2;
    const ERR_INVALID_GROUP = 3;
    const ERR_DB_ERROR      = 4;
}

class UpdateUserResult {
    const OK                = 0;
    const ERR_EMAIL_EXISTS  = 2;
    const ERR_INVALID_GROUP = 3;
    const ERR_DB_ERROR      = 4;
}

class UserCheckResult {
    const DEFAULT_ADMIN      = -100;  // see 'settings.inc.php' for details
    const DB_ERROR           = -2;
    const USER_NOT_LOGGED_IN = -1;
    const USER_INVALID       = 0;
    // positive result is user id in database
    const MIN_VALID_USER_ID  = 1;
}

class SaveFileResult {
    const ERR_NO_FILE       = -3;
    const ERR_DB_ERROR      = -2;
    const ERR_FILE_TOO_BIG  = -1;
    const ERR_UPLOAD_ERROR  = 0;
    // positive result is file id in database
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
    public $groupId;
    public $email;
    public $md5;
    public $isTeacher;
    public $lastIP;

    function __construct($login, $firstName, $lastName, $groupId, $email, $md5, $isTeacher, $lastIP) {
        $this->login = $login;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->groupId = $groupId;
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
    
    private $db_user;
    private $db_passwd_md5;
    
    private $repo_worker_host;
    private $repo_worker_port;

    function __construct($maxUploadFileSize, $repo_worker_host, $repo_worker_port) {
        $this->maxUploadFileSize = $maxUploadFileSize;
        $this->repo_worker_host = $repo_worker_host;
        $this->repo_worker_port = $repo_worker_port;
    }

    private function query($q) {
        if (strlen($q) < 1024) {
            file_put_contents('sql_queries.log', date("Y-m-d H:i:s") . ': ' . $q . chr(10), FILE_APPEND);
        }
        return $this->mysqli->query($q);
    }
    
    private function escapeStr($s) {
        return $this->mysqli->real_escape_string($s);
    }

    // returns true or false
    public function connect($db_server, $db_user, $db_passwd, $db_name) {
        $this->db_user = $db_user;
        $this->db_passwd_md5 = md5($db_passwd);

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

        if ($login === $this->db_user && $md5 === $this->db_passwd_md5)
            return UserCheckResult::DEFAULT_ADMIN;

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
                return new UserInfo($row['login'], $row['firstName'], $row['lastName'], $row['groupId'], $row['email'], $row['md5'], ($row['isTeacher'] == 1) ? true : false, $row['lastIP']);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function newRepoOperation($reqUserId, $forUserId, $operation, $params = array()) {
        $reqUserId = $this->escapeStr($reqUserId);
        $forUserId = $this->escapeStr($forUserId);
        $operation = $this->escapeStr($operation);

        $q_p1 = "";
        $q_p2 = "";
        $i = 1;
        foreach ($params as $param) {
            $q_p1 .= ', param' . $i;
            $q_p2 .= ', "' . $this->escapeStr($param) . '"';
            ++$i;
        }

        if (!$this->query('INSERT INTO repo_operations (req_user_id, for_user_id, command' . $q_p1 . ', created' .
                                               ') VALUES (' . $reqUserId . ', ' . $forUserId . ', "' . $operation . '"' . $q_p2 . ', NOW())')) {
            return false;
        }
        $roId = $this->mysqli->insert_id;

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            file_put_contents('socket_errors.log', date("Y-m-d H:i:s") . ', roId = ' . $roId . ': ' . socket_strerror(socket_last_error()) . chr(10), FILE_APPEND);
        } else {
            $result = socket_connect($socket, $this->repo_worker_host, $this->repo_worker_port);
            if ($result === false) {
                file_put_contents('socket_errors.log', date("Y-m-d H:i:s") . ', roId = ' . $roId . ': ' . socket_strerror(socket_last_error()) . chr(10), FILE_APPEND);
            } else {
                $updatePacket = "update";
                socket_write($socket, $updatePacket, strlen($updatePacket));
                socket_close($socket);
            }
        }

        return true;
    }

    // returns RegistrationResult
    public function registerNewUser($login, $firstName, $lastName, $groupId, $email, $md5, $isTeacher, $ip, $publicKey) {
        $login     = $this->escapeStr($login);
        $firstName = $this->escapeStr($firstName);
        $lastName  = $this->escapeStr($lastName);
        $groupId   = $this->escapeStr($groupId);
        $email     = $this->escapeStr($email);
        $md5       = $this->escapeStr($md5);
        $ip        = $this->escapeStr($ip);

        if ($login === $this->db_user)
            return RegistrationResult::ERR_LOGIN_EXISTS;

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
        if ($result = $this->query('SELECT id FROM groups WHERE id = ' . $groupId)) {
            if ($result->num_rows != 1)
                return RegistrationResult::ERR_INVALID_GROUP;
        } else {
            return RegistrationResult::ERR_DB_ERROR;
        }

        //insert to database
        if ($this->query('INSERT INTO users (login, firstName, lastName, groupId, email, md5, isTeacher, lastIP) VALUES ("' .
                                    $login . '", "' . $firstName . '", "' . $lastName . '", "' . $groupId . '", "'. $email .'", "' . $md5 .
                                    '", ' . (($isTeacher === true) ? '1' : '0') . ', "' . $ip . '")') == true) {
            
            $this->newRepoOperation($this->mysqli->insert_id, $this->mysqli->insert_id, 'createrepo', array('u' . $this->mysqli->insert_id, $publicKey));
            return RegistrationResult::OK;
        } else {
            return RegistrationResult::ERR_DB_ERROR;
        }
    }

    // returns UpdateUserResult
    public function updateUserInfo($id, $firstName, $lastName, $groupId, $email, $md5, $isTeacher) {
        $id        = $this->escapeStr($id);
        $firstName = $this->escapeStr($firstName);
        $lastName  = $this->escapeStr($lastName);
        $groupId   = $this->escapeStr($groupId);
        $email     = $this->escapeStr($email);
        $md5       = $this->escapeStr($md5);
        
        //perform checks
        if ($result = $this->query('SELECT id FROM users WHERE LOWER(email) = "' . strtolower($email) . '" AND id != ' . $id)) {
            if ($result->num_rows > 0)
                return UpdateUserResult::ERR_EMAIL_EXISTS;
        } else {
            return UpdateUserResult::ERR_DB_ERROR;
        }
        if (!$isTeacher) {
            if ($result = $this->query('SELECT id FROM groups WHERE id = ' . $groupId)) {
                if ($result->num_rows != 1)
                    return UpdateUserResult::ERR_INVALID_GROUP;
            } else {
                return UpdateUserResult::ERR_DB_ERROR;
            }
        }
        
        //update in database
        if ($this->query('UPDATE users SET firstName = "' . $firstName . '", lastName = "' . $lastName . '", groupId = "' . $groupId .
                         '", email = "' . $email . '", md5 = "' . $md5 . '", isTeacher = "' . (($isTeacher === true) ? '1' : '0') . '" WHERE id = ' . $id) == true) {
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
    
    private function updateTaskGroups($taskId, $groupIds) {
        $this->query('DELETE FROM group_tasks WHERE task_id = ' . $taskId);
        foreach ($groupIds as $groupId) {
            $groupId = $this->escapeStr($groupId);
            $this->query('INSERT INTO group_tasks (group_id, task_id) VALUES (' . $groupId . ', ' . $taskId . ') ');
        }
    }
    
    private function updateTaskStudents($taskId, $studentIds) {
        $this->query('DELETE FROM student_tasks WHERE task_id = ' . $taskId);
        foreach ($studentIds as $studentId) {
            $studentId = $this->escapeStr($studentId);
            $this->query('INSERT INTO student_tasks (student_id, task_id) VALUES (' . $studentId . ', ' . $taskId . ') ');
        }
    }

    // returns true or false
    public function addNewTask($name, $description, $taskFileId, $envFileId, $groupIds, $studentIds) {
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
            $q_p2 = ', env_file_id';
            $q_p4 = ', ' . $this->escapeStr($envFileId);
        }

        if (!$this->query('INSERT INTO tasks (name, description' . $q_p1 . $q_p2 . ') VALUES ("' . $name . '", "' . $description . '"' . $q_p3 . $q_p4 . ')'))
            return false;
        $taskId = $this->mysqli->insert_id;

        $this->updateTaskGroups($taskId, $groupIds);
        $this->updateTaskStudents($taskId, $studentIds);

        return true;
    }

    public function updateTask($id, $name, $description, $taskFileId, $envFileId, $groupIds, $studentIds) {
        $id          = $this->escapeStr($id);
        $name        = $this->escapeStr($name);
        $description = $this->escapeStr($description);

        $q_p1 = "";
        $q_p2 = "";

        if ($taskFileId >= SaveFileResult::MIN_VALID_FILE_ID) {
            $q_p1 = ', task_file_id = ' . $this->escapeStr($taskFileId);
        }
        if ($envFileId >= SaveFileResult::MIN_VALID_FILE_ID) {
            $q_p2 = ', env_file_id = '. $this->escapeStr($envFileId);
        }

        if (!$this->query('UPDATE tasks SET name = "' . $name . '", description = "' . $description . '"' . $q_p1 . $q_p2 . ' WHERE id = ' . $id))
            return false;

        $this->updateTaskGroups($id, $groupIds);
        $this->updateTaskStudents($id, $studentIds);

        return true;
    }

    public function getAllTasks() {
        if ($result = $this->query('SELECT tasks.*, tf.name, tf.size, tf.data_md5, ef.name, ef.size, ef.data_md5 FROM tasks LEFT OUTER JOIN files AS tf ON tf.id = tasks.task_file_id LEFT OUTER JOIN files AS ef ON ef.id = tasks.env_file_id ORDER BY tasks.id')) {
            $ans = array();
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                array_push($ans, $row);
            }
            return $ans;
        } else {
            return false;
        }
    }

    public function getTask($taskId) {
        $taskId = $this->escapeStr($taskId);

        if ($result = $this->query('SELECT tasks.*, tf.name, tf.size, tf.data_md5, ef.name, ef.size, ef.data_md5 FROM tasks LEFT OUTER JOIN files AS tf ON tf.id = tasks.task_file_id LEFT OUTER JOIN files AS ef ON ef.id = tasks.env_file_id WHERE tasks.id = ' . $taskId)) {
            return (($result->num_rows == 1) ? $result->fetch_array(MYSQLI_NUM) : false);
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
    
    public function getAllStudents($groupId = -1) {
        $groupCond = "";
        if ($groupId !== -1) {
            $groupCond = " AND groupId = " . $this->escapeStr($groupId);
        }

        if ($result = $this->query('SELECT users.id, users.firstName, users.lastName, groups.name FROM users LEFT OUTER JOIN groups ON groups.id = users.groupId WHERE users.isTeacher = 0' . $groupCond . ' ORDER BY users.groupId')) {
            $ans = array();
            while ($row = $result->fetch_assoc()) {
                array_push($ans, $row);
            }
            return $ans;
        } else {
            return false;
        }
    }

    public function getAllTasksForGroup($groupId) {
        $groupId = $this->escapeStr($groupId);

        if ($result = $this->query('SELECT gt.task_id, tasks.name FROM group_tasks AS gt INNER JOIN tasks ON tasks.id = gt.task_id WHERE gt.group_id = ' . $groupId)) {
            $ans = array();
            while ($row = $result->fetch_assoc()) {
                array_push($ans, $row);
            }
            return $ans;
        } else {
            return false;
        }
    }

    public function getAllTasksForStudent($studentId) {
        $studentId = $this->escapeStr($studentId);

        if ($result = $this->query('SELECT st.task_id, tasks.name, tasks.description, tasks.task_file_id, tasks.env_file_id, tf.name, tf.size, tf.data_md5, ef.name, ef.size, ef.data_md5, 0 FROM student_tasks AS st INNER JOIN tasks ON tasks.id = st.task_id LEFT OUTER JOIN files AS tf ON tf.id = tasks.task_file_id LEFT OUTER JOIN files AS ef ON ef.id = tasks.env_file_id WHERE st.student_id = ' . $studentId .
                            ' UNION SELECT gt.task_id, tasks.name, tasks.description, tasks.task_file_id, tasks.env_file_id, tf.name, tf.size, tf.data_md5, ef.name, ef.size, ef.data_md5, 1 FROM users INNER JOIN group_tasks AS gt ON users.groupId = gt.group_id INNER JOIN tasks ON tasks.id = gt.task_id LEFT OUTER JOIN files AS tf ON tf.id = tasks.task_file_id LEFT OUTER JOIN files AS ef ON ef.id = tasks.env_file_id WHERE users.id = ' . $studentId .
                            ' ORDER BY 1')) {
            $ans = array();
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                array_push($ans, $row);
            }
            return $ans;
        } else {
            return false;
        }
    }

    public function getAllAssignmentsForTask($taskId) {
        $taskId = $this->escapeStr($taskId);

        if ($result = $this->query('SELECT groups.id, groups.name, 1 FROM groups INNER JOIN group_tasks AS gt ON groups.id = gt.group_id WHERE gt.task_id = ' . $taskId .
                            ' UNION SELECT users.id, CONCAT(users.firstName, " ", users.lastName), 0 FROM users INNER JOIN student_tasks AS st ON users.id = st.student_id WHERE st.task_id = ' . $taskId)) {
            $ans = array();
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                array_push($ans, $row);
            }
            return $ans;
        } else {
            return false;
        }
    }

    public function addGroup($groupName) {
        $groupName = $this->escapeStr($groupName);
        
        return $this->query('INSERT INTO groups (name) VALUES ("' . $groupName . '")');
    }

    public function getAllGroups() {
        if ($result = $this->query('SELECT id, name FROM groups')) {
            $ans = array();
            while ($row = $result->fetch_assoc()) {
                array_push($ans, $row);
            }
            return $ans;
        } else {
            return false;
        }
    }

    // returns true if table 'groups' is not empty
    public function checkIfGroupsExist() {
        if ($result = $this->query('SELECT COUNT(*) FROM groups')) {
            $row = $result->fetch_array(MYSQLI_NUM);
            return ($row[0] > 0);
        } else {
            return false;
        }
    }

    public function makeTeacher($userId) {
        $userId = $this->escapeStr($userId);
        
        if (!$this->query('UPDATE users SET isTeacher = 1 WHERE isTeacher = 0 AND id = ' . $userId))
            return false;
        $this->newRepoOperation(0, $userId, 'maketeacher', array('u' . $userId));
        return true;
    }

    public function getOperations($userId = 0) {
        $q_p1 = "";
        if ($userId !== 0) {
            $userId = $this->escapeStr($userId);
            $q_p1 = " WHERE ro.req_user_id = " . $userId . " OR ro.for_user_id = " . $userId;
        }
        if ($result = $this->query('SELECT CONCAT(u1.firstName, " ", u1.lastName), CONCAT(u2.firstName, " ", u2.lastName), ro.command, ro.done, ro.repo_worker_message, ro.created, ro.processed FROM repo_operations AS ro LEFT OUTER JOIN users AS u1 ON ro.req_user_id = u1.id LEFT OUTER JOIN users AS u2 ON ro.for_user_id = u2.id' . $q_p1 . ' ORDER BY ro.id DESC')) {
            $ans = array();
            while ($row = $result->fetch_array()) {
                array_push($ans, $row);
            }
            return $ans;
        } else {
            return false;
        }
    }

    public function getGitAddress($userId = 0) {
        if ($userId !== 0) {
            $userId = $this->escapeStr($userId);
            if ($result = $this->query('SELECT git FROM users WHERE id = ' . $userId)) {
                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    return $row['git'];
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return "git@<host>:tasks.git";
    }
    
    public function getPublicKey($userId) {
        $userId = $this->escapeStr($userId);
        if ($result = $this->query('SELECT param2 FROM repo_operations WHERE for_user_id = ' . $userId . ' AND (command = "createrepo" OR command = "newpubkey") AND done = 1 ORDER BY processed DESC LIMIT 1')) {
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                return $row['param2'];
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
