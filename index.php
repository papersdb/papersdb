<?php ;

// $Id: index.php,v 1.29 2006/09/24 21:21:42 aicmltec Exp $

/**
 * Main page for PapersDB.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 *
 * @package PapersDB
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class indexPage extends pdHtmlPage {
    function indexPage() {
        global $access_level;

        pubSessionInit();

        parent::pdHtmlPage('home');
        $db =& dbCreate();
        $pub_list = new pdPubList($db, array('sort_by_updated' => true));

        $this->contentPre = 'Recent Additions:<ul>';
        $stringlength = 0;
        foreach ($pub_list->list as $pub) {
            if ($stringlength > 300) break;

            if (strlen($pub->title) < 60)
                $stringlength += 60;
            else if (strlen($pub->title) <= 120)
                $stringlength += 120;
            else if (strlen($pub->title) > 120)
                $stringlength += 180;

            $this->contentPre .= '<li><a href="view_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<b>' . $pub->title . '</b></a></li>';
        }
        $this->contentPre .= '</ul>';
        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new indexPage();
echo $page->toHtml();

?>
