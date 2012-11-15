<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class RegistrationTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;
    private $userInfo;
    private $regAvaliable;
    private $publicKey;

    function __construct($formAction, DatabaseManager &$dbm) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
        $this->userInfo = new UserInfo("", "", "", 0, "", "", false, "");
        $this->regAvaliable = $this->dbm->checkIfGroupsExist();
        if (!$this->regAvaliable) {
            $this->errorInfo = "Registration unavaliable! No groups in database.";
            $this->successInfo = "Default admin should authorize and create groups.<br />Default admin's login and password are equal to login and password to database. See: <i>settings.inc.php</i>.";
        }
        $this->publicKey = "";
    }

    public function getTabInfo() {
        return new TabInfo("Registration", "register");
    }

    public function displayContent() {
        display_content_start_block();
        display_error_or_info_if_any($this->errorInfo, $this->successInfo);
        if ($this->regAvaliable) {
            $i = 0;
?>
<form method="post" action="<?php echo $this->formAction; ?>">
    <table id="submitTable">
        <?php tr($i); ?>
            <td>Login:</td>
            <td><input type="text" size="20" name="login" value="<?php echo $this->userInfo->login; ?>"></td>
        </tr>
        <?php tr($i); ?>
            <td>First name:</td>
            <td><input type="text" size="20" name="firstName" value="<?php echo $this->userInfo->firstName; ?>"></td>
        </tr>
        <?php tr($i); ?>
            <td>Last name:</td>
            <td><input type="text" size="20" name="lastName" value="<?php echo $this->userInfo->lastName; ?>"></td>
        </tr>
        <?php tr($i); ?>
            <td>Group:</td>
            <td><?php displayGroupsSelect($this->dbm, $this->userInfo->groupId); ?></td>
        </tr>
        <?php tr($i); ?>
            <td>E-mail:</td>
            <td><input type="text" size="20" name="email" value="<?php echo $this->userInfo->email; ?>"></td>
        </tr>
        <?php tr($i); ?>
            <td>Password:</td>
            <td><input type="password" size="20" name="password"></td>
        </tr>
        <?php tr($i); ?>
            <td>Repeat password:</td>
            <td><input type="password" size="20" name="password2"></td>
        </tr>
        <?php tr($i); ?>
            <td>Public key for Git:</td>
            <td><textarea name="publickey" cols="90" rows="7"><?php echo $this->publicKey; ?></textarea></td>
        </tr>
        <?php tr($i); ?>
            <td colspan="2"><center><input type="submit" name="submitRegister" value="Register"></center></td>
        </tr>
    </table>
</form>
<?php
        }
        display_content_end_block();
    }

    public function isSubmitted() {
        if (!$this->regAvaliable)
            return false;

        return (isset($_POST['submitRegister']) && isset($_POST['login']) && isset($_POST['firstName']) && isset($_POST['lastName']) &&
                isset($_POST['groupId']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password2']) && isset($_POST['publickey']));
    }

    public function handleSubmit() {
        if (!$this->regAvaliable)
            return;

        $this->userInfo->login = $_POST['login'];
        $this->userInfo->firstName = $_POST['firstName'];
        $this->userInfo->lastName = $_POST['lastName'];
        $this->userInfo->groupId = $_POST['groupId'];
        $this->userInfo->email = $_POST['email'];
        $this->publicKey = $_POST['publickey'];

        $pwd = $_POST['password'];
        if ($pwd !== $_POST['password2']) {
            $this->errorInfo = "Passwords did not match";
        } else if ($this->userInfo->login === "") {
            $this->errorInfo = "Empty login not allowed";
        } else if ($this->userInfo->firstName === "") {
            $this->errorInfo = "Empty first name not allowed";
        } else if ($this->userInfo->lastName === "") {
            $this->errorInfo = "Empty last name not allowed";
        } else if ($this->userInfo->email === "") {
            $this->errorInfo = "Empty email not allowed";
        } else if ($pwd === "") {
            $this->errorInfo = "Empty password not allowed";

        } else {
            $regRes = $this->dbm->registerNewUser($this->userInfo->login, $this->userInfo->firstName, $this->userInfo->lastName, $this->userInfo->groupId,
                                                  $this->userInfo->email, md5($_POST['password']), false, getClientIP(), $this->publicKey);
            if ($regRes === RegistrationResult::OK) {
                $this->successInfo = "Registered successfully";
                $this->userInfo = new UserInfo("", "", "", "", "", "", 0, "");
                $this->publicKey = "";
            } else if ($regRes === RegistrationResult::ERR_LOGIN_EXISTS) {
                $this->errorInfo = "Such login already registered";
            } else if ($regRes === RegistrationResult::ERR_EMAIL_EXISTS) {
                $this->errorInfo = "Such email already registered";
            } else if ($regRes === RegistrationResult::ERR_INVALID_GROUP) {
                $this->errorInfo = "Hacking attempt? No such group";
            } else if ($regRes === RegistrationResult::ERR_DB_ERROR) {
                $this->errorInfo = "Database query error";
            }
        }
    }
}

?>
