<?php ;

// $Id: search_results.php,v 1.1 2006/09/15 20:23:09 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
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

        $db =& dbCreate();
        $result_pubs =& $_SESSION['search_results'];
        $search_url =& $_SESSION['search_url'];

        $this->contentPre .= '<h3>SEARCH RESULTS</h3>';

        $table = new HTML_Table();

        $cvForm =& $this->cvFormCreate($result_pubs);
        if ($cvForm != null) {
            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $cvForm->accept($renderer);
            $table->addRow(array($renderer->toHtml()));
        }

        $this->contentPre .= $table->toHtml();

        if ($result_pubs == null) {
            $this->contentPre
                .= '<br/><h3>Your search did not generate any results.</h3>';
            return;
        }

        $table = new HTML_Table(array('class' => 'nomargins',
                                      'width' => '100%'));

        $b = 0;
        foreach ($result_pubs as $pub_id) {
            $pub = new pdPublication();
            $pub->dbLoad($db, $pub_id);

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

            // Show Additional Materials
            if (count($pub->additional_info) > 0) {
                $add_count = 1;
                foreach ($pub->additional_info as $att) {
                    $citation .= '<a href="'
                        . $pub->attachmentGetUrl($add_count - 1) . '">';

                    if (preg_match("/\.(pdf|PDF)$/", $att->location)) {
                        $citation .= '<img src="images/pdf.gif" alt="PDF" height="18" '
                            . 'width="17" border="0" align="middle">';
                    }

                    if (preg_match("/\.(ppt|PPT)$/", $att->location)) {
                        $citation .= '<img src="images/ppt.gif" alt="PPT" height="18" '
                            . 'width="17" border="0" align="middle">';
                    }

                    if (preg_match("/\.(ps|PS)$/", $att->location)) {
                        $citation .= '<img src="images/ps.gif" alt="PS" height="18" '
                            . 'width="17" border="0" align="middle">';
                    }

                    $add_count++;
                }
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
    function cvFormCreate() {
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
