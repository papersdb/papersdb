<?php ;

// $Id: search_results.php,v 1.30 2007/11/02 22:42:26 loyola Exp $

/**
 * Displays the search resutls contained in the session variables.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdSearchParams.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdUser.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class search_results extends pdHtmlPage {
    public $debug = 0;

    public function __construct() {
        parent::__construct('search_results');

        if ($this->loginError) return;

        if (!isset($_SESSION['search_results'])
            || !isset($_SESSION['search_url'])) {
            $this->pageError = true;
            return;
        }

        $this->showSearchParams();

        if (count($_SESSION['search_results']) == 0) {
            echo '<br/><h3>Your search did not generate any results.</h3>';
            return;
        }

        $this->form = $this->otherFormatForm($_SESSION['search_results']);

        if ($this->form->validate())
            $this->processForm();
        else
            $this->renderForm();
    }

    /**
     *
     */
    public function otherFormatForm($result_pubs) {
        if ($result_pubs == null) return;

        $form = new HTML_QuickForm('otherFormatForm');
        $form->addElement('hidden', 'pub_ids', implode(",", $result_pubs));

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'submit', 'cv_format', 'Show results in CV format'),
                HTML_QuickForm::createElement(
                    'submit', 'bibtex_format', 'Show results in BibTex format')
                ),
            null, null, '&nbsp;');

        return $form;
    }

    public function renderForm() {
        $sp =& $_SESSION['search_params'];
        $renderer =& $this->form->defaultRenderer();
        $this->form->accept($renderer);

        $pubs = pdPubList::create(
            $this->db, array('cat_pub_ids' => $_SESSION['search_results']));
            
        if ($pubs == null) return;

        echo $renderer->toHtml();
        echo $this->displayPubList($pubs, true, -1, null, 
        						   array('show_internal_info' 
        						          => ($sp->show_internal_info == 'yes')));

        $searchLinkTable = new HTML_Table(array('id' => 'searchlink',
                                                'border' => '0',
                                                'cellpadding' => '0',
                                                'cellspacing' => '0'));

        $search_url =& $_SESSION['search_url'];

        $searchLinkTable->addRow(
            array('<a href="' . $search_url . '">'
                  . '<img src="images/link.gif" title="view" alt="view" '
                  . 'height="16" width="16" border="0" align="top" />'
                  . ' Link to this search</a></div><br/>'));

        echo '<hr/>', $searchLinkTable->toHtml();
    }

    public function processForm() {
        $values = $this->form->exportValues();

        if (isset($values['cv_format']))
            header('Location: cv.php?pub_ids=' . $values['pub_ids']);
        else if (isset($values['bibtex_format']))
            header('Location: bibtex.php?pub_ids=' . $values['pub_ids']);
    }

    public function showSearchParams() {
        $sp =& $_SESSION['search_params'];

        $table = new HTML_Table(array('class' => 'nomargins',
                                      'width' => '90%'));

        if ($sp->search != '') {
            $table->addRow(array($sp->search));
        }
        else {
            // check each field of the search parameter except the dates and
            // authors
            foreach (array_diff(array_keys(get_class_vars(get_class($sp))),
                                array('startdate',
                                      'enddate',
                                      'author_myself',
                                      'authors',
                                      'paper_rank',
                                      'paper_rank_other',
                                      'paper_col'))
                     as $param)
                if ($sp->$param != '') {
                    $name = '';

                    if ($param == 'cat_id') {
                        $cl = pdCatList::create($this->db);
                        $name = 'Category';
                        $value =& $cl[$sp->cat_id];
                    }
                    else {
                        $name = preg_replace('/_/', ' ', ucwords($param));
                        $value = $sp->$param;
                    }
                    
                    if (($param == 'show_internal_info') 
                         && ($sp->$param == 'no'))
                         continue;

                    if ($name != '')
                        $table->addRow(array($name . ':', $value));
                }

            $al = null;
            $values = array();

            if (($sp->author_myself != '')
                && ($_SESSION['user']->author_id != '')) {
                $authors = pdAuthorList::create($this->db, null, null, true);
                $values[] = $authors[$_SESSION['user']->author_id];
            }

            if (!empty($sp->authors))
	            $values[] = $sp->authors;

            if (count($values) > 0)
                $table->addRow(array('<b>Author(s)</b>:',
                                     implode(' AND ', $values)));

            if (isset($_SESSION['user'])
                && ($_SESSION['user']->showInternalInfo())) {
                if (!empty($sp->paper_rank)) {
	                // ranking
    	            $label = 'Ranking:';
        	        $rankings = pdPublication::rankingsGlobalGet($this->db);
                
            	    foreach ($sp->paper_rank as $rank_id => $value) {
    	                if ($value != 'yes') continue;
	
    	                $table->addRow(array($label, $rankings[$rank_id]));
        	            $label = '';
            	    }

	                if ($sp->paper_rank_other != '') {
    	                $table->addRow(array($label, $sp->paper_rank_other));
        	        }
                }

                if (!empty($sp->paper_col)) {
	                // collaboration
    	            $label = 'Collaboration:';
        	        $collaborations = pdPublication::collaborationsGet($this->db);
	
    	            foreach ($sp->paper_col as $col_id => $value) {
        	            if ($value != 'yes') continue;
	
    	                $table->addRow(array($label, $collaborations[$col_id]));
        	            $label = '';
            	    }
                }
            }

            if (($sp->startdate != '') && ($sp->enddate != '')) {
                $stime = strtotime(implode('-', $sp->startdate) . '-1');
                $etime = strtotime(implode('-', $sp->enddate) . '-1');

                // now check the dates
                if ($etime > $stime) {
                    $table->addRow(
                        array('<b>Start date</b>:',
                              $sp->startdate['Y'] . '-'
                              . sprintf("%02d", $sp->startdate['M'])));

                    $table->addRow(
                        array('<b>End date</b>:',
                              $sp->enddate['Y'] . '-'
                              . sprintf("%02d", $sp->enddate['M'])));
                }
            }
        }

        $table->updateColAttributes(0, array('class' => 'emph'), true);

        echo '<h3>SEARCH RESULTS FOR</h3>';
        echo $table->toHtml();
    }
}

$page = new search_results();
echo $page->toHtml();

?>
