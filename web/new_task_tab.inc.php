<?php

require_once('abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class NewTaskTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;
    private $oldNameValue;
    private $oldDescriptionValue;

    function __construct($formAction, DatabaseManager &$dbm) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
        $this->oldNameValue = "";
        $this->oldDescriptionValue = "";
    }

    public function getTabInfo() {
        return new TabInfo("Add new task", "new_task");
    }

    public function displayContent() {
        display_content_start_block();
        display_error_or_info_if_any($this->errorInfo, $this->successInfo);
?>
<form method="post" action="<?php echo $this->formAction; ?>" enctype="multipart/form-data">
    <table>
        <tr>
            <td>Task name:</td>
            <td><input type="text" size="20" name="name" value="<?php echo $this->oldNameValue; ?>"></td>
        </tr>
        <tr>
            <td>Description:</td>
            <td><textarea name="description" cols="50" rows="3"><?php echo $this->oldDescriptionValue; ?></textarea></td>
        </tr>
        <tr>
            <td>Task file:</td>
            <td><input type="file" name="taskFile" /> (optional)</td>
        </tr>
        <tr>
            <td>Student environment file:</td>
            <td><input type="file" name="envFile" /> (optional)</td>
        </tr>
        <tr>
            <td colspan="2"><center><input type="submit" name="submitNewTask" value="Add"></center></td>
        </tr>
    </table>
</form>
<?php
        display_content_end_block();
    }
    
    public function isSubmitted() {
        return (isset($_POST['submitNewTask']) && isset($_POST['name']) && isset($_POST['description']) && isset($_FILES['taskFile']) && isset($_FILES['envFile']));
    }
    
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
            $task_file_id = $this->saveFileOrSetErrorInfo('taskFile', 'task file');
            $env_file_id = $this->saveFileOrSetErrorInfo('envFile', 'student environment file');  //TODO: if error happens here, delete task file from db

            // if still no error
            if ($this->errorInfo == "") {
                if ($this->dbm->addNewTask($_POST['name'], $_POST['description'], $task_file_id, $env_file_id)) {
                    $this->successInfo = "Task added successfully";
                } else {
                    $this->errorInfo = "Database query error while adding task";
                }
            } else {
                $this->oldNameValue = $_POST['name'];
                $this->oldDescriptionValue = $_POST['description'];
            }
        }
    }
}

?>
