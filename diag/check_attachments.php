<?php ;

// $Id: check_attachments.php,v 1.3 2006/09/24 21:21:42 aicmltec Exp $

/**
 * Script that reports the publications whose attachments are not
 * on the file server.
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
class check_attachments extends pdHtmlPage {
    function check_attachments() {
        global $access_level;

        pubSessionInit();
        parent::pdHtmlPage('check_attachments');

        if ($access_level <= 1) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();

        $pub_list = new pdPubList($db);
        foreach ($pub_list->list as $pub) {
            $pub->dbLoad($db, $pub->pub_id);

            $paper_arr = split('paper_', $pub->paper);

            if ($paper_arr[1] == '') continue;

            $this->checkAtt($pub->pub_id, $pub->paper, $paper_arr[1], 0);

            if (count($pub->additional_info) > 0)
                foreach ($pub->additional_info as $att) {
                    $paper_arr = split('additional_', $att->location);
                    $this->checkAtt($pub->pub_id, $att->location,
                                    $paper_arr[1], 1);
                }
        }

        $db->close();
    }

    function checkAtt($pub_id, $dbname, $basename, $is_additional) {
        $filename = $dbname;

        if ($is_additional) {
            if (strpos($dbname, 'uploaded_files') === false) {
                $filename = FS_PATH . '/uploaded_files/' . $dbname;
            }
            else
                $filename = FS_PATH . $dbname;
        }
        else {
            if (strpos($dbname, 'uploaded_files') === false) {
                $filename = FS_PATH . '/uploaded_files/' . $pub_id . '/' . $dbname;
            }
            else
                $filename = FS_PATH . $dbname;
        }

        if (!file_exists($filename)) {
            $this->contentPre .= 'pub_id ' . $pub_id
                . ' missing ';
            if ($is_additional)
                $this->contentPre .= 'additional ';
            else
                $this->contentPre .= 'paper ';

            $this->contentPre .= $basename . '<br/>' . "\n";
        }
    }
}

session_start();
$access_level = check_login();
$page = new check_attachments();
echo $page->toHtml();

?>
