<?php ;

// $Id: cv.php,v 1.9 2006/07/28 22:10:49 aicmltec Exp $

/**
 * \file
 *
 * \brief  This file outputs all the search results given to it in a CV format.
 *
 * This is mainly for the authors needing to publish there CV.  Given the ID
 * numbers of the publications, it extracts the information from the database
 * and outputs the data in a certain format.  Input: $_POST['pub_ids'] - a
 * string file of the publication ids seperated by commas Output: CV Format
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 */
class cv extends pdHtmlPage {
    function cv() {
        parent::pdHtmlPage('cv', null, false);

        if (!isset($_POST['pub_ids'])) {
            $this->pageError = true;
            return;
        }

        $db =& dbCreate();
        $pub_count = 0;
        foreach (split(",", $_POST['pub_ids']) as $pub_id) {
            $pub_count++;
            $this->contentPre .= "<b>[" . $pub_count . "]</b> ";

            $pub = new pdPublication();
            $pub->dbLoad($db, $pub_id);

            // AUTHORS - Outputs the Authors for the ID
            unset($authors);
            foreach ($pub->authors as $auth) {
                $authors[] = $auth->firstname[0] . ". " . $auth->lastname;
            }

            $this->contentPre
                .= implode(', ', array_slice($authors, 0, count($authors) - 1))
                . ' and ' . $authors[count($authors) - 1];

            //  Output the Title (if this doesn't exist we have a problem)
            $this->contentPre .= ', "' . $pub->title . '"';

            //  VENUE - Checks to see if its unique or an ID and takes the
            //  right action for each
            if (is_object($pub->venue)) {
                if (($pub->venue->name != null)
                    && ($pub->venue->data != null)) {
                    $this->contentPre .= ', ' . $pub->venue->type
                        . ': ' . $pub->venue->name . ', '
                        . $pub->venue->data;
                }
            }
            else if ($pub->venue != '') {
                // If no ID exist output the unique venue
                $this->contentPre .= ', ' . strip_tags($pub->venue);
            }

            // Additional Information - Outputs the category specific
            // information if it exists
            if (isset($pub->info))
                foreach ($pub->info as $name => $value) {
                    if($value != null)
                        $this->contentPre .= ", " . $value;
                }


            // DATE - Parses the date file, and the outputs it
            $string = "";
            $published = split("-", $pub->published);
            if($published[1] != 00)
                $string .= date("F", mktime (0,0,0,$published[1]))." ";
            if($published[2] != 00)
                $string .= $published[2].", ";
            if($published[0] != 0000)
                $string .= $published[0];

            if($string != "")
                $this->contentPre .= ', ' . $string . '.';
            else
                $this->contentPre .= '.';
            $this->contentPre .= '<p/>';
        }
        $db->close();
    }
}

$page = new cv();
echo $page->toHtml();

?>

