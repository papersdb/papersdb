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
            736, 737, 807, 828, 842, 858, 870, 871, 874, 875, 876, 877, 879, 
            894, 895, 896, 898, 900, 901, 906, 907, 908, 910, 911, 912, 914, 
            915, 916, 917, 921, 922, 923, 925, 926, 927, 928, 929, 930, 936, 
            941);
		$additional = array();
		foreach ($pub_ids as $pub_id) {
			$additional[$pub_id] = $pub_id;
		}
        
        $pub_list =  pdPubList::create($this->db, array('pub_ids' => $pub_ids,
        												'sort'    => false));
        uasort($pub_list, array('pdPublication', 'pubsDateSortDesc'));
        
        echo $this->displayPubList($pub_list, true, -1, $additional);
    }
}

$page = new publist_citations();
echo $page->toHtml();
?>