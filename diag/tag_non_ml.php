<?php ;

// $Id: tag_non_ml.php,v 1.2 2008/02/04 13:52:22 loyola Exp $

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

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class tag_non_ml extends aicml_pubs_base {
    public function __construct() {
        parent::__construct('tag_non_ml');

        if ($this->loginError) return;
        
        $pubs =& $this->getNonMachineLearningPapers();
        
        $form = new HTML_QuickForm('tag_non_ml_form');
        
        foreach ($pubs as $pub) {
        	$form->addGroup(array(
                HTML_QuickForm::createElement('static', null, null,
        			$pub->getCitationHtml()),
                HTML_QuickForm::createElement('advcheckbox', 
                	'pub_tag[' . $pub->pub_id . ']',
        			null, null, null, array('no', 'yes'))
        		),
        		'tag_ml_grouop', null, null, false);
        }

        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm($form);
    }
    
    private function processForm() {
    
    }
    
    private function renderForm(&$form) {
        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" '
            . 'cellspacing="2">'
            . '<form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $form->accept($renderer);
        $this->renderer =& $renderer;
    }
    
    private function getNonMachineLearningPapers() {
    	$pubs =& $this->getAllAicmlAuthoredPapers();
        
        foreach ($pubs as $pub_id => $pub) {
            $pub->dbLoad($this->db, $pub_id);

            // only consider machine learning papers
            if (isset($pub->keywords)
                && (strpos(strtolower($pub->keywords), 'machine learning') !== false))
                unset($pubs[$pub_id]);

            // publication must have the category assigned and
            // category must be either 'In Journal' or 'In Conference'
            if (isset($pub->category)  && ($pub->category->cat_id != 1) 
                && ($pub->category->cat_id != 3))
                unset($pubs[$pub_id]);
        }

        uasort($pubs, array('pdPublication', 'pubsDateSortDesc'));
        return $pubs;
    }    
}

$page = new tag_non_ml();
echo $page->toHtml();

?>
