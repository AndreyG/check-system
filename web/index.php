<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Check System Web Client</title>
</head>
<body>

<h1 align=center>Check System Web Client</h1>

<div id="wrap">

<?php
    function getClientIP() {
        $client_ip = ( !empty($HTTP_SERVER_VARS['REMOTE_ADDR']) ) ? $HTTP_SERVER_VARS['REMOTE_ADDR'] : ( ( !empty($HTTP_ENV_VARS['REMOTE_ADDR']) ) ? $HTTP_ENV_VARS['REMOTE_ADDR'] : getenv('REMOTE_ADDR') );
        return $client_ip;
    }

    require_once('authorization_tab.inc.php');

    require_once('database_manager.inc.php');
    require_once('style.inc.php');
    require_once('settings.inc.php');
    
    $selfLink = htmlspecialchars($_SERVER['PHP_SELF']);
    
    $dbm = new DatabaseManager();
    
    if (!$dbm->connect($db_server, $db_user, $db_passwd, $db_name)) {
        display_tabs(0, Tabs::$FATAL_ERROR);
        $connError = $dbm->getConnError();
        display_content("<p><b><font color=#cc0000>Failed to connect to MySQL: (" . $connError[0] . ") " . $connError[1] . "</font></b></p>");
    } else {

        session_start();
        session_regenerate_id(true);
        
        $authorizationTab = new AuthorizationTab($selfLink, $dbm);
        
        $page = "";
        if (isset($_GET['page'])) {
            $page = htmlentities($_GET['page']);
        }
        
        $user_id = UserCheckResult::USER_INVALID;
        
        $register_page_error = "";
        $register_page_info = "";
        
        // if registration form submitted
        if (isset($_POST['submitRegister']) && isset($_POST['login']) && isset($_POST['firstName']) && isset($_POST['lastName']) &&
            isset($_POST['groupNumber']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password2'])) {

            $page = "register";

            $pwd = $_POST['password'];
            if ($pwd != $_POST['password2']) {
                $register_page_error = "Passwords did not match";
            } else if ($_POST['login'] == "") {
                $register_page_error = "Empty login not allowed";
            } else if ($_POST['firstName'] == "") {
                $register_page_error = "Empty first name not allowed";
            } else if ($_POST['lastName'] == "") {
                $register_page_error = "Empty last name not allowed";
            } else if ($_POST['groupNumber'] == "") {
                $register_page_error = "Empty group number not allowed";
            } else if ($_POST['email'] == "") {
                $register_page_error = "Empty email not allowed";
            } else if ($_POST['password'] == "") {
                $register_page_error = "Empty password not allowed";

            } else {
                $regRes = $dbm->registerNewUser($_POST['login'], $_POST['firstName'], $_POST['lastName'], $_POST['groupNumber'],
                                               $_POST['email'], md5($_POST['password']), false, getClientIP());
                if ($regRes == RegistrationResult::OK) {
                    $register_page_info = "Registered successfully";
                } else if ($regRes == RegistrationResult::ERR_LOGIN_EXISTS) {
                    $register_page_error = "Such login already registered";
                } else if ($regRes == RegistrationResult::ERR_EMAIL_EXISTS) {
                    $register_page_error = "Such email already registered";
                } else if ($regRes == RegistrationResult::ERR_DB_ERROR) {
                    $register_page_error = "Database query error";
                }
            }

        // if login form submitted
        } else if ($authorizationTab->isSubmitted()) {
            $authorizationTab->handleSubmit();
            $user_id = $authorizationTab->getUserId();

        // if session data is set
        } else if (isset($_SESSION['user']) && isset($_SESSION['md5']) && $_SESSION['user'] != "" && $_SESSION['md5'] != "") {
            $user_id = $dbm->checkUserMD5($_SESSION['user'], $_SESSION['md5']);
            if ($user_id < UserCheckResult::MIN_VALID_USER_ID) {
                $_SESSION['user'] = "";
                $_SESSION['md5'] = "";
            }
        }


        // if valid user logged in
        if ($user_id >= UserCheckResult::MIN_VALID_USER_ID) {
            $dbm->updateUserLastIP($user_id, getClientIP());

            if ($page == "logout") {
                $_SESSION['user'] = "";
                $_SESSION['md5'] = "";
                $user_id = UserCheckResult::USER_NOT_LOGGED_IN;
            } else {

                $user_info = $dbm->getUserInfo($user_id);
                
                $profile_page_error = "";
                $profile_page_info = "";
                $new_task_page_error = "";
                $new_task_page_info = "";
                $new_task_page_old_description_value = "";
                
                // if update profile form submitted
                if (isset($_POST['submitUpdateProfile']) && isset($_POST['firstName']) && isset($_POST['lastName']) && isset($_POST['email']) &&
                    isset($_POST['password']) && isset($_POST['password2']) && isset($_POST['curPassword']) && ($user_info->isTeacher || isset($_POST['groupNumber']))) {

                    $page = "profile";
                    
                    $user_info->firstName = $_POST['firstName'];
                    $user_info->lastName = $_POST['lastName'];
                    if (!$user_info->isTeacher)
                        $user_info->groupNumber = $_POST['groupNumber'];
                    $user_info->email = $_POST['email'];

                    if (md5($_POST['curPassword']) != $user_info->md5) {
                        $profile_page_error = "Current password incorrect";
                    } else if ($_POST['password'] != "" && $_POST['password'] != $_POST['password2']) {
                        $profile_page_error = "New passwords did not match";
                    } else if ($_POST['firstName'] == "") {
                        $profile_page_error = "Empty first name not allowed";
                    } else if ($_POST['lastName'] == "") {
                        $profile_page_error = "Empty last name not allowed";
                    } else if (!$user_info->isTeacher && $_POST['groupNumber'] == "") {
                        $profile_page_error = "Empty group number not allowed";
                    } else if ($_POST['email'] == "") {
                        $profile_page_error = "Empty email not allowed";

                    } else {
                        $updRes = $dbm->updateUserInfo($user_id, $user_info->firstName, $user_info->lastName, $user_info->groupNumber,
                                                       $user_info->email, ($_POST['password'] != "") ? md5($_POST['password']) : $user_info->md5);
                        if ($updRes == UpdateUserResult::OK) {
                            $profile_page_info = "Profile updated successfully";
                            $user_info = $dbm->getUserInfo($user_id);
                            $_SESSION['md5'] = $user_info->md5;
                        } else if ($updRes == UpdateUserResult::ERR_EMAIL_EXISTS) {
                            $profile_page_error = "Such email already registered";
                        } else if ($updRes == UpdateUserResult::ERR_DB_ERROR) {
                            $profile_page_error = "Database query error";
                        }
                    }
                }

                // if a teacher is logged in
                if ($user_info->isTeacher) {

                    // if new task form submitted
                    if (isset($_POST['submitNewTask']) && isset($_POST['name']) && isset($_POST['description']) && isset($_FILES['taskFile']) && isset($_FILES['envFile'])) {
                        $page = "new_task";
                        
                        if ($_POST['name'] == "") {
                            $new_task_page_error = "Task name can't be empty";
                            $new_task_page_old_description_value = $_POST['description'];
                        } else {
                            $task_file_id = false;
                            $env_file_id = false;

                            if ($_FILES['taskFile']['error'] != UPLOAD_ERR_NO_FILE) {
                                $task_file_id = $dbm->saveFile($_FILES['taskFile']);
                                if ($task_file_id === false) {
                                    $new_task_page_error = "Error while uploading task file";
                                    $new_task_page_old_description_value = $_POST['description'];
                                }
                            }
                            if ($_FILES['envFile']['error'] != UPLOAD_ERR_NO_FILE) {
                                $env_file_id = $dbm->saveFile($_FILES['envFile']);
                                if ($env_file_id === false) {
                                    $new_task_page_error = "Error while uploading student env file";
                                    $new_task_page_old_description_value = $_POST['description'];
                                }
                            }
                            
                            // if still no error
                            if ($new_task_page_error == "") {
                                
                            }
                        }
                    }

                    if ($page == "new_task") {
                        display_tabs("Add new task", Tabs::$TEACHER);
                        display_new_task_page($selfLink, $new_task_page_error, $new_task_page_info, $new_task_page_old_description_value);
                    } else if ($page == "profile") {
                        display_tabs("Profile", Tabs::$TEACHER);
                        display_profile_page($selfLink, $profile_page_error, $profile_page_info, $user_info);
                    } else {
                        display_tabs("...", Tabs::$TEACHER);
                    }

                // if a student is logged in
                } else {
                    if ($page == "submit") {
                        display_tabs("Submit", Tabs::$STUDENT);
                    } else if ($page == "profile") {
                        display_tabs("Profile", Tabs::$STUDENT);
                        display_profile_page($selfLink, $profile_page_error, $profile_page_info, $user_info);
                    } else {
                        display_tabs("Tasks", Tabs::$STUDENT);
                    }
                }

            }
        }

        // if not logged in
        if ($user_id < UserCheckResult::MIN_VALID_USER_ID) {
            if ($page == "register") {
                display_tabs("Registration", Tabs::$ANONYMOUS);
                display_register_page($selfLink, $register_page_error, $register_page_info);
            } else {
                display_tabs("Authorization", Tabs::$ANONYMOUS);
                $authorizationTab->displayContent();
            }
        }
        
        $dbm->close();
    }
?>

</div>

</body>
</html>
