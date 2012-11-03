<?php

class Tabs {
    public static $FATAL_ERROR = array(array('Fatal Error', ''));
    public static $ANONYMOUS = array(array('Authorization', '?page=login'), array('Registration', '?page=register'));
    public static $STUDENT = array(array('Tasks', '?page=tasks'), array('Submit', '?page=submit'), array('Profile', '?page=profile'), array('Logout', '?page=logout'));
    public static $TEACHER = array(array('Add new task', '?page=new_task'), array('Profile', '?page=profile'), array('Logout', '?page=logout'));
};

function getClientIP() {
    $client_ip = ( !empty($HTTP_SERVER_VARS['REMOTE_ADDR']) ) ? $HTTP_SERVER_VARS['REMOTE_ADDR'] : ( ( !empty($HTTP_ENV_VARS['REMOTE_ADDR']) ) ? $HTTP_ENV_VARS['REMOTE_ADDR'] : getenv('REMOTE_ADDR') );
    return $client_ip;
}

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
