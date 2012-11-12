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
        <td><b>Assigned tasks</b><br /><font size=2>g - by group, s - by student</font></td>
        <td></td>
    </tr>
<?php
        if ($students = $this->dbm->getAllStudents()) {
            foreach ($students as $student) {
                $tasks = $this->dbm->getAllTasksForStudent($student['id']);
                $strTasks = "";
                foreach ($tasks as $task) {
                    $strTasks .= '<a href="?page=edit_task&id=' . $task[0] . '">' . $task[1] . '</a> ' . (($task[2] == 1) ? '(g)' : '(s)') . '; ';
                }
?>
    <tr>
        <td><?php echo $student['firstName'] . ' ' . $student['lastName']; ?></td>
        <td><?php echo ($student['name'] != NULL) ? $student['name'] : "<i>&lt;not set&gt;</i>"; ?></td>
        <td><font size=2><?php echo substr($strTasks, 0, -2); ?></font></td>
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
