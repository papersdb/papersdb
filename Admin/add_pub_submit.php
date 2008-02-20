<?php ;

// $Id: add_pub_submit.php,v 1.28 2008/02/20 21:20:14 loyola Exp $

/**
 * This is the form portion for adding or editing author information.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdAuthor.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub_submit extends pdHtmlPage {
    public $debug = 0;

    public function __construct() {
        parent::__construct(null, 'Publication Submitted',
                           'Admin/add_pub_submit.php', pdNavMenuItem::MENU_NEVER);

        if ($this->loginError) return;

        if (!isset($_SESSION['state']) || ($_SESSION['state'] != 'pub_add')) {
            $this->pageError = true;
            return;
        }

        $pub =& $_SESSION['pub'];
        $user =& $_SESSION['user'];

        if ($pub->pub_id != null) {
          echo 'The following PapersDB entry has been modified:<p/>';
        }
        else {
          echo 'The following PapersDB entry has been added to the database:<p/>';
        }

        $pub->submit = $user->name;

        $pub->dbSave($this->db);

        // deal with paper
        if (isset($_SESSION['paper'])) {
            if ($_SESSION['paper'] != 'none')
                $pub->paperSave($this->db, $_SESSION['paper']);
            else
                $pub->deletePaper($this->db);
        }

        if (isset($_SESSION['attachments'])
            && (count($_SESSION['attachments']) > 0))
            for ($i = 0, $n = count( $_SESSION['attachments']); $i < $n; $i++) {
                assert('isset($_SESSION["att_types"][$i])');

                $pub->attSave($this->db, $_SESSION['attachments'][$i],
                              $_SESSION['att_types'][$i]);
            }

        if (isset($_SESSION['removed_atts'])
             && (count($_SESSION['removed_atts']) > 0))
            foreach ($_SESSION['removed_atts'] as $filename)
                $pub->dbAttRemove($this->db, $filename);

        if ($this->debug) {
        	debugVar('$pub', $pub);
        }

        // does pub entry require validation?
        if ($pub->validationRequired($this->db) 
            && $user->isAdministrator())
            $pub->markValid($this->db);
        else 
            $pub->markPending($this->db);

        echo $pub->getCitationHtml(), getPubIcons($this->db, $pub);

        pubSessionInit();
    }
}

$page = new add_pub_submit();
echo $page->toHtml();


?>
