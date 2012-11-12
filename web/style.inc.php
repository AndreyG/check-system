<?php

function getClientIP() {
    $client_ip = ( !empty($HTTP_SERVER_VARS['REMOTE_ADDR']) ) ? $HTTP_SERVER_VARS['REMOTE_ADDR'] : ( ( !empty($HTTP_ENV_VARS['REMOTE_ADDR']) ) ? $HTTP_ENV_VARS['REMOTE_ADDR'] : getenv('REMOTE_ADDR') );
    return $client_ip;
}

function html_page_start() {
?>
<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Check System Web Client</title>
</head>
<body>

<h1 align=center>Check System Web Client</h1>

<div id="wrap">
<?php
}

function html_page_end() {
?>
</div>

</body>
</html>
<?php
}

// $activeTab - ref to current tab; $tabs - array of tabs
function displayTabs(AbstractTab &$activeTab, $tabs) {
    $tabsCount = count($tabs);
    if ($tabsCount <= 0)
        return;

    $tabs = array_reverse($tabs);
    echo "<ul id=\"lineTabs$tabsCount\">\n";
    $tabNumber = 0;
    foreach ($tabs as &$tab) {
        $tabInfo = $tab->getTabInfo();
        if ($tabInfo->page === "") {
            echo '    <li><a href=""';
        } else {
            echo '    <li><a href="?page=' . $tabInfo->page . '"';
        }
        if ($tabInfo->title === $activeTab->getTabInfo()->title && $tabInfo->page === $activeTab->getTabInfo()->page) {
            echo ' class="active"';
        }
        echo ">" . $tabInfo->title . "</a></li>\n";
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

function displayGroupsSelect(DatabaseManager &$dbm, $selectedGroupId = -1) {
    if ($groups = $dbm->getAllGroups()) {
        echo '<select name="groupId">';
        foreach ($groups as $group) {
            echo '<option ' . (($selectedGroupId == $group['id']) ? 'selected ' : '') . 'value="' . $group['id'] . '">' . $group['name'] . '</option>';
        }
        echo '</select>';
    } else {
        echo '<p><b><font color=#cc0000>Fatal error: could not make groups select object</font></b></p>';
    }
}

function displayGroupsMultiSelect(DatabaseManager &$dbm, $selectedGroups = array()) {
    if ($groups = $dbm->getAllGroups()) {
        /*
        echo '<select size=7 multiple name="groupIds[]">';
        foreach ($groups as $group) {
            echo '<option ' . (in_array($group['id'], $selectedGroups) ? 'selected ' : '') . 'value="' . $group['id'] . '">' . $group['name'] . '</option>';
        }
        echo '</select> (hold Ctrl to use multiselection)';
        */

        foreach ($groups as $group) {
            echo '<input type="checkbox" name="groupIds[]" value="' . $group['id'] . '"' . (in_array($group['id'], $selectedGroups) ? ' checked' : '') .
                 '>' . $group['name'] . '</input><br />';
        }
    } else {
        echo '<p><b><font color=#cc0000>Fatal error: could not make groups multiselect object</font></b></p>';
    }
}

function displayStudentsMultiSelect(DatabaseManager &$dbm, $selectedStudents = array()) {
    if ($students = $dbm->getAllStudents()) {
        /*
        echo '<select size=11 multiple name="studentIds[]">';
        foreach ($students as $student) {
            echo '<option ' . (in_array($student['id'], $selectedStudents) ? 'selected ' : '') . 'value="' . $student['id'] . '">' .
                     $student['firstName'] . ' ' . $student['lastName'] . ' [' . $student['name'] . ']</option>';
        }
        echo '</select> (hold Ctrl to use multiselection)';
        */

        foreach ($students as $student) {
            echo '<input type="checkbox" name="studentIds[]" value="' . $student['id'] . '"' . (in_array($student['id'], $selectedStudents) ? ' checked' : '') .
                 '>' . $student['firstName'] . ' ' . $student['lastName'] . ' [' . $student['name'] . ']</input><br />';
        }
    } else {
        echo '<p><b><font color=#cc0000>Fatal error: could not make students multiselect object</font></b></p>';
    }
}

?>
