<?php

require_once('tabs/abstract_tab.inc.php');
require_once('style.inc.php');
require_once('database_manager.inc.php');

class EditStudentTab extends AbstractTab {
    private $studentId;

    function __construct($studentId) {
        $this->studentId = $studentId;
    }

    public function getTabInfo() {
        return new TabInfo("Edit student", "edit_student&id=" . $this->studentId);
    }

    public function displayContent() {
        display_content('<p>Check System Web Client author thought that we don\'t need this tab... But it can be easily created</p>');
    }

    public function isSubmitted() {
        return false;
    }

    public function handleSubmit() {
    }
}

?>
