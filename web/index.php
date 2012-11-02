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

    require_once('style.inc.php');
    require_once('database_manager.inc.php');
    require_once('settings.inc.php');
    
    $selfLink = htmlspecialchars($_SERVER['PHP_SELF']);
    
    $db = new DatabaseManager();
    
    if (!$db->connect($db_server, $db_user, $db_passwd, $db_name)) {
        display_tabs(0, Tabs::$FATAL_ERROR);
        $connError = $db->getConnError();
        display_content("<p>Failed to connect to MySQL: (" . $connError[0] . ") " . $connError[1] . "</p>");
    } else {

        session_start();
        session_regenerate_id(true);
        
        $page = "";
        if (isset($_GET['page'])) {
            $page = htmlentities($_GET['page']);
        }
        
        $user_id = UserCheckResult::USER_INVALID;
        
        $login_error = "";
        $register_error = "";
        $register_info = "";
        
        if (isset($_POST['submitRegister']) && isset($_POST['login']) && isset($_POST['firstName']) && isset($_POST['lastName']) &&
            isset($_POST['groupNumber']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password2'])) {

            $page = "register";
            $pwd = $_POST['password'];
            if ($pwd != $_POST['password2']) {
                $register_error = "Passwords did not match";
            } else if ($_POST['login'] == "") {
                $register_error = "Empty login not allowed";
            } else if ($_POST['firstName'] == "") {
                $register_error = "Empty first name not allowed";
            } else if ($_POST['lastName'] == "") {
                $register_error = "Empty last name not allowed";
            } else if ($_POST['groupNumber'] == "") {
                $register_error = "Empty group number not allowed";
            } else if ($_POST['email'] == "") {
                $register_error = "Empty email not allowed";
            } else if ($_POST['password'] == "") {
                $register_error = "Empty password not allowed";
            } else {
                $regRes = $db->registerNewUser($_POST['login'], $_POST['firstName'], $_POST['lastName'], $_POST['groupNumber'],
                                               $_POST['email'], md5($_POST['password']), false, getClientIP());
                if ($regRes == RegistrationResult::OK) {
                    $register_info = "Registered successfully";
                } else if ($regRes == RegistrationResult::ERR_LOGIN_EXISTS) {
                    $register_error = "Such login already registered";
                } else if ($regRes == RegistrationResult::ERR_EMAIL_EXISTS) {
                    $register_error = "Such email already registered";
                } else if ($regRes == RegistrationResult::ERR_DB_ERROR) {
                    $register_error = "Database query error";
                }
            }

        } else if (isset($_POST['submitLogin']) && isset($_POST['login']) && isset($_POST['password'])) {
            $user = htmlentities($_POST['login']);
            $md5 = md5($_POST['password']);
            $user_id = $db->checkUserMD5($user, $md5);
            if ($user_id >= UserCheckResult::MIN_VALID_USER_ID) {
                $_SESSION['user'] = $user;
                $_SESSION['md5'] = $md5;
            } else if ($user_id == UserCheckResult::USER_INVALID) {
                $login_error = "Incorrect login or password";
            } else if ($user_id == UserCheckResult::DB_ERROR) {
                $login_error = "Database query error";
            }
        } else if (isset($_SESSION['user']) && isset($_SESSION['md5'])) {
            $user_id = $db->checkUserMD5($_SESSION['user'], $_SESSION['md5']);
        }
        
        if ($user_id >= UserCheckResult::MIN_VALID_USER_ID) {
        } else {
            if ($page == "register") {
                display_register_form($selfLink, $register_error, $register_info);
            } else {
                display_login_form($selfLink, $login_error);
            }
        }
        
        $db->close();
    }
?>

</div>

</body>
</html>
