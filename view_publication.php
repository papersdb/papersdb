<?php ;

// $Id: view_publication.php,v 1.63 2007/03/10 01:23:05 aicmltec Exp $

/**
 * View Publication
 *
 * Given a publication id number this page shows most of the information about
 * the publication. It does not display the extra information which is hidden
 * and used only for the search function. It provides links to all the authors
 * that are included. If a user is logged in, then there is an option to edit
 * or delete the current publication.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdAttachmentTypesList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class view_publication extends pdHtmlPage {
    var $debug = 0;
    var $pub_id;

    function view_publication() {
        global $access_level;

        pubSessionInit();
        parent::pdHtmlPage('view_publication');

        if (!isset($_GET['pub_id'])) {
            $this->pageError = true;
            return;
        }

        $db = dbCreate();
        $this->pub_id = intval($_GET['pub_id']);
        isValid($this->pub_id);

        $pub = new pdPublication();
        $result = $pub->dbLoad($db, $this->pub_id);

        if (!$result) {
            $this->contentPre .= 'Publication does not exist';
            return;
        }

        if ($this->debug) {
            $this->contentPost .= 'pub<pre>' . print_r($pub, true) . '</pre>';
        }

        $content = "<h1>" . $pub->title;

        if ($access_level > 0) {
            $content
                .= '&nbsp;&nbsp;<a href="Admin/add_pub1.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="images/pencil.png" title="edit" alt="edit" '
                . 'height="16" width="16" border="0" align="top" /></a>'
                . '<a href="Admin/delete_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="images/kill.png" title="delete" alt="delete" '
                . 'height="16" width="16" border="0" align="top" /></a>';
        }

        $content .= "</h1>\n" . $pub->authorsToHtml();

        if (($pub->paper != 'No paper')
            && (basename($pub->paper) != 'paper_')) {

            $path = FS_PATH;
            if (strpos($pub->paper, 'uploaded_files/') === false)
                $path .= '/uploaded_files/' . $pub->pub_id . '/';
            $path .= $pub->paper;

            if (file_exists($path)) {
                $content .= 'Full Text: <a href="' . $pub->paperAttGetUrl()
                    . '">';

                if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
                    $content .= '<img src="images/pdf.gif" alt="PDF" '
                        . 'height="18" width="17" border="0" align="middle">';
                }
                else if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
                    $content .= '<img src="images/ppt.gif" alt="PPT" height="18" '
                        . 'width="17" border="0" align="middle">';
                }
                else if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
                    $content .= '<img src="images/ps.gif" alt="PS" height="18" '
                        . 'width="17" border="0" align="middle">';
                }
                else {
                    $name = split('paper_', $pub->paper);
                    if ($name[1] != '')
                        $content .= $name[1];
                }
                $content .= '</a><br/>';
            }
        }

        // Show Additional Materials
        $att_types = new pdAttachmentTypesList($db);

        if (count($pub->additional_info) > 0) {
            $table = new HTML_Table(array('width' => '350',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));

            $heading = 'Other Attachments:';

            $add_count = 1;
            foreach ($pub->additional_info as $att) {
                $cell = '';

                $path = FS_PATH;
                if (strpos($att->location, 'uploaded_files/') === false)
                    $path .= '/uploaded_files/';
                $path .= $att->location;

                if (file_exists($path)) {
                    $name = split('additional_', $att->location);

                    $cell .= '<a href="'
                        . $pub->attachmentGetUrl($add_count - 1) . '">';

                    if (preg_match("/\.(pdf|PDF)$/", $att->location)) {
                        $cell .= '<img src="images/pdf.gif" alt="PDF" '
                            . 'height="18" width="17" border="0" '
                            . 'align="middle">';
                    }
                    else if (preg_match("/\.(ppt|PPT)$/", $att->location)) {
                        $cell .= '<img src="images/ppt.gif" alt="PPT" '
                            . 'height="18" width="17" border="0" '
                            . 'align="middle">';
                    }
                    else if (preg_match("/\.(ps|PS)$/", $att->location)) {
                        $cell .= '<img src="images/ps.gif" alt="PS" '
                            . 'height="18" width="17" border="0" '
                            . 'align="middle">';
                    }
                    else {
                        if ($name[1] != '')
                            $cell .= $name[1];
                    }

                    $cell .= '</a>';

                    if (in_array($att->type, $att_types->list))
                        $cell .= '&nbsp;[' . $att->type . ']';

                    $add_count++;
                }

                $table->addRow(array($heading, $cell));
                $heading = '';
            }

            $content .= $table->toHtml();
        }

        $content .= '<p/>' . stripslashes(nl2br($pub->abstract)) . '<p/>'
            . '<h3>Citation</h3>' . $pub->getCitationHtml(). '<p/>';

        $table = new HTML_Table(array('width' => '600',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));

        $table->addRow(array('Category:', $pub->category->category));
        $table->addRow(array('Keywords:', $pub->keywordsGet()));
        $table->addRow(array('Extra Info:', $pub->extraInfoGet()));

        if ($pub->user != '')
            $table->addRow(array('User Info:', $pub->user));

        if (count($pub->web_links) > 0) {
            $c = 0;
            foreach ($pub->web_links as $name => $url) {
                if ($c == 0)
                    $label = 'Web Links:';
                else
                    $label = '';
                $table->addRow(array($label, '<a href="' . $url . '" '
                                     . 'target="_blank">' . $name . '</a>'));
                $c++;
            }
        }

        if (count($pub->pub_links) > 0) {
            $c = 0;
            foreach ($pub->pub_links as $link_pub_id) {
                if ($c == 0)
                    $label = 'Publication Links:';
                else
                    $label = '';
                $linked_pub = new pdPublication();
                $linked_pub->dbLoad($db, $link_pub_id);

                $table->addRow(array($label, '<a href="view_publication.php?'
                                     . 'pub_id=' . $linked_pub->pub_id . '" '
                                     . ' target="_blank">'
                                     . $linked_pub->title . '</a>'));
                $c++;
            }
        }

        $table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));

        $content .= $table->toHtml();

        $bibtex = $pub->getBibtex();
        if ($bibtex !== false)
        $content .= '<h3>BibTeX</h3><pre>' . $bibtex . '</pre><p/>';

        $updateStr = $this->lastUpdateGet($pub);
        if ($updateStr != '') {
            $updateStr ='Last Updated: ' . $updateStr . '<br/>';
        }
        $updateStr .= 'Submitted by ' . $pub->submit;

        $this->contentPre .= $content . '<span id="small">' . $updateStr
            . '</span>';

        $db->close();
    }

    function lastUpdateGet(&$pub) {
        $string = "";
        $published = split("-",$pub->updated);
        if($published[1] != 00)
            $string .= date("F", mktime (0,0,0,$published[1]))." ";
        if($published[2] != 00)
            $string .= $published[2].", ";
        if($published[0] != 0000)
            $string .= $published[0];
        return $string;
    }
}

session_start();
$access_level = check_login();
$page = new view_publication();
echo $page->toHtml();

?>
