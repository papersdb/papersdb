<?php ;

// $Id: list_publication.php,v 1.15 2006/06/05 04:28:41 aicmltec Exp $

/**
 * \file
 *
 * \brief Lists all the publications in database.
 *
 * Makes each publication a link to it's own seperate page.  If a user is
 * logged in, he/she has the option of adding a new publication, editing any of
 * the publications and deleting any of the publications.
 *
 * Pretty much identical to list_author.php
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/pdPubList.php';

require_once 'HTML/Table.php';

/* Connecting, selecting database */
$db =& dbCreate();

if (isset($_GET['author_id'])) {
    // If there exists an author_id, only extract the publications for that
    // author
    //
    // This is used when viewing an author.
    $pub_list = new pdPubList($db, $_GET['author_id']);
}
else {
    // Otherwise just get all publications
    $pub_list = new pdPubList($db);
}

htmlHeader('Publications');
echo "<body>\n";
pageHeader();
navMenu('all_publications');
echo "<div id='content'>\n";
echo "<h2><b><u>Publications";

if (isset($_GET['author_id'])) {
    $auth = new pdAuthor();
    $auth->dbLoad($db, $_GET['author_id'], PD_AUTHOR_DB_LOAD_BASIC);

    echo " by " . $auth->name;
}

echo "</u></b></h2>\n";

$tableAttrs = array('width' => '100%',
                    'border' => '0',
                    'cellpadding' => '6',
                    'cellspacing' => '0');
$table = new HTML_Table($tableAttrs);
$table->setAutoGrow(true);

if (count($pub_list->list) > 0) {
    foreach ($pub_list->list as $pub) {
        unset($cells);
        $cells[] = "<a href='view_publication.php?pub_id=" . $pub->pub_id
            . "'>" . $pub->title . "</a>";
        $attr[] = '';
        if ($logged_in) {
            $cells[] = "<a href='Admin/add_publication.php?pub_id="
                . $pub->pub_id . "'>Edit</a>";
            $cells[] = "<a href='Admin/delete_publication.php?pub_id="
                . $pub->pub_id . "'>Delete</a>";
        }

        $table->addRow($cells);
    }
}
else {
    $table->addRow(array('No Publications'));
}

/* now assign table attributes including highlighting for even and odd
 * rows */
for ($i = 0; $i < $table->getRowCount(); $i++) {
    $table->updateCellAttributes($i, 0, array('class' => 'standard'));

    if ($i & 1) {
        $table->updateRowAttributes($i, array('class' => 'even'), true);
    }
    else {
        $table->updateRowAttributes($i, array('class' => 'odd'), true);
    }

    if ($logged_in) {
        $table->updateCellAttributes($i, 1, array('id' => 'emph',
                                                  'class' => 'small'));
        $table->updateCellAttributes($i, 2, array('id' => 'emph',
                                                  'class' => 'small'));
    }
}

echo  $table->toHtml();

if (count($pub_list->list) < 5) {
    // add extra space to make the page display well
    echo "<br/><br/><br/>\n";
}

echo "</div>";

$db->close();

pageFooter();

echo "</body>\n</html>\n";

?>


