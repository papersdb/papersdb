<?php ;

// $Id: list_publication.php,v 1.7 2006/05/18 20:45:36 aicmltec Exp $

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

ini_set("include_path", ini_get("include_path") . ":.:./includes:./HTML");

require_once('functions.php');
require_once('check_login.php');
require_once('pdAuthor.php');

htmlHeader('Publications');
print "<body>\n";
pageHeader();
navigationMenu();
print "<div id='content'>\n";

/* Connecting, selecting database */
$db =& dbCreate();

if (isset($_GET['author_id'])) {
    // If there exists an author_id, only extract the publications with that
    // author
    //
    // This is used when viewing an author.
    $q = $db->select(array('publication', 'pub_author'),
                     array('publication.pub_id', 'publication.title',
                           'publication.paper', 'publication.abstract',
                           'publication.keywords', 'publication.published',
                           'publication.updated'),
                     array('pub_author.pub_id = publication.pub_id',
                           'pub_author.author_id'
                           => quote_smart($_GET['author_id'])),
                     "list_publication.php",
                     array('ORDER BY' => 'publication.title ASC'));
    $r = $db->fetchObject($q);
    while ($r) {
        $pub_array[] = $r;
        $r = $db->fetchObject($q);
    }
}
else {
    // Otherwise just get all publications
    $q = $db->select(array('publication'), '*', '', "list_publication.php",
                     array('ORDER BY' => 'title ASC'));
    $r = $db->fetchObject($q);
    while ($r) {
        $pub_array[] = $r;
        $r = $db->fetchObject($q);
    }
}

print "<h2><b><u>Publications";

if (isset($_GET['author_id'])) {
    $auth = new pdAuthor();
    $auth->dbLoad($db, $_GET['author_id'], PD_AUTHOR_DB_LOAD_BASIC);

    print " by " . $auth->name;
}

print "</u></b></h2>\n";

$tableAttrs = array('width' => '100%',
                    'border' => '0',
                    'cellpadding' => '6',
                    'cellspacing' => '0');
$table = new HTML_Table($tableAttrs);
$table->setAutoGrow(true);

if (count($pub_array) > 0) {
    foreach ($pub_array as $pub) {
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

/* now assign table attributes including highlighting for even and odd rows */
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

print  $table->toHtml();

if (count($pub_array) < 5) {
    // add extra space to make the page display well
    print "<br/><br/><br/>\n";
}

print "</div>";

$db->close();

pageFooter();

echo "</body>\n</html>\n";

?>


