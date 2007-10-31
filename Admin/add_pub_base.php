<?php ;

// $Id: add_pub_base.php,v 1.8 2007/10/31 19:29:47 loyola Exp $

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
    protected $pub;
    protected $pub_id;

    public function __construct() {
        parent::__construct('add_publication');

        if ($this->loginError) return;

        if ((get_class($this) == "add_pub2")
            || (get_class($this) == "add_pub3")
            || (get_class($this) == "add_pub4")) {
            if ($_SESSION['state'] != 'pub_add') {
                header('Location: add_pub1.php');
                return;
            }
        }

        $this->addPubDisableMenuItems();
    }

    public static function similarPubsHtml() {
        if (!isset($_SESSION['similar_pubs'])) return;

        $html = '<h3>Similar Publications in Database</h3>';
        foreach ($_SESSION['similar_pubs'] as $sim_pub_id) {
            $sim_pub = new pdPublication();
            $sim_pub->dbLoad($this->db, $sim_pub_id);

            $html .= $sim_pub->getCitationHtml('..', false) . '<p/>';
        }

        return $html;
    }

    public function addPubDisableMenuItems() {
        $this->navMenuItemEnable('add_publication', 0);
        $this->navMenuItemDisplay('add_author', 0);
        $this->navMenuItemDisplay('add_category', 0);
        $this->navMenuItemDisplay('add_venue', 0);
    }
}