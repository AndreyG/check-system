<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class AllTasksTab extends AbstractTab {
    private $dbm;

    function __construct(DatabaseManager &$dbm) {
        $this->dbm = $dbm;
    }

    public function getTabInfo() {
        return new TabInfo("All tasks", "all_tasks");
    }

    public function displayContent() {
        display_content_start_block();
?>
<table border=1>
    <tr>
        <td><b>Name</b></td>
        <td><b>Description</b></td>
        <td><b>Task file</b></td>
        <td><b>Student env file</b></td>
        <td><b>Assigned to</b></td>
    </tr>
<?php
        if ($tasks = $this->dbm->getAllTasks()) {
            foreach ($tasks as $task) {
?>
    <tr>
        <td><?php echo $task[1]; ?></td>
        <td><pre><?php echo $task[2]; ?></pre></td>
        <td><?php echo ($task[3] != NULL) ? ("<a href=\"?page=download_file&id=" . $task[3] . "&md5=" . $task[7]  . "\" target=_blank>" . $task[5]) . "</a>" : "-"; ?></td>
        <td><?php echo ($task[4] != NULL) ? ("<a href=\"?page=download_file&id=" . $task[4] . "&md5=" . $task[10] . "\" target=_blank>" . $task[8]) . "</a>" : "-"; ?></td>
        <td>-</td>
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
