<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class EditTaskTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;
    private $taskId;
    private $taskDataArray;
    private $assArray;

    function __construct($formAction, DatabaseManager &$dbm, $taskId) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->successInfo = "";
        $this->taskId = $taskId;
        $this->getTaskDataFromDB();
    }

    private function getTaskDataFromDB() {
        $this->taskDataArray = $this->dbm->getTask($this->taskId);
        $this->assArray = array(array(), array());
        if (!$this->taskDataArray) {
            $this->errorInfo = "Could not get task data";
        } else {
            $this->errorInfo = "";
            $ass = $this->dbm->getAllAssignmentsForTask($this->taskId);
            foreach ($ass as $as) {
                array_push($this->assArray[$as[2]], $as[0]);
            }
        }
    }

    public function getTabInfo() {
        return new TabInfo("Edit task", "edit_task&id=" . $this->taskId);
    }

    public function displayContent() {
        display_content_start_block();
        display_error_or_info_if_any($this->errorInfo, $this->successInfo);
        if ($this->taskDataArray) {
            $i = 0;
?>
<form method="post" action="<?php echo $this->formAction . '?' . $this->getTabInfo()->page; ?>" enctype="multipart/form-data">
    <table id="submitTable">
        <?php tr($i); ?>
            <td>Task name:</td>
            <td><?php echo $this->taskDataArray['name']; ?></td>
        </tr>
        <?php tr($i); ?>
            <td>Description:</td>
            <td><textarea name="description" cols="50" rows="3"><?php echo $this->taskDataArray['description']; ?></textarea></td>
        </tr>
        <?php tr($i); ?>
            <td>Assign to groups:</td>
            <td><?php displayGroupsMultiSelect($this->dbm, $this->assArray[1]); ?></td>
        </tr>
        <?php tr($i); ?>
            <td>Assign to students:</td>
            <td><?php displayStudentsMultiSelect($this->dbm, $this->assArray[0]); ?></td>
        </tr>
        <?php tr($i); ?>
            <td colspan="2"><center><input type="submit" name="submitEditTask" value="Edit"></center></td>
        </tr>
    </table>
</form>
<?php
        }
        display_content_end_block();
    }

    public function isSubmitted() {
        return $this->isSubmitted_static();
    }

    public static function isSubmitted_static() {
        return (isset($_POST['submitEditTask']) && isset($_POST['description']));
    }

    private function saveSubmitValues() {
        $this->taskDataArray['description'] = $_POST['description'];
        $this->assArray[1] = getPostArray('groupIds');
        $this->assArray[0] = getPostArray('studentIds');
    }

    public function handleSubmit() {
        if ($this->dbm->updateTask($this->taskId, $_POST['description'], getPostArray('groupIds'), getPostArray('studentIds'))) {
            $this->successInfo = "Task edited successfully";
            $this->getTaskDataFromDB();
        } else {
            $this->errorInfo = "Database query error while editing task";
            $this->saveSubmitValues();
        }
    }
}

?>
