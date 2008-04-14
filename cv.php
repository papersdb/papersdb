<?php ;



/**
 * This file outputs all the search results given to it in a CV format.
 *
 * This is mainly for the authors needing to publish there CV.  Given the ID
 * numbers of the publications, it extracts the information from the database
 * and outputs the data in a certain format.  Input: $_POST['pub_ids'] - a
 * string file of the publication ids seperated by commas Output: CV Format
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class cv extends pdHtmlPage {
    public $pub_ids;

    public function __construct() {
        parent::__construct('cv', null, false);

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (!isset($this->pub_ids)) {
            $this->pageError = true;
            return;
        }

        $pubs = explode(',', $this->pub_ids);

        if (!is_array($pubs) || (count($pubs) == 0)) {
            $this->pageError = true;
            return;
        }

        $pub_list = pdPubList::create($this->db, array('pub_ids' => $pubs));

        if (empty($pub_list) || (count($pub_list) == 0)) {
            $this->pageError = true;
            return;
        }

        $pub_count = 0;
        foreach ($pub_list as $pub) {
            $pub_count++;
            $result = $pub->dbLoad($this->db, $pub->pub_id);

            if ($result === false) {
                $this->pageError = true;
                return;
            }

            echo '<b>[', $pub_count, ']</b> ', $pub->getCitationText(), '<p/>';
        }
    }
}

$page = new cv();
echo $page->toHtml();

?>

