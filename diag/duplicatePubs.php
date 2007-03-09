<?php ;

// $Id: duplicatePubs.php,v 1.1 2007/03/09 20:24:49 aicmltec Exp $

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
class duplicatePubs extends pdHtmlPage {
    function duplicatePubs() {
        global $access_level;

        pubSessionInit();
        parent::pdHtmlPage('duplicatePubs', 'Duplicate Publications',
                           'diag/duplicatePubs.php');

        if ($access_level <= 1) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();

        $all_pubs = new pdPubList($db);

        $this->contentPre .= '<h1>Publications with same title</h1>'
            . 'Note that some publications may exist both in a conference '
            . 'and later in time in a journal.';

        $titles = array();

        foreach ($all_pubs->list as $pub) {
            $titles[]
                = array($pub,
                        preg_replace('/\s\s+/', ' ', strtolower($pub->title)));
        }

        //$this->debugVar('titles', $titles);

        $count = 1;
        for ($i = 0; $i < count($titles) - 1; ++$i) {
            for ($j = $i + 1; $j < count($titles); ++$j) {
                if ($titles[$i][1] == $titles[$j][1]) {
                    $this->contentPre .= '<h2>Match ' . $count . '</h2>';

                    $titles[$i][0]->dbLoad($db, $titles[$i][0]->pub_id);
                    $titles[$j][0]->dbLoad($db, $titles[$j][0]->pub_id);

                    $this->contentPre .= $this->citationGet( $titles[$i][0])
                        . '<br/>' . $this->citationGet( $titles[$j][0]);

                    ++$count;
                }
            }
        }

        $db->close();
    }

    function citationGet($pub) {
        assert('is_object($pub)');

        $citation = $pub->getCitationHtml('..');

        // Show Paper
        if ($pub->paper != 'No paper') {
            $citation .= '<a href="../' . $pub->paperAttGetUrl() . '">';

            if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
                $citation .= '<img src="../images/pdf.gif" alt="PDF" '
                    . 'height="18" width="17" border="0" '
                    . 'align="middle">';
            }

            if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
                $citation .= '<img src="../images/ppt.gif" alt="PPT" '
                    . 'height="18" width="17" border="0" '
                    . 'align="middle">';
            }

            if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
                $citation .= '<img src="../images/ps.gif" alt="PS" '
                    . 'height="18" width="17" border="0" '
                    . 'align="middle">';
            }
            $citation .= '</a>';
        }

        $citation .= '<a href="../view_publication.php?pub_id='
            . $pub->pub_id . '">'
            . '<img src="../images/viewmag.png" title="view" alt="view" '
            . ' height="16" width="16" border="0" align="middle" /></a>'
            . '<a href="../Admin/add_pub1.php?pub_id='
            . $pub->pub_id . '">'
            . '<img src="../images/pencil.png" title="edit" alt="edit" '
            . ' height="16" width="16" border="0" align="middle" />'
            . '</a>'
            . '<a href="../Admin/delete_publication.php?pub_id='
            . $pub->pub_id . '">'
            . '<img src="../images/kill.png" title="delete" alt="delete" '
            . 'height="16" width="16" border="0" align="top" /></a>';

        return $citation;
    }
}

session_start();
$access_level = check_login();
$page = new duplicatePubs();
echo $page->toHtml();

?>