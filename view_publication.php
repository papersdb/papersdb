<?php ;

// $Id: view_publication.php,v 1.87 2008/02/11 22:20:58 loyola Exp $

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
    private $debug = 0;
    protected $pub_id;
    protected $submit_pending;
    protected $submit;

    public function __construct() {
        parent::__construct('view_publication', 'View Publication',
                           'view_publication.php');
        
        if ($this->loginError) return;

        $this->loadHttpVars();

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
        
        if (isset($this->submit_pending) && $this->submit_pending) {
            // check if this pub entry is pending
            $q = $this->db->selectRow('pub_pending', '*',
                array('pub_id' => $this->pub_id));
                    
            assert('$q');
            $form = new HTML_QuickForm('submit_pending');
            $form->addElement('hidden', 'submit_pending', true);
            $form->addElement('hidden', 'pub_id', $this->pub_id);
            $elements = array();
            $elements[] = HTML_QuickForm::createElement(
            	'advcheckbox', 'valid', null, 'Valid', null, array(0, 1));
            $elements[] = HTML_QuickForm::createElement(
            	'submit', 'submit', 'Submit');
            $form->addGroup($elements, 'elgroup', '', '&nbsp', false);    
            
            // create a new renderer because $form->defaultRenderer() creates
            // a single copy
            $renderer = new HTML_QuickForm_Renderer_Default();
            $form->accept($renderer);

            
            if ($form->validate()) {
                $values =& $form->exportValues();
                $pub->markValid($this->db);
                echo 'Publication entry marked as valid.';
                return;
            }
            else {
                echo "<h2>This publication entry requires validation</h2>\n";
                echo $renderer->toHtml();  
            }
        }
        
        $this->showPublication($pub);
    }
    
    private function showPublication(&$pub) {
        $content = "<h2>" . $pub->title;

        if ($this->access_level > 0) {
            $content .= $this->getPubIcons($pub, 0xc);
        }

        $content .= "</h2>\n" . $pub->authorsToHtml();

        if (isset($pub->paper) && (strtolower($pub->paper) != 'no paper')
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
        $att_types = pdAttachmentTypesList::create($this->db);

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

                    if (in_array($att->type, $att_types))
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

        $table->addRow(array('Keywords:', $pub->keywordsGet()));
        $table->addRow(array('Category:', $category));

        if (isset($_SESSION['user'])
            && ($_SESSION['user']->showInternalInfo())) {
            $table->addRow(array('Ranking:', $pub->ranking));

            if (is_array($pub->collaborations)
                && (count($pub->collaborations) > 0)) {
                $col_desciptions = $pub->collaborationsGet($this->db);

                foreach ($pub->collaborations as $col_id) {
                    $values[] = $col_desciptions[$col_id];
                }

                $table->addRow(array('Collaboration:',
                                     implode(', ', $values)));
            }

            $table->addRow(array('Extra Info:', $pub->extraInfoGet()));
        }

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

        if (count($pub->relatedPubsGet()) > 0) {
            $c = 0;
            foreach ($pub->relatedPubsGet() as $related_pub_id) {
                if ($c == 0)
                    $label = 'Related Publication(s):';
                else
                    $label = '';
                $rel_pub = new pdPublication();
                $rel_pub->dbLoad($this->db, $related_pub_id);

                $table->addRow(array($label, '<a href="view_publication.php?'
                                     . 'pub_id=' . $rel_pub->pub_id . '" '
                                     . ' target="_blank">'
                                     . $rel_pub->title . '</a>'));
                $c++;
            }
        }

        $table->updateColAttributes(0, array('class' => 'emph',
                                             'width' => '25%'));

        $content .= $table->toHtml();

        $bibtex = $pub->getBibtex();
        if ($bibtex !== false)
        $content .= '<h3>BibTeX</h3><pre class="bibtex">' . $bibtex
            . '</pre><p/>';

        $updateStr = $this->lastUpdateGet($pub);
        if ($updateStr != '') {
            $updateStr ='Last Updated: ' . $updateStr . '<br/>';
        }
        $updateStr .= 'Submitted by ' . $pub->submit;

        echo $content, '<span class="small">', $updateStr, '</span>';
    }

    public function lastUpdateGet($pub) {
        $string = "";
        $published = split("-",$pub->updated);
        
        if (count($published) != 3) return false;
        
        if ($published[1] != 00)
            $string .= date("F", mktime (0,0,0,$published[1]))." ";
        if ($published[2] != 00)
            $string .= $published[2].", ";
        if ($published[0] != 0000)
            $string .= $published[0];
        return $string;
    }
}

$page = new view_publication();
echo $page->toHtml();

?>
