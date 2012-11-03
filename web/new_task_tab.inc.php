<?php

require_once('abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class NewTaskTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $errorInfo;
    private $successInfo;
    private $oldDescriptionValue;

    function __construct($formAction, DatabaseManager &$dbm) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->errorInfo = "";
        $this->successInfo = "";
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
            <td><input type="text" size="20" name="name"></td>
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
            <td colspan="2"><center><input type="submit" name="submitNewTask" value="Add new task"></center></td>
        </tr>
    </table>
</form>
<?php
        display_content_end_block();
    }
    
    public function isSubmitted() {
        return (isset($_POST['submitNewTask']) && isset($_POST['name']) && isset($_POST['description']) && isset($_FILES['taskFile']) && isset($_FILES['envFile']));
    }
    
    public function handleSubmit() {
        if ($_POST['name'] == "") {
            $this->errorInfo = "Task name can't be empty";
            $this->oldDescriptionValue = $_POST['description'];
        } else {
            $task_file_id = false;
            $env_file_id = false;

            if ($_FILES['taskFile']['error'] != UPLOAD_ERR_NO_FILE) {
                $task_file_id = $dbm->saveFile($_FILES['taskFile']);
                if ($task_file_id === false) {
                    $this->errorInfo = "Error while uploading task file";
                    $this->oldDescriptionValue = $_POST['description'];
                }
            }
            if ($_FILES['envFile']['error'] != UPLOAD_ERR_NO_FILE) {
                $env_file_id = $dbm->saveFile($_FILES['envFile']);
                if ($env_file_id === false) {
                    $this->errorInfo = "Error while uploading student env file";
                    $this->oldDescriptionValue = $_POST['description'];
                }
            }
            
            // if still no error
            if ($this->errorInfo == "") {
                
            }
        }
    }
}

?>
