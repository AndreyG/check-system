<?php

require_once('abstract_tab.inc.php');
require_once('style.inc.php');

class TabHolder {
    private $tabInfos;
    
    function __construct() {
        $this->tabInfos = array();
    }
    
    public function addTab(AbstractTab &$tab) {
        array_push($this->tabInfos, $tab->getTabInfo());
    }
    
    public function display(AbstractTab &$currentTab) {
        displayTabs($currentTab->getTabInfo(), $this->tabInfos);
        $currentTab->displayContent();
    }
}

?>
