<?php

function getClientIP() {
    $client_ip = ( !empty($HTTP_SERVER_VARS['REMOTE_ADDR']) ) ? $HTTP_SERVER_VARS['REMOTE_ADDR'] : ( ( !empty($HTTP_ENV_VARS['REMOTE_ADDR']) ) ? $HTTP_ENV_VARS['REMOTE_ADDR'] : getenv('REMOTE_ADDR') );
    return $client_ip;
}

// $activeTab - ref to current tab; $tabsArray holds TabInfos
function displayTabs($activeTab, $tabsArray) {
    $tabsCount = count($tabsArray);
    if ($tabsCount <= 0)
        return;

    $tabsArray = array_reverse($tabsArray);
    echo "<ul id=\"lineTabs$tabsCount\">\n";
    $tabNumber = 0;
    foreach ($tabsArray as $tab) {
        if ($tab->page === "") {
            echo '    <li><a href=""';
        } else {
            echo '    <li><a href="?page=' . $tab->page . '"';
        }
        if ($tab->title === $activeTab->title && $tab->page === $activeTab->page) {
            echo ' class="active"';
        }
        echo ">" . $tab->title . "</a></li>\n";
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
