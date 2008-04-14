<?php ;



/**
 * Main page for PapersDB.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'diag/aicml_pubs_base.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdTagMlHistory.php';

/**
 * This script is called when the 'tag_non_ml.php' script is submitted. Each
 * of the paper entries tagged by the user will have 'machine learning' appended
 * to its keywords.
 *
 * @package PapersDB
 */
class tag_non_ml extends pdHtmlPage {
    protected $pub_tag;
    
    public function __construct() {
        parent::__construct('tag_ml_submit', 'Submit ML Paper Entries', 
        	'diag/tag_ml_submit.php');

        if ($this->loginError) return;
        
        $this->loadHttpVars();
        
        $pubs_tagged = array();
        
        foreach ($this->pub_tag as $pub_id => $tag) {
            if ($tag != 'yes')  continue;
            
            $pub = new pdPublication();
            $pub->dbLoad($this->db, $pub_id);
            
            if (strpos(strtolower($pub->keywords), 'machine learning' !== false)) {
                echo 'Error: paper titled<br/>', $pub->title, 
                	'<br/>is already a machine learning paper.<br/>';
                continue;
            }
            
            $pub->keywordAdd('machine learning');
            $pub->dbSave($this->db);
            $pubs_tagged[$pub_id] = $pub;
        }     

        pdTagMlHistory::dbSave($this->db, array_keys($pubs_tagged));
        
        if (count($pubs_tagged) == 0) {
            echo 'No publication entries tagged<br>/';
            return;
        }
        
        echo 'The following publication entries are now tagged as '
        . '<i>machine learning</i>:<ul>';
        foreach ($pubs_tagged as $pub_id => $pub) {
            echo '<li>', $pub->title, '</li>';
        }
        echo '</ul>';
    }
}

$page = new tag_non_ml();
echo $page->toHtml();

?>
