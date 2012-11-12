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
        <td><b>Assigned to</b><br /><font size=2>G - groups, S - students</font></td>
        <td></td>
    </tr>
<?php
        if ($tasks = $this->dbm->getAllTasks()) {
            foreach ($tasks as $task) {
                $ass = $this->dbm->getAllAssignmentsForTask($task[0]);
                $strAss = '';
                $lastType = -1;
                foreach ($ass as $as) {
                    if ($lastType !== $as[2]) {
                        $lastType = $as[2];
                        if ($strAss !== '')
                            $strAss .= '<br />';
                        $strAss .= ($as[2] == 1 ? 'G: ' : 'S: ');
                    }
                    if ($as[2] == 1) {
                        $strAss .= $as[1] . '; ';
                    } else {
                        $strAss .= '<a href="?page=edit_student&id=' . $as[0] . '">' . $as[1] . '</a>; ';
                    }
                }
?>
    <tr>
        <td><?php echo $task[1]; ?></td>
        <td><font size=2><?php echo $task[2]; ?></font></td>
        <td><?php echo ($task[3] != NULL) ? ("<a href=\"?page=download_file&id=" . $task[3] . "&md5=" . $task[7]  . "\" target=_blank>" . $task[5]) . "</a>" : "-"; ?></td>
        <td><?php echo ($task[4] != NULL) ? ("<a href=\"?page=download_file&id=" . $task[4] . "&md5=" . $task[10] . "\" target=_blank>" . $task[8]) . "</a>" : "-"; ?></td>
        <td><font size=2><?php echo substr($strAss, 0, -2); ?></font></td>
        <td><a href="?page=edit_task&id=<?php echo $task[0]; ?>">Edit</a></td>
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
