<?php

require_once('abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class RegistrationTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;
    private $userInfo;

    function __construct($formAction, DatabaseManager &$dbm) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
        $this->userInfo = new UserInfo("", "", "", "", "", "", 0, "");
    }

    public function getTabInfo() {
        return new TabInfo("Registration", "register");
    }

    public function displayContent() {
        display_content_start_block();
        display_error_or_info_if_any($this->errorInfo, $this->successInfo);
?>
<form method="post" action="<?php echo $this->formAction; ?>">
    <table>
        <tr>
            <td>Login:</td>
            <td><input type="text" size="20" name="login" value="<?php echo $this->userInfo->login; ?>"></td>
        </tr>
        <tr>
            <td>First name:</td>
            <td><input type="text" size="20" name="firstName" value="<?php echo $this->userInfo->firstName; ?>"></td>
        </tr>
        <tr>
            <td>Last name:</td>
            <td><input type="text" size="20" name="lastName" value="<?php echo $this->userInfo->lastName; ?>"></td>
        </tr>
        <tr>
            <td>Group number:</td>
            <td><input type="text" size="20" name="groupNumber" value="<?php echo $this->userInfo->groupNumber; ?>"></td>
        </tr>
        <tr>
            <td>E-mail:</td>
            <td><input type="text" size="20" name="email" value="<?php echo $this->userInfo->email; ?>"></td>
        </tr>
        <tr>
            <td>Password:</td>
            <td><input type="password" size="20" name="password"></td>
        </tr>
        <tr>
            <td>Repeat password:</td>
            <td><input type="password" size="20" name="password2"></td>
        </tr>
        <tr>
            <td colspan="2"><center><input type="submit" name="submitRegister" value="Register"></center></td>
        </tr>
    </table>
</form>
<?php
        display_content_end_block();
    }
    
    public function isSubmitted() {
        return (isset($_POST['submitRegister']) && isset($_POST['login']) && isset($_POST['firstName']) && isset($_POST['lastName']) &&
                isset($_POST['groupNumber']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password2']));
    }
    
    public function handleSubmit() {
        $this->userInfo->login = $_POST['login'];
        $this->userInfo->firstName = $_POST['firstName'];
        $this->userInfo->lastName = $_POST['lastName'];
        $this->userInfo->groupNumber = $_POST['groupNumber'];
        $this->userInfo->email = $_POST['email'];

        $pwd = $_POST['password'];
        if ($pwd !== $_POST['password2']) {
            $this->errorInfo = "Passwords did not match";
        } else if ($this->userInfo->login === "") {
            $this->errorInfo = "Empty login not allowed";
        } else if ($this->userInfo->firstName === "") {
            $this->errorInfo = "Empty first name not allowed";
        } else if ($this->userInfo->lastName === "") {
            $this->errorInfo = "Empty last name not allowed";
        } else if ($this->userInfo->groupNumber === "") {
            $this->errorInfo = "Empty group number not allowed";
        } else if ($this->userInfo->email === "") {
            $this->errorInfo = "Empty email not allowed";
        } else if ($pwd === "") {
            $this->errorInfo = "Empty password not allowed";

        } else {
            $regRes = $this->dbm->registerNewUser($this->userInfo->login, $this->userInfo->firstName, $this->userInfo->lastName, $this->userInfo->groupNumber,
                                                  $this->userInfo->email, md5($_POST['password']), false, getClientIP());
            if ($regRes === RegistrationResult::OK) {
                $this->successInfo = "Registered successfully";
                $this->userInfo = new UserInfo("", "", "", "", "", "", 0, "");
            } else if ($regRes === RegistrationResult::ERR_LOGIN_EXISTS) {
                $this->errorInfo = "Such login already registered";
            } else if ($regRes === RegistrationResult::ERR_EMAIL_EXISTS) {
                $this->errorInfo = "Such email already registered";
            } else if ($regRes === RegistrationResult::ERR_DB_ERROR) {
                $this->errorInfo = "Database query error";
            }
        }
    }
}

?>
