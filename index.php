<?php ;

// $Id: index.php,v 1.19 2006/06/08 23:42:34 aicmltec Exp $

/**
 * \file
 *
 * \brief Main page for application.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 */
class indexPage extends pdHtmlPage {
    function indexPage() {
        parent::pdHtmlPage('index');
        $this->db =& dbCreate();
        $pub_list = new pdPubList($this->db, null, -1, true);

        $this->contentAdd('Recent Additions:<ul>');
        foreach ($pub_list->list as $pub) {
            if ($stringlength > 300) break;

            if (strlen($pub->title) < 60)
                $stringlength += 60;
            else if (strlen($pub->title) <= 120)
                $stringlength += 120;
            else if (strlen($pub->title) > 120)
                $stringlength += 180;

            $this->contentAdd('<li><a href="view_publication.php?pub_id='
                              . $pub->pub_id . '">'
                              . '<b>' . $pub->title . '</b></a></li>');
        }
        $this->contentAdd('</ul>');
        $this->db->close();
    }
}

$page = new indexPage();
echo $page->toHtml();

?>
