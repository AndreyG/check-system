<?php
    require_once('tabs/fatal_error_tab.inc.php');
    require_once('tabs/make_teacher_tab.inc.php');
    require_once('tabs/authorization_tab.inc.php');
    require_once('tabs/registration_tab.inc.php');
    require_once('tabs/profile_tab.inc.php');
    require_once('tabs/repo_tab.inc.php');
    require_once('tabs/logout_tab.inc.php');

    require_once('tabs/student/tasks_tab.inc.php');

    require_once('tabs/teacher/all_tasks_tab.inc.php');
    require_once('tabs/teacher/all_students_tab.inc.php');
    require_once('tabs/teacher/groups_tab.inc.php');
    require_once('tabs/teacher/edit_task_tab.inc.php');

    require_once('tab_holder.inc.php');
    require_once('database_manager.inc.php');
    require_once('style.inc.php');
    require_once('settings.inc.php');

    $showPage = true;  // show html page or not

    $selfLink = htmlspecialchars($_SERVER['PHP_SELF']);

    $tabHolder = new TabHolder();

    $dbm = new DatabaseManager($repo_worker_host, $repo_worker_port);

    if (!$dbm->connect($db_server, $db_user, $db_passwd, $db_name)) {
        $connError = $dbm->getConnError();

        $fatalErrorTab = new FatalErrorTab("<p><b><font color=#cc0000>Failed to connect to MySQL: (" . $connError[0] . ") " . $connError[1] . "</font></b></p>");

        $tabHolder->addTab($fatalErrorTab);

        html_page_start();
        $tabHolder->display($fatalErrorTab);
        html_page_end();
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
            $page = $registrationTab->getTabInfo()->page;
            $registrationTab->handleSubmit();

        // if login form submitted
        } else if ($authorizationTab->isSubmitted()) {
            $authorizationTab->handleSubmit();
            $user_id = $authorizationTab->getUserId();

        // if session data is set
        } else if (isset($_SESSION['user']) && isset($_SESSION['md5']) && $_SESSION['user'] !== "" && $_SESSION['md5'] !== "") {
            $user_id = $dbm->checkUserMD5($_SESSION['user'], $_SESSION['md5']);
            if ($user_id < UserCheckResult::MIN_VALID_USER_ID && $user_id !== UserCheckResult::DEFAULT_ADMIN) {
                $_SESSION['user'] = "";
                $_SESSION['md5'] = "";
            }
        }

        // if default admin logged in
        if ($user_id === UserCheckResult::DEFAULT_ADMIN) {
            if ($page === "logout") {
                $_SESSION['user'] = "";
                $_SESSION['md5'] = "";
                $user_id = UserCheckResult::USER_NOT_LOGGED_IN;
            } else {
                $groupsTab = new GroupsTab($selfLink, $dbm);
                $makeTeacherTab = new MakeTeacherTab($dbm);
                $logoutTab = new LogoutTab();
                
                // if new group form submitted
                if ($groupsTab->isSubmitted()) {
                    $page = $groupsTab->getTabInfo()->page;
                    $groupsTab->handleSubmit();
                }
            
                $tabHolder->addTab($groupsTab);
                $tabHolder->addTab($makeTeacherTab);
                $tabHolder->addTab($logoutTab);
            }

        // if valid user logged in
        } else if ($user_id >= UserCheckResult::MIN_VALID_USER_ID) {
            $dbm->updateUserLastIP($user_id, getClientIP());

            if ($page === "logout") {
                $_SESSION['user'] = "";
                $_SESSION['md5'] = "";
                $user_id = UserCheckResult::USER_NOT_LOGGED_IN;

            } else {

                $user_info = $dbm->getUserInfo($user_id);

                $profileTab = new ProfileTab($selfLink, $dbm, $user_id, $user_info);
                $repoTab = new RepoTab($dbm, $user_info->isTeacher ? 0 : $user_id);
                $logoutTab = new LogoutTab();

                // if update profile form submitted
                if ($profileTab->isSubmitted()) {
                    $page = $profileTab->getTabInfo()->page;
                    $profileTab->handleSubmit();
                }

                // if a teacher is logged in
                if ($user_info->isTeacher) {
                    $allTasksTab = new AllTasksTab($dbm);
                    $allStudentsTab = new AllStudentsTab($dbm);
                    $groupsTab = new GroupsTab($selfLink, $dbm);
                    
                    $tabHolder->addTab($allTasksTab);
                    $tabHolder->addTab($allStudentsTab);
                    $tabHolder->addTab($groupsTab);
                    $tabHolder->addTab($profileTab);
                    $tabHolder->addTab($repoTab);

                    if (($page === "edit_task" && isset($_GET['id'])) || EditTaskTab::isSubmitted_static()) {
                        $editTaskTab = new EditTaskTab($selfLink, $dbm, htmlentities($_GET['id']));
                        $page = $editTaskTab->getTabInfo()->page;
                        $tabHolder->addTab($editTaskTab);

                        if ($editTaskTab->isSubmitted())
                            $editTaskTab->handleSubmit();
                    }

                    // if new group form submitted
                    if ($groupsTab->isSubmitted()) {
                        $page = $groupsTab->getTabInfo()->page;
                        $groupsTab->handleSubmit();
                    }

                // if a student is logged in
                } else {
                    $tasksTab = new TasksTab($selfLink, $dbm, $user_id);

                    $tabHolder->addTab($tasksTab);
                    $tabHolder->addTab($profileTab);
                    $tabHolder->addTab($repoTab);
                }

                $tabHolder->addTab($logoutTab);
            }
        }

        // if not logged in
        if ($user_id < UserCheckResult::MIN_VALID_USER_ID && $user_id !== UserCheckResult::DEFAULT_ADMIN) {
            $tabHolder->addTab($authorizationTab);
            $tabHolder->addTab($registrationTab);
        }

        if ($showPage) {
            html_page_start();
            $tabHolder->displayByPage($page);
            html_page_end();
        }

        $dbm->close();
    }
?>
