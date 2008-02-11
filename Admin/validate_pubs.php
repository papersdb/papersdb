<?php ;

// $Id: validate_pubs.php,v 1.1 2008/02/11 22:20:58 loyola Exp $

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

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
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
        
        if ($this->db->numRows($q) == 0) {
            echo 'There are no publicaiton entries requiring validation.';
            return;
        }
        
        $r = $this->db->fetchObject($q);
        while ($r) {
        	$pub = new pdPublication($r);
        	$pub_ids[] = $r->pub_id;
        	$r = $this->db->fetchObject($q);
        }
        
        $pub_list =  pdPubList::create($this->db, array('pub_ids' => $pub_ids,
        												'sort'    => false));
        uasort($pub_list, array('pdPublication', 'pubsDateSortDesc'));
        
        echo "<h3>The following publications requrie validation</h3>\n",
            "Please view or edit each entry separately.<br/>\n";
        
        // add additional parameter to the view icon
        echo preg_replace('/pub_id=(\d+)/',
            'pub_id=${1}&submit_pending=true',
            $this->displayPubList($pub_list, true));
    }
}

$page = new view_publication();
echo $page->toHtml();

?>
