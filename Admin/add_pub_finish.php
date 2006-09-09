<?php ;

// $Id: add_pub_finish.php,v 1.1 2006/09/09 01:03:07 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion for adding or editing author information.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/pdExtraInfoList.php';
require_once 'includes/pdAttachmentTypesList.php';

/**
 * Renders the whole page.
 */
class add_pub_finish extends pdHtmlPage {
    var $debug = 1;

    function add_pub_finish() {
        global $access_level;

        parent::pdHtmlPage('add_publication', 'Select Authors',
                           'Admin/add_pub_finish.php',
                           PD_NAV_MENU_LEVEL_ADMIN);

        if ($access_level <= 1) {
            $this->loginError = true;
            return;
        }

        if ($_SESSION['state'] != 'pub_add') {
            $this->pageError = true;
            return;
        }

        $this->navMenuItemEnable('add_publication', 0);
        $this->navMenuItemDisplay('add_author', 0);
        $this->navMenuItemDisplay('add_category', 0);
        $this->navMenuItemDisplay('add_venue', 0);

        $this->db =& dbCreate();
        $db =& $this->db;
        $pub =& $_SESSION['pub'];

        $pub->dbSave($db);

        // deal with paper
        if (isset($_SESSION['paper'])) {
            $path = FS_PATH . '/uploaded_files/';
            $pub_path = $path . $pub->pub_id;

            $e = explode('_', $_SESSION['paper']);
            $basename = 'paper_' . $e[1];
            $filename = $pub_path . '/' . $basename;

            $this->contentPre .= 'basename: ' . $basename . '<br/>'
                . 'filename: ' . $filename . '<br/>'
                . 'tmp_name: ' . $_SESSION['paper'] . '<br/>';

            // create the publication's path if it does not exist
            if (!file_exists($path)) {
                mkdir($path, 0777);
                // mkdir permissions with 0777 does not seem to work
                chmod($path, 0777);
            }

            if (rename($path . $_SESSION['paper'], $filename)) {
                chmod($filename, 0777);
                $pub->dbUpdatePaper($db, $basename);
            }
            else
                $this->contentPre .= 'Could not upload paper.<br/>';
        }

        if ($this->debug) {
            $this->contentPre
                .= 'sess<pre>' . print_r($_SESSION, true) . '</pre>';
        }

        $this->db->close();
    }

}

session_start();
$access_level = check_login();
$page = new add_pub_finish();
echo $page->toHtml();


?>
