<?php ;

// $Id: add_pub_submit.php,v 1.2 2006/09/13 20:26:16 aicmltec Exp $

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
class add_pub_submit extends pdHtmlPage {
    var $debug = 0;

    function add_pub_submit() {
        global $access_level;

        parent::pdHtmlPage('add_publication', 'Select Authors',
                           'Admin/add_pub_submit.php',
                           PD_NAV_MENU_LEVEL_ADMIN);

        if ($access_level <= 1) {
            header('Location: add_pub1.php');
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
        if (strpos(basename($_SESSION['paper']), 'paper_') === false)
            $pub->paperSave($db, $_SESSION['paper']);

        if (count($_SESSION['attachments']) > 0)
            for ($i = 0; $i < count( $_SESSION['attachments']); $i++) {
                assert('isset($_SESSION["att_types"][$i])');

                echo 'add_pub_submit.php here<br/>';

                if (strpos(basename($_SESSION['attachments'][$i]),
                            'additional_') === false) {
                    $pub->attSave($db, $_SESSION['attachments'][$i],
                                  $_SESSION['att_types'][$i]);
                }
            }

        if (count($_SESSION['removed_atts']) > 0)
            foreach ($_SESSION['removed_atts'] as $filename)
                $pub->dbAttRemove($db, $filename);

        if ($this->debug) {
            $this->contentPost
                .= 'sess<pre>' . print_r($_SESSION, true) . '</pre>';
        }

        $this->contentPre .= 'The following publication has been added to '
            . 'the database:<p/>'
            . $pub->getCitationHtml()
            . '<a href="../view_publication.php?pub_id=' . $pub->pub_id . '">'
            . '<img src="../images/viewmag.png" title="view" alt="view" height="16" '
            . 'width="16" border="0" align="middle" /></a>';

        pubSessionInit();
        $this->db->close();
    }

}

session_start();
$access_level = check_login();
$page = new add_pub_submit();
echo $page->toHtml();


?>
