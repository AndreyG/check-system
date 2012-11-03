<?php

require_once('abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class RegistrationTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;

    function __construct($formAction, DatabaseManager &$dbm) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
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
            <td><input type="text" size="20" name="login"></td>
        </tr>
        <tr>
            <td>First name:</td>
            <td><input type="text" size="20" name="firstName"></td>
        </tr>
        <tr>
            <td>Last name:</td>
            <td><input type="text" size="20" name="lastName"></td>
        </tr>
        <tr>
            <td>Group number:</td>
            <td><input type="text" size="20" name="groupNumber"></td>
        </tr>
        <tr>
            <td>E-mail:</td>
            <td><input type="text" size="20" name="email"></td>
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
        $pwd = $_POST['password'];
        if ($pwd != $_POST['password2']) {
            $this->errorInfo = "Passwords did not match";
        } else if ($_POST['login'] == "") {
            $this->errorInfo = "Empty login not allowed";
        } else if ($_POST['firstName'] == "") {
            $this->errorInfo = "Empty first name not allowed";
        } else if ($_POST['lastName'] == "") {
            $this->errorInfo = "Empty last name not allowed";
        } else if ($_POST['groupNumber'] == "") {
            $this->errorInfo = "Empty group number not allowed";
        } else if ($_POST['email'] == "") {
            $this->errorInfo = "Empty email not allowed";
        } else if ($_POST['password'] == "") {
            $this->errorInfo = "Empty password not allowed";

        } else {
            $regRes = $this->dbm->registerNewUser($_POST['login'], $_POST['firstName'], $_POST['lastName'], $_POST['groupNumber'],
                                                  $_POST['email'], md5($_POST['password']), false, getClientIP());
            if ($regRes == RegistrationResult::OK) {
                $this->successInfoInfo = "Registered successfully";
            } else if ($regRes == RegistrationResult::ERR_LOGIN_EXISTS) {
                $this->errorInfo = "Such login already registered";
            } else if ($regRes == RegistrationResult::ERR_EMAIL_EXISTS) {
                $this->errorInfo = "Such email already registered";
            } else if ($regRes == RegistrationResult::ERR_DB_ERROR) {
                $this->errorInfo = "Database query error";
            }
        }
    }
}
