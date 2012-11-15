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
<table id="infoTable">
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Task file</th>
        <th>Student env file</th>
        <th>Assignment type</th>
    </tr>
<?php
        if ($tasks = $this->dbm->getAllTasksForStudent($this->userId)) {
            $i = 0;
            foreach ($tasks as $task) {
?>
    <?php tr($i); ?>
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
