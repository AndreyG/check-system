<?php

require_once('abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class ProfileTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;
    private $userId;
    private $userInfo;

    function __construct($formAction, DatabaseManager &$dbm, $userId, UserInfo $userInfo) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
        $this->userId = $userId;
        //$this->userInfo = $this->dbm->getUserInfo($this->userId);
        $this->userInfo = $userInfo;
    }

    public function getTabInfo() {
        return new TabInfo("Profile", "profile");
    }

    public function displayContent() {
        display_content_start_block();
        display_error_or_info_if_any($this->errorInfo, $this->successInfo);
?>
<form method="post" action="<?php echo $this->formAction; ?>">
    <table>
        <tr>
            <td>Login:</td>
            <td><?php echo $this->userInfo->login; ?></td>
        </tr>
        <tr>
            <td>Role:</td>
            <td><?php echo ($this->userInfo->isTeacher ? "Teacher" : "Student"); ?></td>
        </tr>
        <tr>
            <td>First name:</td>
            <td><input type="text" size="20" name="firstName" value="<?php echo $this->userInfo->firstName; ?>"></td>
        </tr>
        <tr>
            <td>Last name:</td>
            <td><input type="text" size="20" name="lastName" value="<?php echo $this->userInfo->lastName; ?>"></td>
        </tr>
<?php
        if (!$this->userInfo->isTeacher) {
?>
        <tr>
            <td>Group number:</td>
            <td><input type="text" size="20" name="groupNumber" value="<?php echo $this->userInfo->groupNumber; ?>"></td>
        </tr>
<?php
        }
?>
        <tr>
            <td>E-mail:</td>
            <td><input type="text" size="20" name="email" value="<?php echo $this->userInfo->email; ?>"></td>
        </tr>
        <tr>
            <td>New password:</td>
            <td><input type="password" size="20" name="password"> (leave empty if you don't need to change it)</td>
        </tr>
        <tr>
            <td>Repeat new password:</td>
            <td><input type="password" size="20" name="password2"></td>
        </tr>
        <tr>
            <td>Current password:</td>
            <td><input type="password" size="20" name="curPassword"> (required for updating profile)</td>
        </tr>
        <tr>
            <td colspan="2"><center><input type="submit" name="submitUpdateProfile" value="Update profile"></center></td>
        </tr>
    </table>
</form>
<?php
        display_content_end_block();
    }

    public function isSubmitted() {
        return (isset($_POST['submitUpdateProfile']) && isset($_POST['firstName']) && isset($_POST['lastName']) && isset($_POST['email']) &&
                isset($_POST['password']) && isset($_POST['password2']) && isset($_POST['curPassword']) && ($this->userInfo->isTeacher || isset($_POST['groupNumber'])));
    }

    public function handleSubmit() {
        $this->userInfo->firstName = $_POST['firstName'];
        $this->userInfo->lastName = $_POST['lastName'];
        if (!$this->userInfo->isTeacher)
            $this->userInfo->groupNumber = $_POST['groupNumber'];
        $this->userInfo->email = $_POST['email'];

        if (md5($_POST['curPassword']) != $this->userInfo->md5) {
            $this->errorInfo = "Current password incorrect";
        } else if ($_POST['password'] != "" && $_POST['password'] !== $_POST['password2']) {
            $this->errorInfo = "New passwords did not match";
        } else if ($_POST['firstName'] == "") {
            $this->errorInfo = "Empty first name not allowed";
        } else if ($_POST['lastName'] == "") {
            $this->errorInfo = "Empty last name not allowed";
        } else if (!$this->userInfo->isTeacher && $_POST['groupNumber'] == "") {
            $this->errorInfo = "Empty group number not allowed";
        } else if ($_POST['email'] == "") {
            $this->errorInfo = "Empty email not allowed";

        } else {
            $updRes = $this->dbm->updateUserInfo($this->userId, $this->userInfo->firstName, $this->userInfo->lastName, $this->userInfo->groupNumber,
                                            $this->userInfo->email, ($_POST['password'] != "") ? md5($_POST['password']) : $this->userInfo->md5);
            if ($updRes == UpdateUserResult::OK) {
                $this->successInfo = "Profile updated successfully";
                $this->userInfo = $this->dbm->getUserInfo($this->userId);
                $_SESSION['md5'] = $this->userInfo->md5;
            } else if ($updRes == UpdateUserResult::ERR_EMAIL_EXISTS) {
                $this->errorInfo = "Such email already registered";
            } else if ($updRes == UpdateUserResult::ERR_DB_ERROR) {
                $this->errorInfo = "Database query error";
            }
        }
    }
}
