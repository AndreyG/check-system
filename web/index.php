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
    require_once('authorization_tab.inc.php');
    require_once('registration_tab.inc.php');
    require_once('profile_tab.inc.php');
    require_once('new_task_tab.inc.php');

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
        $registrationTab = new RegistrationTab($selfLink, $dbm);
        
        $page = "";
        if (isset($_GET['page'])) {
            $page = htmlentities($_GET['page']);
        }
        
        $user_id = UserCheckResult::USER_NOT_LOGGED_IN;
        
        // if registration form submitted
        if ($registrationTab->isSubmitted()) {
            $page = "register";
            $registrationTab->handleSubmit();
        
        // if login form submitted
        } else if ($authorizationTab->isSubmitted()) {
            $authorizationTab->handleSubmit();
            $user_id = $authorizationTab->getUserId();

        // if session data is set
        } else if (isset($_SESSION['user']) && isset($_SESSION['md5']) && $_SESSION['user'] !== "" && $_SESSION['md5'] !== "") {
            $user_id = $dbm->checkUserMD5($_SESSION['user'], $_SESSION['md5']);
            if ($user_id < UserCheckResult::MIN_VALID_USER_ID) {
                $_SESSION['user'] = "";
                $_SESSION['md5'] = "";
            }
        }


        // if valid user logged in
        if ($user_id >= UserCheckResult::MIN_VALID_USER_ID) {
            $dbm->updateUserLastIP($user_id, getClientIP());

            if ($page === "logout") {
                $_SESSION['user'] = "";
                $_SESSION['md5'] = "";
                $user_id = UserCheckResult::USER_NOT_LOGGED_IN;
            } else {

                $user_info = $dbm->getUserInfo($user_id);
                
                $profileTab = new ProfileTab($selfLink, $dbm, $user_id, $user_info);
                
                // if update profile form submitted
                if ($profileTab->isSubmitted()) {
                    $page = "profile";
                    $profileTab->handleSubmit();
                }

                // if a teacher is logged in
                if ($user_info->isTeacher) {

                    $newTaskTab = new NewTaskTab($selfLink, $dbm);

                    // if new task form submitted
                    if ($newTaskTab->isSubmitted()) {
                        $page = "new_task";
                        $newTaskTab->handleSubmit();
                    }

                    if ($page === "new_task") {
                        display_tabs("Add new task", Tabs::$TEACHER);
                        $newTaskTab->displayContent();
                    } else if ($page === "profile") {
                        display_tabs("Profile", Tabs::$TEACHER);
                        $profileTab->displayContent();
                    } else {
                        display_tabs("...", Tabs::$TEACHER);
                    }

                // if a student is logged in
                } else {
                    if ($page === "submit") {
                        display_tabs("Submit", Tabs::$STUDENT);
                    } else if ($page === "profile") {
                        display_tabs("Profile", Tabs::$STUDENT);
                        $profileTab->displayContent();
                    } else {
                        display_tabs("Tasks", Tabs::$STUDENT);
                    }
                }

            }
        }

        // if not logged in
        if ($user_id < UserCheckResult::MIN_VALID_USER_ID) {
            if ($page === "register") {
                display_tabs("Registration", Tabs::$ANONYMOUS);
                $registrationTab->displayContent();
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
