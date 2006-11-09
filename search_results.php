<?php ;

// $Id: search_results.php,v 1.6 2006/11/09 20:49:58 aicmltec Exp $

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

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class search_results extends pdHtmlPage {
    var $debug = 0;

    function search_results() {
        global $access_level;

        parent::pdHtmlPage('search_results');

        if ($this->debug) {
            $this->contentPost .= '<pre>' . print_r($_SESSION, true) . '</pre>';
        }

        if (!isset($_SESSION['search_results'])
            || !isset($_SESSION['search_url'])) {
            $this->pageError = true;
            return;
        }

        if (count($_SESSION['search_results']) == 0) {
            $this->contentPre
                .= '<br/><h3>Your search did not generate any results.</h3>';
            return;
        }

        $db =& dbCreate();
        $search_url =& $_SESSION['search_url'];

        $pubs = new pdPubList($db, array('pub_ids'
                                         => $_SESSION['search_results']));

        $this->contentPre .= '<h3>SEARCH RESULTS</h3>';

        $cvForm =& $this->cvFormCreate($_SESSION['search_results']);
        if ($cvForm != null) {
            $renderer =& $cvForm->defaultRenderer();
            $cvForm->accept($renderer);
        }

        $this->contentPre .= $renderer->toHtml();

        $table = new HTML_Table(array('class' => 'nomargins',
                                      'width' => '100%'));

        $b = 0;
        foreach ($pubs->list as $pub) {
            // get all info for this pub
            $pub->dbload($db, $pub->pub_id);
            $pubTable = new HTML_Table();

            $citation = $pub->getCitationHtml();

            // Show Paper
            if ($pub->paper != 'No paper') {
                $citation .= '<a href="' . $pub->paperAttGetUrl() . '">';

                if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
                    $citation .= '<img src="images/pdf.gif" alt="PDF" '
                        . 'height="18" width="17" border="0" align="middle">';
                }

                if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
                    $citation .= '<img src="images/ppt.gif" alt="PPT" height="18" '
                        . 'width="17" border="0" align="middle">';
                }

                if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
                    $citation .= '<img src="images/ps.gif" alt="PS" height="18" '
                        . 'width="17" border="0" align="middle">';
                }
                $citation .= '</a>';
            }

            $pubTable->addRow(array($citation));

            $indexTable = new HTML_Table();

            $cell = ($b + 1)
                . '<br/><a href="view_publication.php?pub_id=' . $pub->pub_id . '">'
                . '<img src="images/viewmag.png" title="view" alt="view" height="16" '
                . 'width="16" border="0" align="middle" /></a>';

            if ($access_level > 0)
                $cell .= '<a href="Admin/add_pub1.php?pub_id='
                    . $pub->pub_id . '">'
                    . '<img src="images/pencil.png" title="edit" alt="edit" height="16" '
                    . 'width="16" border="0" align="middle" /></a>';

            $indexTable->addRow(array($cell), array('nowrap'));

            $table->addRow(array($indexTable->toHtml(), $pubTable->toHtml()));
            $b++;
        }

        tableHighlightRows($table);

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

        $db->close();
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

session_start();
$access_level = check_login();
$page = new search_results();
echo $page->toHtml();

?>
