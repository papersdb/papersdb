<?php ;

// $Id: search_results.php,v 1.24 2007/10/26 22:03:15 aicmltec Exp $

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
    var $debug = 0;

    function search_results() {
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
    function otherFormatForm($result_pubs) {
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

    function renderForm() {
        $renderer =& $this->form->defaultRenderer();
        $this->form->accept($renderer);

        $pubs = new pdPubList(
            $this->db, array('cat_pub_ids' => $_SESSION['search_results']));

        echo $renderer->toHtml();
        echo $this->displayPubList($pubs);

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

        echo '<hr/>' . $searchLinkTable->toHtml();
    }

    function processForm() {
        $values = $this->form->exportValues();

        if (isset($values['cv_format']))
            header('Location: cv.php?pub_ids=' . $values['pub_ids']);
        else if (isset($values['bibtex_format']))
            header('Location: bibtex.php?pub_ids=' . $values['pub_ids']);
    }

    function showSearchParams() {
        $sp =& $_SESSION['search_params'];

        $table = new HTML_Table(array('class' => 'nomargins',
                                      'width' => '60%'));

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
                                      'authortyped',
                                      'authorselect',
                                      'paper_rank',
                                      'paper_rank_other',
                                      'paper_col'))
                     as $param)
                if ($sp->$param != '') {
                    $name = '';

                    if ($param == 'cat_id') {
                        $cl = new pdCatList($this->db);
                        $name = 'Category';
                        $value =& $cl->list[$sp->cat_id];
                    }
                    else {
                        $name = ucwords($param);
                        $value = $sp->$param;
                    }

                    if ($name != '')
                        $table->addRow(array($name . ':', $value));
                }

            $al = null;
            $values = array();

            if ($sp->authortyped != '') {
                $values[] = $sp->authortyped;
            }

            if (($sp->author_myself != '')
                && ($_SESSION['user']->author_id != '')) {
                $al = new pdAuthorList($this->db);

                $values[] = $al->list[$_SESSION['user']->author_id];
            }

            if (count($sp->authorselect) > 0) {
                if ($al == null)
                    $al = new pdAuthorList($this->db);
                foreach ($sp->authorselect as $auth_id)
                    $values[] = $al->list[$auth_id];

            }

            if (count($values) > 0)
                $table->addRow(array('<b>Author(s)</b>:',
                                     implode(' AND ', $values)));

            if (isset($_SESSION['user'])
                && ($_SESSION['user']->showInternalInfo())) {
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

                // collaboration
                $label = 'Collaboration:';
                $collaborations = pdPublication::collaborationsGet($this->db);

                foreach ($sp->paper_col as $col_id => $value) {
                    if ($value != 'yes') continue;

                    $table->addRow(array($label, $collaborations[$col_id]));
                    $label = '';
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
