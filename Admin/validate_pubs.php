<?php

/**
 * View Publication
 *
 * Given a publication id number this page shows most of the information about
 * the publication. It does not display the extra information which is hidden
 * and used only for the search function. It provides links to all the authors
 * that are included. If a user is logged in, then there is an option to edit
 * or delete the current publication.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class view_publication extends pdHtmlPage {
    private $debug = 0;
    protected $pub_id;
    protected $submit_pending;
    protected $submit;

    public function __construct() {
        parent::__construct('validate_publications');
        
        if ($this->loginError) return;
        
        $pub_ids = array();
        $q = $this->db->select('pub_pending', 'pub_id');
        
        if (count($q) == 0) {
            echo 'There are no publicaiton entries requiring validation.';
            return;
        }
        
        foreach ($q as $r) {
        	$pub = new pdPublication($r);
        	$pub_ids[] = $r->pub_id;
        }
        
        $pub_list =  pdPubList::create($this->db, array('pub_ids' => $pub_ids,
        												'sort'    => false));
        uasort($pub_list, array('pdPublication', 'pubsDateSortDesc'));
        
        echo "<h3>The following publications requrie validation</h3>\n",
            "Please view or edit each entry separately.<br/>\n";
        
        // add additional parameter to the view icon
        echo preg_replace('/pub_id=(\d+)/',
            'pub_id=${1}&submit_pending=true',
            displayPubList($this->db, $pub_list, true, -1, null, null, '../'));
    }
}

$page = new view_publication();
echo $page->toHtml();

?>
