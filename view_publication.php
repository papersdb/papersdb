<?php ;

// $Id: view_publication.php,v 1.45 2006/08/09 02:46:41 aicmltec Exp $

/**
 * \file
 *
 * \brief View Publication
 *
 * Given a publication id number this page shows most of the information about
 * the publication. It does not display the extra information which is hidden
 * and used only for the search function. It provides links to all the authors
 * that are included. If a user is logged in, then there is an option to edit
 * or delete the current publication.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 */
class view_publication extends pdHtmlPage {
    var $pub_id;

    function view_publication() {
        global $access_level;

        parent::pdHtmlPage('view_publications');

        if (!isset($_GET['pub_id'])) {
            $this->pageError = true;
            return;
        }

        $db =& dbCreate();
        $this->pub_id = intval($_GET['pub_id']);
        isValid($this->pub_id);

        $pub = new pdPublication();
        $result = $pub->dbLoad($db, $this->pub_id);

        if (!$result) {
            $this->contentPre .= 'Publication does not exist';
            return;
        }

        $content .= "<h1>" . $pub->title;

        if ($access_level > 0) {
            $content .= '<a href="Admin/add_publication.php?pub_id='
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

        // Show Additional Materials
        if (count($pub->additional_info) > 0) {
            $add_count = 1;
            foreach ($pub->additional_info as $att) {
                $content .= 'Other Attachments: <a href="'
                    . $pub->attachmentGetUrl($add_count - 1) . '">';

                if (preg_match("/\.(pdf|PDF)$/", $att->location)) {
                    $content .= '<img src="images/pdf.gif" alt="PDF" height="18" '
                        . 'width="17" border="0" align="middle">';
                }
                else if (preg_match("/\.(ppt|PPT)$/", $att->location)) {
                    $content .= '<img src="images/ppt.gif" alt="PPT" height="18" '
                        . 'width="17" border="0" align="middle">';
                }
                else if (preg_match("/\.(ps|PS)$/", $att->location)) {
                    $content .= '<img src="images/ps.gif" alt="PS" height="18" '
                        . 'width="17" border="0" align="middle">';
                }
                else {
                    $name = split('additional_', $att->location);
                    if ($name[1] != '')
                        $content .= $name[1];
                }

                $add_count++;
            }
            $content .= '</a><br/>';
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

        if (count($pub->extPointer) > 0) {
            $c = 0;
            foreach ($pub->extPointer as $name => $url) {
                if ($c == 0)
                    $label = 'Web Links:';
                else
                    $label = '';
                $table->addRow(array($label, '<a href="' . $url . '">'
                                     . $name . '</a>'));
                $c++;
            }
        }

        if (count($pub->intPointer) > 0) {
            $c = 0;
            foreach ($pub->intPointer as $int) {
                if ($c == 0)
                    $label = 'Publication Links:';
                else
                    $label = '';
                $linked_pub = new pdPublication();
                $linked_pub->dbLoad($db, $int->value);

                $table->addRow(array($label, '<a href=view_publication?pub_id="'
                                     . $int->value . '">'
                                     . $linked_pub->title . '</a>'));
                $c++;
            }
        }

        $table->addRow(array('Extra Info:', $pub->extraInfoGet()));

        $updateStr = $this->lastUpdateGet($pub);
        if ($updateStr != "") {
            $updateStr ='Last Updated: ' . $updateStr . '<br/>';
        }
        $updateStr .= 'Submitted by ' . $pub->submit;
        $table->addRow(array('&nbsp;', $updateStr));

        $table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));

        $content .= $table->toHtml();

        $bibtex = $pub->getBibtex();
        if ($bibtex !== false)
        $content .= '<h3>BibTex</h3><pre>' . $bibtex . '</pre><p/>';


        if ($access_level > 0)
            $content .= $actions;

        $this->contentPre .= $content;

        $db->close();
    }

    function additional2Html(&$pub) {
        if(count($pub->additional_info) == 0) return "No Additional Materials";

        $additionalMaterials = "";
        $add_count = 0;
        $temp = "";
        foreach ($pub->additional_info as $info) {
            $temp = split('additional_', $info->location);
            $additionalMaterials .= '<a href="'
                . $pub->attachmentGetUrl($add_count) . '">'
                . "<i><b>".$temp[1]."</b></i>";

            $additionalMaterials .= "</a><br/>";
            $add_count++;
        }
        return $additionalMaterials;
    }

    function venueRowsAdd(&$pub, &$table) {
        $venueStr = "";
        if ($pub->venue->url != '')
            $venueStr .= " <a href=\"" . $pub->venue->url
                . "\" target=\"_blank\">";

        $venueStr .= $pub->venue->name;
        if ($pub->venue->url != '')
            $venueStr .= "</a>";
        $table->addRow(array($pub->venue->type . ':', $venueStr));

        if($pub->venue->data != ""){
            $venueStr .= "</td></tr><tr><td width=\"25%\"><div id=\"emph\">";
            if ($pub->venue->type == "Conference")
                $venueStr = "Location:";
            else if ($pub->venue->type == "Journal")
                $venueStr = "Publisher:";
            else if ($pub->venue->type == "Workshop")
                $venueStr = "Associated Conference:";
            $table->addRow(array($venueStr, $pub->venue->data));
        }
    }

    function extPointerRowsAdd(&$pub, &$table) {
        if (count($pub->extPointer) == 0) return;

        foreach ($pub->extPointer as $name => $value) {
            if (strpos($value, 'http://') !== false)
                $cell = '<a href="' . $value . '">' . $value . '</a>';
            else
                $cell = $value;
            $table->addRow(array($name . ':', $cell));
        }
    }

    function intPointerRowsAdd(&$db, &$pub, &$table) {
        if (count($pub->intPointer) == 0) return;

        foreach ($pub->intPointer as $int) {
            $intPub = new pdPublication();
            $result = $intPub->dbLoad($db, $int->value);
            if ($result) {
                $intLinkStr = '<a href="view_publication.php?pub_id='
                    . $int->value . '">' . $intPub->title . '</a>';

                $table->addRow(array('Linked to:', $intLinkStr));
            }
        }
    }

    function publishDateGet(&$pub) {
        $string = "";
        $published = split("-",$pub->published);
        if($published[1] != 00)
            $string .= date("F", mktime (0,0,0,$published[1]))." ";
        if($published[2] != 00)
            $string .= $published[2].", ";
        if($published[0] != 0000)
            $string .= $published[0];
        return $string;
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
