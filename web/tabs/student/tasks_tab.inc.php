<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class TasksTab extends AbstractTab {
    private $formAction;
    private $dbm;
    private $userId;

    function __construct($formAction, DatabaseManager &$dbm, $userId) {
        $this->formAction = $formAction;
        $this->dbm = $dbm;
        $this->userId = $userId;
    }

    public function getTabInfo() {
        return new TabInfo("Tasks", "tasks");
    }

    public function displayContent() {
        display_content_start_block();
?>
<table id="infoTable">
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Assignment type</th>
    </tr>
<?php
        if ($tasks = $this->dbm->getAllTasksForStudent($this->userId)) {
            $i = 0;
            foreach ($tasks as $task) {
?>
    <?php tr($i); ?>
        <td><?php echo $task[1]; ?></td>
        <td><font size=2><?php echo $task[2]; ?></font></td>
        <td><?php echo ($task[3] == 1) ? "group" : "personal"; ?></td>
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
