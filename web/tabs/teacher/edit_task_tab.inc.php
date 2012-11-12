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
        $this->taskDataArray = $this->dbm->getTask($taskId);
        $this->assArray = array(array(), array());
        if (!$this->taskDataArray) {
            $this->errorInfo = "Could not get task data";
        } else {
            $this->errorInfo = "";
            $ass = $this->dbm->getAllAssignmentsForTask($taskId);
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
?>
<form method="post" action="<?php echo $this->formAction; ?>" enctype="multipart/form-data">
    <table>
        <tr>
            <td>Task name:</td>
            <td><input type="text" size="20" name="name" value="<?php echo $this->taskDataArray[1]; ?>"></td>
        </tr>
        <tr>
            <td>Description:</td>
            <td><textarea name="description" cols="50" rows="3"><?php echo $this->taskDataArray[2]; ?></textarea></td>
        </tr>
        <tr>
            <td>Task file:</td>
            <td><?php echo ($this->taskDataArray[3] != NULL) ? ("<a href=\"?page=download_file&id=" . $this->taskDataArray[3] . "&md5=" . $this->taskDataArray[7] .
                     "\" target=_blank>" . $this->taskDataArray[5]) . "</a>" : "-"; ?><br />Update with file: <input type="file" name="taskFile" /> (don't set to leave as it is)</td>
        </tr>
        <tr>
            <td>Student environment file:</td>
            <td><?php echo ($this->taskDataArray[4] != NULL) ? ("<a href=\"?page=download_file&id=" . $this->taskDataArray[4] . "&md5=" . $this->taskDataArray[10] .
                     "\" target=_blank>" . $this->taskDataArray[8]) . "</a>" : "-"; ?><br />Update with file: <input type="file" name="envFile" /> (don't set to leave as it is)</td>
        </tr>
        <tr>
            <td>Assign to groups:</td>
            <td><?php displayGroupsMultiSelect($this->dbm, $this->assArray[1]); ?></td>
        </tr>
        <tr>
            <td>Assign to students:</td>
            <td><?php displayStudentsMultiSelect($this->dbm, $this->assArray[0]); ?></td>
        </tr>
        <tr>
            <td colspan="2"><center><input type="submit" name="submitEditTask" value="Edit"></center></td>
        </tr>
    </table>
</form>
<?php
        }
        display_content_end_block();
    }

    public function isSubmitted() {
        return (isset($_POST['submitEditTask']) && isset($_POST['name']) && isset($_POST['description']) && isset($_FILES['taskFile']) && isset($_FILES['envFile']));
    }

    // TODO: GET RID OF THIS COPY OF FUNCTION FROM new_task_tab.php
    private function saveFileOrSetErrorInfo($fileFieldName, $fileDescription) {
        $file_id = SaveFileResult::ERR_NO_FILE;
        if ($this->errorInfo === "" && $_FILES[$fileFieldName]['error'] != UPLOAD_ERR_NO_FILE) {
            $file_id = $this->dbm->saveFile($_FILES[$fileFieldName]);
            if ($file_id === SaveFileResult::ERR_FILE_TOO_BIG) {
                $this->errorInfo = "Error while uploading $fileDescription: file too big, max allowed size is " . $this->dbm->maxUploadFileSize . " bytes";
            } else if ($file_id < SaveFileResult::MIN_VALID_FILE_ID) {
                $this->errorInfo = "Error while uploading $fileDescription [error: " . $file_id . "]";
            }
        }
        return $file_id;
    }

    public function handleSubmit() {
        if ($_POST['name'] == "") {
            $this->errorInfo = "Task name can't be empty";
        } else {
            //TODO: write update task code
            /*
            $task_file_id = $this->saveFileOrSetErrorInfo('taskFile', 'task file');
            $env_file_id = $this->saveFileOrSetErrorInfo('envFile', 'student environment file');  //TODO: if error happens here, delete task file from db

            // if still no error
            if ($this->errorInfo == "") {
                if ($this->dbm->addNewTask($_POST['name'], $_POST['description'], $task_file_id, $env_file_id, $_POST['groupIds'], $_POST['studentIds'])) {
                    $this->successInfo = "Task added successfully";
                } else {
                    $this->errorInfo = "Database query error while adding task";
                }
            } else {
                $this->oldNameValue = $_POST['name'];
                $this->oldDescriptionValue = $_POST['description'];
            }
            */
        }
    }
}

?>
