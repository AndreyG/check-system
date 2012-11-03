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

?>
