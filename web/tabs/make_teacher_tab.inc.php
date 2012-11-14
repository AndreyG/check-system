<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class MakeTeacherTab extends AbstractTab {
    private $dbm;
    private $errorInfo;
    private $successInfo;

    function __construct(DatabaseManager &$dbm) {
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
        if (isset($_GET['id'])) {
            if ($this->dbm->makeTeacher($_GET['id']))
                $this->successInfo = "New teacher made";
            else
                $this->errorInfo = "Unable to make new teacher";
        }
    }

    public function getTabInfo() {
        return new TabInfo("Make teacher", "make_teacher");
    }

    public function displayContent() {
        display_content_start_block();
        display_error_or_info_if_any($this->errorInfo, $this->successInfo);
?>
<table border=1>
    <tr>
        <td><b>Name</b></td>
        <td></td>
    </tr>
<?php
        if ($students = $this->dbm->getAllStudents()) {
            foreach ($students as $student) {
?>
    <tr>
        <td><?php echo $student['firstName'] . ' ' . $student['lastName']; ?></td>
        <td><a href="?page=make_teacher&id=<?php echo $student['id']; ?>">Make teacher</a></td>
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
