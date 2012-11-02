<?php

class Tabs {
    public static $FATAL_ERROR = array(array('Fatal Error', ''));
    public static $ANONYMOUS = array(array('Authorization', '?page=login'), array('Registration', '?page=register'));
    public static $STUDENT = array(array('Tasks', '?page=tasks'));
    #public static TEACHER = array();
};

function display_tabs($activeTab, $tabsArray) {
    $tabsCount = count($tabsArray);
    if ($tabsCount <= 0)
        return;
    
    $tabsArray = array_reverse($tabsArray);
    $activeTab = $tabsCount - $activeTab - 1;
    echo "<ul id=\"lineTabs$tabsCount\">\n";
    $tabNumber = 0;
    foreach ($tabsArray as $tab) {
        echo "    <li><a href=\"$tab[1]\"";
        if ($activeTab == $tabNumber)
            echo ' class="active"';
        echo ">$tab[0]</a></li>\n";
        ++$tabNumber;
    }
    echo "</ul>\n";
}

function display_content_start_block() {
    echo "<div id=\"content\">\n";
}

function display_content_end_block() {
    echo "\n</div>\n";
}

function display_content($content) {
    display_content_start_block();
    echo $content;
    display_content_end_block();
}

function display_login_form($form_action, $error) {
    display_tabs(0, Tabs::$ANONYMOUS);
    display_content_start_block();
    if ($error != "") {
        echo "<p><b id=\"error\">$error</b></p>\n";
    }
?>
<form method="post" action="<?php echo $form_action; ?>">
    <table>
        <tr>
            <td>Login:</td>
            <td><input type="text" size="20" name="login"></td>
        </tr>
        <tr>
            <td>Password:</td>
            <td><input type="password" size="20" name="password"></td>
        </tr>
        <tr>
            <td colspan="2"><center><input type="submit" size="20" name="submitLogin" value="Login"></center></td>
        </tr>
    </table>
</form>
<?php
    display_content_end_block();
}

function display_register_form($form_action, $error, $info) {
    display_tabs(1, Tabs::$ANONYMOUS);
    display_content_start_block();
    if ($error != "") {
        echo "<p><b id=\"error\">$error</b></p>\n";
    }
    if ($info != "") {
        echo "<p><b id=\"info\">$info</b></p>\n";
    }
?>
<form method="post" action="<?php echo $form_action; ?>">
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
            <td colspan="2"><center><input type="submit" size="20" name="submitRegister" value="Register"></center></td>
        </tr>
    </table>
</form>
<?php
    display_content_end_block();
}

?>
