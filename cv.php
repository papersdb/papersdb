<?php ;

// $Id: cv.php,v 1.19 2007/03/14 02:58:47 loyola Exp $

/**
 * This file outputs all the search results given to it in a CV format.
 *
 * This is mainly for the authors needing to publish there CV.  Given the ID
 * numbers of the publications, it extracts the information from the database
 * and outputs the data in a certain format.  Input: $_POST['pub_ids'] - a
 * string file of the publication ids seperated by commas Output: CV Format
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class cv extends pdHtmlPage {
    var $pub_ids;

    function cv() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('cv', null, false);

        if ($this->loginError) return;

        $this->loadHttpVars(false, true);

        if (!isset($this->pub_ids)) {
            $this->pageError = true;
            return;
        }

        $pub_count = 0;
        foreach (explode(',', $this->pub_ids) as $pub_id) {
            $pub_count++;
            $pub = new pdPublication();
            $pub->dbLoad($this->db, $pub_id);
            echo '<b>[' . $pub_count . ']</b> '
              . $pub->getCitationText() . '<p/>';
        }
    }
}

$page = new cv();
echo $page->toHtml();

?>

