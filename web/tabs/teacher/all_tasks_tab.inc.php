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
<table id="infoTable">
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Assigned to<br /><font size=2>G - groups, S - students</font></th>
        <th></th>
    </tr>
<?php
        if ($tasks = $this->dbm->getAllTasks()) {
            $i = 0;
            foreach ($tasks as $task) {
                $ass = $this->dbm->getAllAssignmentsForTask($task['id']);
                $strAss = '';
                $lastType = -1;
                foreach ($ass as $as) {
                    if ($lastType !== $as[2]) {
                        $lastType = $as[2];
                        if ($strAss !== '')
                            $strAss .= '<br />';
                        $strAss .= ($as[2] == 1 ? 'G: ' : 'S: ');
                    }
                    $strAss .= $as[1] . '; ';
                }
?>
    <?php tr($i); ?>
        <td><?php echo $task['name']; ?></td>
        <td><font size=2><?php echo $task['description']; ?></font></td>
        <td><font size=2><?php echo substr($strAss, 0, -2); ?></font></td>
        <td><a href="?page=edit_task&id=<?php echo $task['id']; ?>">Edit</a></td>
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
