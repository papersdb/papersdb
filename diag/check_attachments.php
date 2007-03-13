<?php ;

// $Id: check_attachments.php,v 1.7 2007/03/13 22:06:11 aicmltec Exp $

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
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('check_attachments');

        if ($this->loginError) return;

        $pub_list = new pdPubList($this->db);
        foreach ($pub_list->list as $pub) {
            $pub->dbLoad($this->db, $pub->pub_id);

            $paper_arr = split('paper_', $pub->paper);

            if (isset($paper_arr[1])) {
                if ($paper_arr[1] == '') continue;

                $this->checkAtt($pub->pub_id, $pub->paper, $paper_arr[1], 0);
            }

            if (count($pub->additional_info) > 0)
                foreach ($pub->additional_info as $att) {
                    $paper_arr = split('additional_', $att->location);
                    $this->checkAtt($pub->pub_id, $att->location,
                                    $paper_arr[1], 1);
                }
        }
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
            echo 'pub_id ' . $pub_id
                . ' missing ';
            if ($is_additional)
                echo 'additional ';
            else
                echo 'paper ';

            echo $basename . '<br/>' . "\n";
        }
    }
}

$page = new check_attachments();
echo $page->toHtml();

?>
