<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class ProfileTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;
    private $userId;
    private $userInfo;
    private $publicKey;

    function __construct($formAction, DatabaseManager &$dbm, $userId, UserInfo $userInfo, $gitolite_admin_repo_path) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
        $this->userId = $userId;
        //$this->userInfo = $this->dbm->getUserInfo($this->userId);
        $this->userInfo = $userInfo;
        $pubkeyFilename = $gitolite_admin_repo_path . "/keydir/u" . $userId . ".pub";
        $this->publicKey = file_exists($pubkeyFilename) ? file_get_contents($pubkeyFilename) : "{could not load public key, but still may be able to update it}";
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
            <td>Group:</td>
            <td><?php displayGroupsSelect($this->dbm, $this->userInfo->groupId); ?></td>
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
            <td><input type="password" size="20" name="newPassword"> (leave empty if you don't need to change it)</td>
        </tr>
        <tr>
            <td>Repeat new password:</td>
            <td><input type="password" size="20" name="newPassword2"></td>
        </tr>
        <tr>
            <td>Current password:</td>
            <td><input type="password" size="20" name="password"> (required for updating profile)</td>
        </tr>
        <tr>
            <td>Public key for Git:</td>
            <td><textarea name="publickey" cols="50" rows="10"><?php echo isset($_POST['publickey']) ? $_POST['publickey'] : $this->publicKey; ?></textarea> (won't update immediately)</td>
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
                isset($_POST['newPassword']) && isset($_POST['newPassword2']) && isset($_POST['password']) && ($this->userInfo->isTeacher || isset($_POST['groupId'])));
    }

    public function handleSubmit() {
        $this->userInfo->firstName = $_POST['firstName'];
        $this->userInfo->lastName = $_POST['lastName'];
        if (!$this->userInfo->isTeacher)
            $this->userInfo->groupId = $_POST['groupId'];
        $this->userInfo->email = $_POST['email'];

        if (md5($_POST['password']) !== $this->userInfo->md5) {
            $this->errorInfo = "Current password incorrect";
        } else if ($_POST['newPassword'] !== "" && $_POST['newPassword'] !== $_POST['newPassword2']) {
            $this->errorInfo = "New passwords did not match";
        } else if ($_POST['firstName'] === "") {
            $this->errorInfo = "Empty first name not allowed";
        } else if ($_POST['lastName'] === "") {
            $this->errorInfo = "Empty last name not allowed";
        } else if ($_POST['email'] === "") {
            $this->errorInfo = "Empty email not allowed";

        } else {
            $updRes = $this->dbm->updateUserInfo($this->userId, $this->userInfo->firstName, $this->userInfo->lastName, $this->userInfo->groupId,
                              $this->userInfo->email, ($_POST['newPassword'] != "") ? md5($_POST['newPassword']) : $this->userInfo->md5, $this->userInfo->isTeacher);
            if ($updRes === UpdateUserResult::OK) {
                $this->successInfo = "Profile updated successfully";
                $this->userInfo = $this->dbm->getUserInfo($this->userId);
                $_SESSION['md5'] = $this->userInfo->md5;

                if ($_POST['publickey'] !== $this->publicKey) {
                    if ($this->dbm->newRepoOperation($this->userId, $this->userId, 'newpubkey', array('u' . $this->userId, $_POST['publickey']))) {
                        $this->successInfo .= ", public key queued for update";
                    } else {
                        $this->errorInfo = "Could not queue public key for update";
                    }
                }

            } else if ($updRes === UpdateUserResult::ERR_EMAIL_EXISTS) {
                $this->errorInfo = "Such email already registered";
            } else if ($updRes === UpdateUserResult::ERR_INVALID_GROUP) {
                $this->errorInfo = "Hacking attempt? No such group";
            } else if ($updRes === UpdateUserResult::ERR_DB_ERROR) {
                $this->errorInfo = "Database query error";
            }
        }
    }
}

?>
