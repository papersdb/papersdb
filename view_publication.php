<?php ;

// $Id: view_publication.php,v 1.71 2007/03/20 16:47:19 aicmltec Exp $

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
        parent::pdHtmlPage('view_publication');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        if (!isset($this->pub_id) || !is_numeric($this->pub_id)) {
            $this->pageError = true;
            return;
        }

        $pub = new pdPublication();
        $result = $pub->dbLoad($this->db, $this->pub_id);

        if (!$result) {
            echo 'Publication does not exist';
            return;
        }

        if ($this->debug) {
            echo 'pub<pre>' . print_r($pub, true) . '</pre>';
        }

        $content = "<h1>" . $pub->title;

        if ($this->access_level > 0) {
            $content .= $this->getPubIcons($pub, 0xc);
        }

        $content .= "</h1>\n" . $pub->authorsToHtml();

        if (isset($pub->paper) && ($pub->paper != 'No paper')
            && (basename($pub->paper) != 'paper_')) {
            if ($pub->paperExists()) {
                $content .= 'Full Text: <a href="' . $pub->paperAttGetUrl()
                    . '">';

                $name = split('paper_', $pub->paper);
                if ($name[1] != '')
                    $content .= $name[1];
                $content .= '</a>&nbsp;';

                $content .= $this->getPubIcons($pub, 0x1) . "<br/>\n";

            }
        }

        // Show Additional Materials
        $att_types = new pdAttachmentTypesList($this->db);

        if (count($pub->additional_info) > 0) {
            $table = new HTML_Table(array('width' => '350',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));

            $heading = 'Other Attachments:';

            $add_count = 1;
            foreach ($pub->additional_info as $att) {
                $cell = '';

                if ($pub->attExists($att)) {
                    $name = split('additional_', $att->location);

                    $cell .= '<a href="'
                        . $pub->attachmentGetUrl($add_count - 1) . '">';

                    if ($name[1] != '')
                        $cell .= $name[1];

                    $cell .= '</a>';

                    if (in_array($att->type, $att_types->list))
                        $cell .= '&nbsp;[' . $att->type . ']';

                    $cell .= '&nbsp;<a href="'
                        . $pub->attachmentGetUrl($add_count - 1) . '">'
                        . $this->getPubAddAttIcons($att) . '</a>';

                    $add_count++;
                }

                $table->addRow(array($heading, $cell));
                $heading = '';
            }

            $content .= $table->toHtml();
        }

        $content .= '<p/>' . stripslashes($pub->abstract) . '<p/>'
            . '<h3>Citation</h3>' . $pub->getCitationHtml(). '<p/>';

        $table = new HTML_Table(array('width' => '600',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));

        $category = '';
        if (isset($pub->category) && isset($pub->category->category))
            $category = $pub->category->category;

        $table->addRow(array('Category:', $category));
        $table->addRow(array('Keywords:', $pub->keywordsGet()));

        if ($this->access_level >= 1)
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
                $linked_pub->dbLoad($this->db, $link_pub_id);

                $table->addRow(array($label, '<a href="view_publication.php?'
                                     . 'pub_id=' . $linked_pub->pub_id . '" '
                                     . ' target="_blank">'
                                     . $linked_pub->title . '</a>'));
                $c++;
            }
        }

        $table->updateColAttributes(0, array('class' => 'emph',
                                             'width' => '25%'));

        $content .= $table->toHtml();

        $bibtex = $pub->getBibtex();
        if ($bibtex !== false)
        $content .= '<h3>BibTeX</h3><pre>' . $bibtex . '</pre><p/>';

        $updateStr = $this->lastUpdateGet($pub);
        if ($updateStr != '') {
            $updateStr ='Last Updated: ' . $updateStr . '<br/>';
        }
        $updateStr .= 'Submitted by ' . $pub->submit;

        echo $content . '<span class="small">' . $updateStr
            . '</span>';
    }

    function lastUpdateGet($pub) {
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

$page = new view_publication();
echo $page->toHtml();

?>
