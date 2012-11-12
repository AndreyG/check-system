<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class GroupsTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;

    function __construct($formAction, DatabaseManager &$dbm) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
    }

    public function getTabInfo() {
        return new TabInfo("Groups", "groups");
    }

    public function displayContent() {
        display_content_start_block();
        display_error_or_info_if_any($this->errorInfo, $this->successInfo);
?>
<table border=1>
    <tr>
        <td><b>Group name</b></td>
        <td><b>Students</b></td>
        <td><b>Assigned tasks</b></td>
    </tr>
<?php
        if ($groups = $this->dbm->getAllGroups()) {
            foreach ($groups as $group) {
                $students = $this->dbm->getAllStudents($group['id']);
                $strStudents = "";
                foreach ($students as $student) {
                    $strStudents .= '<a href="?page=edit_student&id=' . $student['id'] . '">' . $student['firstName'] . ' ' . $student['lastName'] . '</a>; ';
                }
                $tasks = $this->dbm->getAllTasksForGroup($group['id']);
                $strTasks = "";
                foreach ($tasks as $task) {
                    $strTasks .= '<a href="?page=edit_task&id=' . $task['task_id'] . '">' . $task['name'] . '</a>; ';
                }
?>
    <tr>
        <td><?php echo $group['name'] ?></td>
        <td><font size=2><?php echo substr($strStudents, 0, -2); ?></font></td>
        <td><font size=2><?php echo substr($strTasks, 0, -2); ?></font></td>
    </tr>
<?php
            }
        }
?>
</table>
<br />
<br />
<b>Create new group</b>
<form method="post" action="<?php echo $this->formAction; ?>">
    <table>
        <tr>
            <td>Name:</td>
            <td><input type="text" size="20" name="groupName"></td>
        </tr>
        <tr>
            <td colspan="2"><center><input type="submit" name="submitCreateGroup" value="Create"></center></td>
        </tr>
    </table>
</form>
<?php
        display_content_end_block();
    }
    
    public function isSubmitted() {
        return (isset($_POST['submitCreateGroup']) && isset($_POST['groupName']));
    }
    
    public function handleSubmit() {
        if ($this->dbm->addGroup($_POST['groupName'])) {
            $this->successInfo = "New group added";
        } else {
            $this->errorInfo = "Could not add group";
        }
    }
}

?>
