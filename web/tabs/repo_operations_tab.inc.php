<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class RepoOperationsTab extends AbstractTab {
    private $dbm;
    private $userId;

    function __construct(DatabaseManager &$dbm, $userId) {
        $this->dbm = $dbm;
        $this->userId = $userId;
    }

    public function getTabInfo() {
        return new TabInfo("Repo op-s", "repo_operations");
    }

    private function displayStatusText($status) {
        if ($status == 0) {
            echo '<font color="blue">queued</font>';
        } else if ($status == 1) {
            echo '<font color="green">done</font>';
        } else if ($status == 2) {
            echo '<font color="red">failed</font>';
        }
    }

    public function displayContent() {
        display_content_start_block();
?>
<p><b>Repository operations</b></p>
<table border=1>
    <tr>
        <td><b>Requested by</b></td>
        <td><b>For</b></td>
        <td><b>Operation</b></td>
        <td><b>Created</b></td>
        <td><b>Processed</b></td>
        <td><b>Status</b></td>
        <td><b>Message</b></td>
    </tr>
<?php
        if ($ops = $this->dbm->getOperations($this->userId)) {
            foreach ($ops as $op) {
?>
    <tr>
        <td><?php echo $op[0]; ?></td>
        <td><?php echo $op[1]; ?></td>
        <td><?php echo $op['command']; ?></td>
        <td><?php echo $op['created']; ?></td>
        <td><?php echo $op['processed']; ?></td>
        <td><?php $this->displayStatusText($op['done']); ?></td>
        <td><?php echo ($op['repo_worker_message'] != NULL) ? $op['repo_worker_message'] : ""; ?></td>
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
