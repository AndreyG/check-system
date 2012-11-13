<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class TasksTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $userId;

    function __construct($formAction, DatabaseManager &$dbm, $userId) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->userId = $userId;
    }

    public function getTabInfo() {
        return new TabInfo("Tasks", "tasks");
    }

    public function displayContent() {
        display_content_start_block();
?>
<p><b>Connection with Git repository</b></p>
<form method="post" action="<?php echo $this->formAction; ?>">
    <table>
        <tr>
            <td>Address:</td>
            <td><input type="text" size="40" name="groupName"></td>
            <td>(something like <i>git@github.com:Tsar/tv_tuner_management.git</i>)</td>
        </tr>
        <tr>
            <td colspan="2"><center><input type="submit" name="submitCreateGroup" value="Update git link"></center></td>
        </tr>
    </table>
</form>
<br />
<table border=1>
    <tr>
        <td><b>Name</b></td>
        <td><b>Description</b></td>
        <td><b>Task file</b></td>
        <td><b>Student env file</b></td>
        <td><b>Assignment type</b></td>
    </tr>
<?php
        if ($tasks = $this->dbm->getAllTasksForStudent($this->userId)) {
            foreach ($tasks as $task) {
?>
    <tr>
        <td><?php echo $task[1]; ?></td>
        <td><font size=2><?php echo $task[2]; ?></font></td>
        <td><?php echo ($task[3] != NULL) ? ("<a href=\"?page=download_file&id=" . $task[3] . "&md5=" . $task[7]  . "\" target=_blank>" . $task[5]) . "</a>" : "-"; ?></td>
        <td><?php echo ($task[4] != NULL) ? ("<a href=\"?page=download_file&id=" . $task[4] . "&md5=" . $task[10] . "\" target=_blank>" . $task[8]) . "</a>" : "-"; ?></td>
        <td><?php echo ($task[11] == 1) ? "group" : "personal"; ?></td>
    </tr>
<?php
            }
        }
?>
</table>
<?php
        display_content_end_block();
    }

    public function isSubmitted() {
        false;
    }

    public function handleSubmit() {
    }
}

?>
