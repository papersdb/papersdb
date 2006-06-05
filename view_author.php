<?php ;

// $Id: view_author.php,v 1.12 2006/06/05 04:28:41 aicmltec Exp $

/**
 * \file
 *
 * \brief Given a author id number, this displays all the info about
 * the author.
 *
 * If the author has only a few publications it will display the title and link
 * to them. If the author has more then 6 then it will link to a seperate page
 * of a list of publications by that author.
 *
 * if a user is logged in, they have the option of editing or deleting the
 * author.
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';
require_once 'includes/pdAuthor.php';

require_once 'HTML/Table.php';

htmlHeader('View Author');
echo "<body>\n";
pageHeader();
navMenu();
echo "<div id='content'>\n";

if (!isset($_GET['author_id'])) {
    errorMessage();
}

/* Connecting, selecting database */
$db =& dbCreate();

$auth = new pdAuthor();
$auth->dbLoad($db, $_GET['author_id'], (PD_AUTHOR_DB_LOAD_PUBS_MIN
                                        | PD_AUTHOR_DB_LOAD_INTERESTS));

// check if this author id is valid
if (!isset($auth->author_id)) {
    errorMessage();
}

echo "<h3>" . $auth->name . "</h3>";

$table =& authTableCreate($db, $auth);

echo $table->toHtml();

if ($logged_in) {
    echo "<br/><b><a href='Admin/edit_author.php?author_id="
        . $auth->author_id . "'>Edit this author</a>&nbsp;&nbsp;&nbsp;"
        . "<a href='Admin/delete_author.php?author_id="
        . $auth->author_id . "'>Delete this author</a></b><br/><br/>";
}

echo "</div>\n";
pageFooter();
echo "</body>\n</html>\n";

$db->close();

function authTableCreate(&$db, &$auth) {
    $tableAttrs = array('width' => '600',
                        'border' => '0',
                        'cellpadding' => '6',
                        'cellspacing' => '0');
    $table = new HTML_Table($tableAttrs);
    $table->setAutoGrow(true);

    $table->addRow(array('Name:', $auth->name));

    if (isset($auth->title) && (trim($auth->title) != "")) {
        $table->addRow(array('Title:', $auth->title));
    }

    $table->addRow(array('Email:',
                         "<a href='mailto:" . $auth->email . "'>"
                         . $auth->email . "</a>"));
    $table->addRow(array('Organization:', $auth->organization));

    if (isset($auth->webpage) && (trim($auth->webpage) != ""))
        $webpage = "<a href=\"" . $auth->webpage . "\" target=\"_blank\">"
            . $auth->webpage . "</a>";
    else
        $webpage = "none";

    $table->addRow(array('Webpage:', $webpage));

    $interestStr = '';
    if (is_array($auth->interest)) {
        foreach ($auth->interest as $interest) {
            $interestStr .= $interest . '<br/>';
        }
    }
    $table->addRow(array('Interest(s):', $interestStr));

    if ($auth->totalPublications > 0) {
        if ($auth->totalPublications <= 6) {
            assert('is_array($auth->pub_list->list)');
            $headingCell = 'Publications:';

            foreach ($auth->pub_list->list as $pub) {
                if (isset($pub->title) && ($pub->title != '')) {
                    $title = "<a href='view_publication.php?pub_id="
                        . $pub->pub_id . "'>". $pub->title . "</a>";
                    $table->addRow(array($headingCell, $title));
                }
                $headingCell = '';
            }
        }
        else {
            $table->addRow(array('Publications:',
                                 '<a href="./list_publication.php?'
                                 . 'type=view&author_id=' . $auth->author_id
                                 . '">View All Publications</a>'));
        }
    }
    else {
        $table->addRow(array('No publications by this author'),
                       array('colspan' => 2));
    }

    $table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));

    return $table;
}

?>
