<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class RepoTab extends AbstractTab {
    private $dbm;
    private $userId;

    function __construct(DatabaseManager &$dbm, $userId) {
        $this->dbm = $dbm;
        $this->userId = $userId;
    }

    public function getTabInfo() {
        return new TabInfo("Repo", "repo");
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
<p><b>Information</b></p>
<p>Your repository: <i><?php echo htmlentities($this->dbm->getGitAddress($this->userId)); ?></i></p>
<br />
<p><b>Operations</b></p>
<table id="infoTable">
    <tr>
        <th>Requested by</th>
        <th>For</th>
        <th>Operation</th>
        <th>Created</th>
        <th>Processed</th>
        <th>Status</th>
        <th>Message</th>
    </tr>
<?php
        if ($ops = $this->dbm->getOperations($this->userId)) {
            $i = 0;
            foreach ($ops as $op) {
?>
    <?php tr($i); ?>
        <td><font size=2><?php echo $op[0]; ?></font></td>
        <td><font size=2><?php echo $op[1]; ?></font></td>
        <td><?php echo $op['command']; ?></td>
        <td><font size=2><?php echo $op['created']; ?></font></td>
        <td><font size=2><?php echo $op['processed']; ?></font></td>
        <td><?php $this->displayStatusText($op['done']); ?></td>
        <td><font size=2><?php echo ($op['repo_worker_message'] != NULL) ? $op['repo_worker_message'] : ""; ?></font></td>
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
