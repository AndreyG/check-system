<?php

class Tabs {
    public static $FATAL_ERROR = array(array('Fatal Error', ''));
    public static $ANONYMOUS = array(array('Authorization', '?page=login'), array('Registration', '?page=register'));
    public static $STUDENT = array(array('Tasks', '?page=tasks'), array('Submit', '?page=submit'), array('Profile', '?page=profile'), array('Logout', '?page=logout'));
    public static $TEACHER = array(array('Add new task', '?page=new_task'), array('Profile', '?page=profile'), array('Logout', '?page=logout'));
};

// $activeTab - tab name or tab index
function display_tabs($activeTab, $tabsArray) {
    $tabsCount = count($tabsArray);
    if ($tabsCount <= 0)
        return;

    $tabsArray = array_reverse($tabsArray);
    echo "<ul id=\"lineTabs$tabsCount\">\n";
    $tabNumber = 0;
    foreach ($tabsArray as $tab) {
        echo "    <li><a href=\"$tab[1]\"";
        if ($tab[0] === $activeTab) {
            echo ' class="active"';
        } else if ($activeTab === $tabsCount - $tabNumber - 1) {
            echo ' class="active"';
        }
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

function display_error_or_info_if_any($error, $info) {
    if ($error != "") {
        echo "<p><b><font color=#cc0000>$error</font></b></p>\n";
    }
    if ($info != "") {
        echo "<p><b><font color=#009900>$info</font></b></p>\n";
    }
}

function display_login_page($form_action, $error) {
    display_content_start_block();
    display_error_or_info_if_any($error, "");
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
            <td colspan="2"><center><input type="submit" name="submitLogin" value="Login"></center></td>
        </tr>
    </table>
</form>
<?php
    display_content_end_block();
}

function display_register_page($form_action, $error, $info) {
    display_content_start_block();
    display_error_or_info_if_any($error, $info);
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
            <td colspan="2"><center><input type="submit" name="submitRegister" value="Register"></center></td>
        </tr>
    </table>
</form>
<?php
    display_content_end_block();
}

function display_profile_page($form_action, $error, $info, $user_info) {
    display_content_start_block();
    display_error_or_info_if_any($error, $info);
?>
<form method="post" action="<?php echo $form_action; ?>">
    <table>
        <tr>
            <td>Login:</td>
            <td><?php echo $user_info->login; ?></td>
        </tr>
        <tr>
            <td>Role:</td>
            <td><?php echo ($user_info->isTeacher ? "Teacher" : "Student"); ?></td>
        </tr>
        <tr>
            <td>First name:</td>
            <td><input type="text" size="20" name="firstName" value="<?php echo $user_info->firstName; ?>"></td>
        </tr>
        <tr>
            <td>Last name:</td>
            <td><input type="text" size="20" name="lastName" value="<?php echo $user_info->lastName; ?>"></td>
        </tr>
<?php
    if (!$user_info->isTeacher) {
?>
        <tr>
            <td>Group number:</td>
            <td><input type="text" size="20" name="groupNumber" value="<?php echo $user_info->groupNumber; ?>"></td>
        </tr>
<?php
    }
?>
        <tr>
            <td>E-mail:</td>
            <td><input type="text" size="20" name="email" value="<?php echo $user_info->email; ?>"></td>
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

function display_new_task_page($form_action, $error, $info, $old_description_value) {
    display_content_start_block();
    display_error_or_info_if_any($error, $info);
?>
<form method="post" action="<?php echo $form_action; ?>" enctype="multipart/form-data">
    <table>
        <tr>
            <td>Task name:</td>
            <td><input type="text" size="20" name="name"></td>
        </tr>
        <tr>
            <td>Description:</td>
            <td><textarea name="description" cols="50" rows="3"><?php echo $old_description_value; ?></textarea></td>
        </tr>
        <tr>
            <td>Task file:</td>
            <td><input type="file" name="taskFile" /> (optional)</td>
        </tr>
        <tr>
            <td>Student environment file:</td>
            <td><input type="file" name="envFile" /> (optional)</td>
        </tr>
        <tr>
            <td colspan="2"><center><input type="submit" name="submitNewTask" value="Add new task"></center></td>
        </tr>
    </table>
</form>
<?php
    display_content_end_block();
}

?>
