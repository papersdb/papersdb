<?php

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

class publist_citations extends pdHtmlPage {
    public function __construct() {
        parent::__construct('publist_citations', 'Publication List Citations',
                           'diag/publist_citations.php', pdNavMenuItem::MENU_NEVER);

        if ($this->loginError) return;
        
        $pub_ids = array(
        	928, 910, 911, 912, 914, 736, 876, 877, 879, 916, 917, 925, 858,
			828, 895, 870, 926, 921, 915, 922, 923, 874, 894, 927, 807, 875);
		$additional = array();
		foreach ($pub_ids as $pub_id) {
			$additional[$pub_id] = $pub_id;
		}
        
        $pub_list =  pdPubList::create($this->db, array('pub_ids' => $pub_ids,
        												'sort'    => false));
        
        echo $this->displayPubList($pub_list, true, -1, $additional);
    }
}

$page = new publist_citations();
echo $page->toHtml();
?>