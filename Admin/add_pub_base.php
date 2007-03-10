<?php ;

// $Id: add_pub_base.php,v 1.1 2007/03/10 01:23:05 aicmltec Exp $

/**
 * Common functions used by pages for adding a new publication.
 *
 * @package PapersDB
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';
require_once 'includes/functions.php';


class add_pub_base extends pdHtmlPage {
    var $pub;
    var $pub_id;

    function add_pub_base($page_id) {
        global $access_level;

        foreach (array_keys(get_class_vars('add_pub_base')) as $name) {
            if (isset($_GET[$name]) && ($_GET[$name] != ''))
                $$name = stripslashes($_GET[$name]);
            else
                $$name = null;
        }

        $db = $this->db = dbCreate();

        if (isset($_SESSION['pub'])) {
            // according to session variables, we are already editing a
            // publication
            $pub =& $_SESSION['pub'];
        }
        else if ($pub_id != '') {
            // pub_id passed in with $_GET variable
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id);
            if (!$result) {
                $this->pageError = true;
                $db->close();
                return;
            }

            $_SESSION['pub'] =& $pub;
        }
        else {
            // create a new publication
            $pub = new pdPublication();
            $_SESSION['pub'] =& $pub;
        }

        parent::pdHtmlPage($page_id);

        if ($access_level <= 0) {
            $this->loginError = true;
            $db->close();
            return;
        }
    }

    /**
     * This is a static function.
     */
    function similarPubsHtml($db) {
        assert('is_object($db)');

        if (!isset($_SESSION['similar_pubs'])) return;

        $html = '<h3>Similar Publications in Database</h3>';
        foreach ($_SESSION['similar_pubs'] as $sim_pub_id) {
            $sim_pub = new pdPublication();
            $sim_pub->dbLoad($db, $sim_pub_id);

            $html .= $sim_pub->getCitationHtml('..', false) . '<p/>';
        }

        return $html;
    }
}