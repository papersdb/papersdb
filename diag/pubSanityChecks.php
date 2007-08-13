<?php ;

// $Id: pubSanityChecks.php,v 1.1 2007/08/13 21:49:51 aicmltec Exp $

/**
 * Script that reports the publications with two PI's and also one PI and one
 * PDF.
 *
 * @package PapersDB
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
class pubSanityChecks extends pdHtmlPage {
    function pubSanityChecks() {
        parent::pdHtmlPage('sanity_checks');

        if ($this->loginError) return;

        echo '<h1>Publication Sanity Checks</h1>';

        $tier1 = array('UAI', 'AAAI', 'NIPS', 'ICML', 'IJCAI');
        $bad_pubs = array();

        // check for T1 pubs
        $all_pubs = new pdPubList($this->db);
        foreach ($all_pubs->list as $pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            foreach ($tier1 as $venue) {
                if (is_object($pub->venue)
                    && isset($pub->venue->title)
                    && (strpos($pub->venue->title, $venue) !== false)
                    && ($pub->rank_id != 1)) {
                    $bad_pubs[] = $pub->pub_id;
                }
            }
        }

        echo '<h2>Mislabelled Tier 1</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_pubs));
        echo $this->displayPubList($pub_list, true);
    }
}

$page = new pubSanityChecks();
echo $page->toHtml();

?>