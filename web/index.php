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
    require_once('tabs/fatal_error_tab.inc.php');
    require_once('tabs/authorization_tab.inc.php');
    require_once('tabs/registration_tab.inc.php');
    require_once('tabs/profile_tab.inc.php');
    require_once('tabs/logout_tab.inc.php');
    
    require_once('tabs/teacher/new_task_tab.inc.php');

    require_once('tab_holder.inc.php');
    require_once('database_manager.inc.php');
    require_once('settings.inc.php');

    $selfLink = htmlspecialchars($_SERVER['PHP_SELF']);

    $tabHolder = new TabHolder();

    $dbm = new DatabaseManager($max_upload_file_size);

    if (!$dbm->connect($db_server, $db_user, $db_passwd, $db_name)) {
        $connError = $dbm->getConnError();

        $fatalErrorTab = new FatalErrorTab("<p><b><font color=#cc0000>Failed to connect to MySQL: (" . $connError[0] . ") " . $connError[1] . "</font></b></p>");

        $tabHolder->addTab($fatalErrorTab);
        $tabHolder->display($fatalErrorTab);
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
                $logoutTab = new LogoutTab();

                // if update profile form submitted
                if ($profileTab->isSubmitted()) {
                    $page = "profile";
                    $profileTab->handleSubmit();
                }

                // if a teacher is logged in
                if ($user_info->isTeacher) {
                    $newTaskTab = new NewTaskTab($selfLink, $dbm);
                    
                    $tabHolder->addTab($newTaskTab);
                    $tabHolder->addTab($profileTab);
                    $tabHolder->addTab($logoutTab);

                    // if new task form submitted
                    if ($newTaskTab->isSubmitted()) {
                        $page = "new_task";
                        $newTaskTab->handleSubmit();
                    }

                    if ($page === "new_task") {
                        $tabHolder->display($newTaskTab);
                    } else if ($page === "profile") {
                        $tabHolder->display($profileTab);
                    } else {
                        // TEMPORARILY
                        $tabHolder->display($profileTab);
                    }

                // if a student is logged in
                } else {
                    $tabHolder->addTab($profileTab);
                    $tabHolder->addTab($logoutTab);

                    if ($page === "submit") {
                        //display_tabs("Submit", Tabs::$STUDENT);
                    } else if ($page === "profile") {
                        $tabHolder->display($profileTab);
                    } else {
                        //display_tabs("Tasks", Tabs::$STUDENT);
                        
                        // TEMPORARILY
                        $tabHolder->display($profileTab);
                    }
                }

            }
        }

        // if not logged in
        if ($user_id < UserCheckResult::MIN_VALID_USER_ID) {
            $tabHolder->addTab($authorizationTab);
            $tabHolder->addTab($registrationTab);

            if ($page === "register") {
                $tabHolder->display($registrationTab);
            } else {
                $tabHolder->display($authorizationTab);
            }
        }
        
        $dbm->close();
    }
?>

</div>

</body>
</html>
