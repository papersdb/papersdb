<?php ;

// $Id: pubSanityChecks.php,v 1.2 2007/08/16 16:31:41 aicmltec Exp $

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
    var $tab;
    var $valid_tabs = array('Rankings', 'Categories', 'Tier 1',
                            'Workshops');


    function pubSanityChecks() {
        parent::pdHtmlPage('sanity_checks');

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (!isset($this->tab))
            $this->tab = $this->valid_tabs[0];
        else if (!in_array($this->tab, $this->valid_tabs)) {
            $this->pageError = true;
            return;
        }

        echo $this->selMenu();
        echo '<h1>Publication Entries Sanity Checks</h1>';

        switch ($this->tab) {
            case $this->valid_tabs[0]:
                $this->venueRankings();
                break;

            case $this->valid_tabs[1]:
                $this->venueCategories();
                break;

            case $this->valid_tabs[2]:
                $this->tier1Report();
                break;

            case $this->valid_tabs[3]:
                $this->venueWorkshops();
                break;

            default:
                $this->pageError = true;
        }
    }

    function venueRankings() {
        $all_pubs = new pdPubList($this->db);
        $bad_rank = array();

        foreach ($all_pubs->list as &$pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            // if the ranking does not match the venue
            if (is_object($pub->venue)
                && ($pub->venue->rank_id != 0)
                && ($pub->venue->rank_id != $pub->rank_id))
                $bad_rank[] = $pub->pub_id;
        }

        echo '<h2>Non Matching Venue Rankings</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_rank));
        echo $this->displayPubList($pub_list, true);
    }

    function venueCategories() {
        $all_pubs = new pdPubList($this->db);
        $bad_cat = array();

        foreach ($all_pubs->list as &$pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            if (is_object($pub->venue)
                && is_object($pub->category)
                && ($pub->venue->cat_id != $pub->category->cat_id))
                $bad_cat[] = $pub->pub_id;
        }

        echo '<h2>Non Matching Venue Category</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_cat));
        echo $this->displayPubList($pub_list, true);
    }

    function tier1Report() {
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

    function venueWorkshops() {
        $all_pubs = new pdPubList($this->db);
        $bad_rank = array();

        foreach ($all_pubs->list as &$pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            // if the ranking does not match the venue
            if (is_object($pub->category)
                && ($pub->category->cat_id == 4)
                && ($pub->rank_id != 3))
                $bad_rank[] = $pub->pub_id;
        }

        echo '<h2>Workshops with suspect rankings</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_rank));
        echo $this->displayPubList($pub_list, true);
    }

    function selMenu() {
        $text = '<div id="sel"><ul>';
        foreach($this->valid_tabs as $tab) {
            if ($tab == $this->tab)
                $text .= '<li><a href="#" class="selected">'
                    . $tab . '</a></li>';
            else
                $text .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?tab='. $tab
                    . '">' . $tab . '</a></li>';
        }
        $text .= '</ul></div><br/>';

        return $text;
    }
}

$page = new pubSanityChecks();
echo $page->toHtml();

?>