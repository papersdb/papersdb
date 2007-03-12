<?php ;

// $Id: search_results.php,v 1.12 2007/03/12 23:05:43 aicmltec Exp $

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
        session_start();
        parent::pdHtmlPage('search_results');

        if ($this->loginError) return;

        if (!isset($_SESSION['search_results'])
            || !isset($_SESSION['search_url'])) {
            $this->pageError = true;
            return;
        }

        $sp =& $_SESSION['search_params'];

        $this->contentPre .= '<h3>SEARCH RESULTS FOR</h3>';

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
                                      'authorselect'))
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
                        $table->addRow(array('<b>' . $name . '</b>:', $value));
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

        $this->contentPre .= $table->toHtml();

        if (count($_SESSION['search_results']) == 0) {
            $this->contentPre
                .= '<br/><h3>Your search did not generate any results.</h3>';
            $this->db->close();
            return;
        }

        $search_url =& $_SESSION['search_url'];

        $pubs = new pdPubList(
            $this->db, array('pub_ids' => $_SESSION['search_results']));

        $cvForm = $this->cvFormCreate($_SESSION['search_results']);
        if ($cvForm != null) {
            $renderer =& $cvForm->defaultRenderer();
            $cvForm->accept($renderer);
        }

        $this->contentPre .= $renderer->toHtml();

        $table = new HTML_Table(array('id' => 'publist',
                                      'width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '0',
                                      'cellspacing' => '0'));

        $b = 0;
        foreach ($pubs->list as $pub) {
            // get all info for this pub
            $pub->dbload($this->db, $pub->pub_id);

            $table->addRow(array(($b+1),
                                 $pub->getCitationHtml()
                                 . $this->getPubIcons($pub)));
            $b++;
        }

        for ($i = 0; $i < $table->getRowCount(); $i++) {
            if ($i & 1)
                $table->updateRowAttributes($i, array('class' => 'even'), true);
            else
                $table->updateRowAttributes($i, array('class' => 'odd'), true);
            $table->updateCellAttributes($i, 1, array('id' => 'publist'), true);
        }
        $table->updateColAttributes(0, array('class' => 'emph',
                                             'id' => 'publist'), true);

        $searchLinkTable = new HTML_Table(array('id' => 'searchlink',
                                                'border' => '0',
                                                'cellpadding' => '0',
                                                'cellspacing' => '0'));
        $searchLinkTable->addRow(
            array('<a href="' . $search_url . '">'
                  . '<img src="images/link.png" title="view" alt="view" '
                  . 'height="16" width="16" border="0" align="top" />'
                  . ' Link to this search</a></div><br/>'));

        $this->contentPre .= $table->toHtml()
            . '<hr/>' . $searchLinkTable->toHtml();

        $this->db->close();
    }

    /**
     *
     */
    function cvFormCreate(&$result_pubs) {
        if ($result_pubs == null) return;

        $form = new HTML_QuickForm('cvForm', 'post', 'cv.php', '_blank',
                                   'multipart/form-data');
        $form->addElement('hidden', 'pub_ids', implode(",", $result_pubs));
        $form->addElement('submit', 'submit', 'Output these results to CV format');

        return $form;
    }
}

$page = new search_results();
echo $page->toHtml();

?>
