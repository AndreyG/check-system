<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class AllStudentsTab extends AbstractTab {
    private $dbm;

    function __construct(DatabaseManager &$dbm) {
        $this->dbm = $dbm;
    }

    public function getTabInfo() {
        return new TabInfo("All students", "all_students");
    }

    public function displayContent() {
        display_content_start_block();
?>
<table border=1>
    <tr>
        <td><b>Name</b></td>
        <td><b>Group</b></td>
        <td><b>Unsolved tasks</b></td>
        <td><b>Solved tasks</b></td>
        <td></td>
    </tr>
<?php
        if ($students = $this->dbm->getAllStudents()) {
            foreach ($students as $student) {
                $sTasks = $this->dbm->getAllTasksFor($student['id']);
                $strTasks = array("", "");  // unsolved / solved
                foreach ($sTasks as $sTask) {
                    $strTasks[$sTask[1]] .= '<a href="?page=edit_task&id=' . $sTask[0] . '">' . $sTask[2] . '</a>; ';
                }
?>
    <tr>
        <td><?php echo $student['firstName'] . ' ' . $student['lastName']; ?></td>
        <td><?php echo ($student['name'] != NULL) ? $student['name'] : "<i>&lt;not set&gt;</i>"; ?></td>
        <td><font size=2><?php echo substr($strTasks[0], 0, -2); ?></font></td>
        <td><font size=2><?php echo substr($strTasks[1], 0, -2); ?></font></td>
        <td><a href="?page=edit_student&id=<?php echo $student['id']; ?>">Edit</a></td>
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
