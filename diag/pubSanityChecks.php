<?php ;

// $Id: pubSanityChecks.php,v 1.10 2007/10/03 20:01:17 aicmltec Exp $

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
require_once 'includes/pdCatList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class pubSanityChecks extends pdHtmlPage {
    var $tab;
    var $valid_tabs = array('Rankings', 'Categories', 'Tier 1',
                            'Journals', 'Conferences', 'Workshops',
                            'Posters', 'Non ML');
    var $tier1 = array('AAAI',
                       'AIJ',
                       'CCR',
                       'ICML',
                       'IJCAI',
                       'JAIR',
                       'JMLR',
                       'MLJ',
                       'NAR',
                       'NIPS',
                       'UAI');

    var $years = array('0' => array('2002-09-01', '2003-08-31'),
                       '1' => array('2003-09-01', '2004-08-31'),
                       '2' => array('2004-09-01', '2006-03-31'),
                       '3' => array('2006-04-01', '2007-03-31'));

    var $pi_authors = array(
        'Szepesvari, C' => array('2006-09-01', '2007-03-31'),
        'Schuurmans, D' => array('2003-07-01', '2007-03-31'),
        'Schaeffer, J'  => array('2002-09-01', '2007-03-31'),
        'Bowling, M'    => array('2003-07-01', '2007-03-31'),
        'Goebel, R'     => array('2002-09-01', '2007-03-31'),
        'Sutton, R'     => array('2003-09-01', '2007-03-31'),
        'Holte, R'      => array('2002-09-01', '2007-03-31'),
        'Greiner, R'    => array('2002-09-01', '2007-03-31'));


    function pubSanityChecks() {
        parent::pdHtmlPage('pub_sanity_checks', 'Pub Sanity Checks',
                           'diag/pubSanityChecks.php');

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
                $this->venueJournalRank();
                break;

            case $this->valid_tabs[4]:
                $this->venueConferenceRank();
                break;

            case $this->valid_tabs[5]:
                $this->venueWorkshopRank();
                break;

            case $this->valid_tabs[6]:
                $this->venuePosterRank();
                break;

            case $this->valid_tabs[7]:
                $this->nonML();
                break;

            default:
                $this->pageError = true;
        }
    }

    function venueRankings() {
        $all_pubs = new pdPubList($this->db);
        $bad_rank = array();
        $additional = array();
        $rankings = pdPublication::rankingsGlobalGet($this->db);

        foreach ($all_pubs->list as &$pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            // if the ranking does not match the venue
            if (is_object($pub->venue)
                && ($pub->venue->rank_id != 0)
                && ($pub->venue->rank_id != $pub->rank_id)) {
                $bad_rank[] = $pub->pub_id;
                $additional[$pub->pub_id]
                    = 'Venue ranking: ' . $rankings[$pub->venue->rank_id];
            }
        }

        echo '<h2>Non Matching Venue Rankings</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_rank));
        echo $this->displayPubList($pub_list, true, -1, $additional);
    }

    function venueCategories() {
        $all_pubs = new pdPubList($this->db);
        $bad_cat = array();
        $additional = array();
        $categories = new pdCatList($this->db);

        foreach ($all_pubs->list as &$pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            if (is_object($pub->venue)
                && is_object($pub->category)) {

                if ($pub->venue->cat_id == 0) {
                    $bad_cat[] = $pub->pub_id;
                    $additional[$pub->pub_id] = 'Venue has no category';
                }
                else if ($pub->venue->cat_id != $pub->category->cat_id) {
                    $bad_cat[] = $pub->pub_id;
                    $additional[$pub->pub_id] = 'Venue category is: '
                        . $categories->list[$pub->venue->cat_id] .
                        '<br/> Pub entry category is: '
                        . $categories->list[$pub->category->cat_id];
                }
            }
        }

        echo '<h2>Non Matching Venue Category</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_cat));
        echo $this->displayPubList($pub_list, true, -1, $additional);
    }

    function tier1Report() {
        $bad_pubs = array();

        // check for T1 pubs
        $all_pubs = new pdPubList($this->db);
        foreach ($all_pubs->list as $pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            if ($this->pubVenueIsTier1($pub) && ($pub->rank_id != 1))
                $bad_pubs[] = $pub->pub_id;
        }

        echo '<h2>Mislabelled Tier 1</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_pubs));
        echo $this->displayPubList($pub_list, true);
    }

    function pubVenueIsTier1(&$pub) {
        assert('is_object($pub)');

        if (is_object($pub->category)
            && ($pub->category->cat_id != 3)
            && ($pub->category->cat_id != 1))
            return false;

        if (is_object($pub->venue)
            && isset($pub->venue->title)) {
            $is_tier1 = false;
            foreach ($this->tier1 as $venue) {
                if (strpos($pub->venue->title, $venue) !== false)
                    return true;
            }
        }
        return false;
    }

    function venueJournalRank() {
        $all_pubs = new pdPubList($this->db);
        $bad_rank = array();
        $additional = array();

        foreach ($all_pubs->list as &$pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            // if the ranking does not match the venue
            if (is_object($pub->category)
                && ($pub->category->cat_id == 3)) {
                if ($pub->rank_id > 2) {
                    $bad_rank[] = $pub->pub_id;
                    $additional[$pub->pub_id] = 'Should be Tier 2';
                }

                // check if its a tier 1 journal
                if ($this->pubVenueIsTier1($pub) && ($pub->rank_id != 1)) {
                    $bad_rank[] = $pub->pub_id;
                    $additional[$pub->pub_id] = 'Should be Tier 1';
                }
            }
        }

        echo '<h2>Journal publication entries with suspect rankings</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_rank));
        echo $this->displayPubList($pub_list, true, -1, $additional);
    }

    function venueConferenceRank() {
        $all_pubs = new pdPubList($this->db);
        $bad_rank = array();
        $additional = array();

        foreach ($all_pubs->list as &$pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            // if the ranking does not match the venue
            if (is_object($pub->category)
                && ($pub->category->cat_id == 1)) {
                if ($pub->rank_id > 2) {
                    $bad_rank[] = $pub->pub_id;
                    $additional[$pub->pub_id] = 'Should be Tier 2';
                }

                // check if its a tier 1 conference
                if ($this->pubVenueIsTier1($pub) && ($pub->rank_id != 1)) {
                    $bad_rank[] = $pub->pub_id;
                    $additional[$pub->pub_id] = 'Should be Tier 1';
                }
            }
        }

        echo '<h2>Conference publication entries with suspect rankings</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_rank));
        echo $this->displayPubList($pub_list, true, -1, $additional);
    }

    function venueWorkshopRank() {
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

        echo '<h2>Workshop publication entries with suspect rankings</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_rank));
        echo $this->displayPubList($pub_list, true);
    }

    function venuePosterRank() {
        $all_pubs = new pdPubList($this->db);
        $bad_rank = array();

        foreach ($all_pubs->list as &$pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            // if the ranking does not match the venue
            if (is_object($pub->category)
                && ($pub->category->cat_id == 12)
                && ($pub->rank_id != 4))
                $bad_rank[] = $pub->pub_id;
        }

        echo '<h2>Poster publication entries with suspect rankings</h1>';
        $pub_list =  new pdPubList($this->db, array('pub_ids' => $bad_rank));
        echo $this->displayPubList($pub_list, true);
    }

    function nonML() {
        foreach ($this->pi_authors as $name => &$dates) {
            $all_pubs = new pdPubList($this->db,
                                      array('author_name' => $name,
                                            'date_start' => $dates[0],
                                            'date_end' => date('Y-m-d')));

            if (count($all_pubs->list) == 0) continue;

            $non_ml = array();

            foreach ($all_pubs->list as &$pub) {
                $keywords = strtolower($pub->keywords);
                if (strpos($keywords, 'machine learning') === false)
                    $non_ml[] = $pub->pub_id;
            }

            echo '<h2>Non Machine Learning papers for ' . $name . '</h1>';
            $pub_list =  new pdPubList($this->db, array('pub_ids' => $non_ml));
            echo $this->displayPubList($pub_list, true);
        }
    }

    function selMenu() {
        $text = '<div id="sel2"><ul>';
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