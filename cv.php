<?php ;

// $Id: cv.php,v 1.14 2006/10/20 16:13:42 aicmltec Exp $

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
    function cv() {
        pubSessionInit();
        parent::pdHtmlPage('cv', null, false);

        if (!isset($_POST['pub_ids'])) {
            $this->pageError = true;
            return;
        }

        $db =& dbCreate();
        $pub_count = 0;
        foreach (split(",", $_POST['pub_ids']) as $pub_id) {
            $pub_count++;
            $pub = new pdPublication();
            $pub->dbLoad($db, $pub_id);
            $this->contentPre .= '<b>[' . $pub_count . ']</b> '
              . $pub->getCitationText() . '<p/>';
        }
        $db->close();
    }
}

$page = new cv();
echo $page->toHtml();

?>

