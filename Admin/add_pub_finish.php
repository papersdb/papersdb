<?php ;

// $Id: add_pub_finish.php,v 1.2 2006/09/11 20:00:09 aicmltec Exp $

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
    var $debug = 0;

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
        $user =& $_SESSION['user'];

        $pub->submit = $user->name;

        $pub->dbSave($db);

        // deal with paper
        $pub->paperSave($db, $_SESSION['paper']);
        if (count( $_SESSION['attachments']) > 0)
            for ($i = 0; $i < count( $_SESSION['attachments']); $i++) {
                assert('isset($_SESSION["att_types"][$i])');
                $pub->attSave($db, $_SESSION['attachments'][$i],
                              $_SESSION['att_types'][$i]);
            }


        if ($this->debug) {
            $this->contentPre
                .= 'sess<pre>' . print_r($_SESSION, true) . '</pre>';
        }

        $this->contentPre .= 'The following publication has been added to '
            . 'the database:<p/>'
            . $pub->getCitationHtml();

        pubSessionInit();
        $this->db->close();
    }

}

session_start();
$access_level = check_login();
$page = new add_pub_finish();
echo $page->toHtml();


?>
