<?php ;

/**
 * \file
 *
 * \brief Lists all the authors in database.
 *
 * Makes each author a link to it's own seperate page. If a user is logged in,
 * he/she has the option of adding a new author, editing any of the authors and
 * deleting any of the authors.
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'HTML/Table.php';

makePage();

function makePage() {
    global $logged_in;

    /* Connecting, selecting database */
    $db =& dbCreate();

    /* Performing SQL query */
    $q = $db->select(array('author'), '*', '', "list_author.php",
                     array('ORDER BY' => 'name ASC'));
    $r = $db->fetchObject($q);
    while ($r) {
        $auth_array[] = $r;
        $r = $db->fetchObject($q);
    }

    htmlHeader('Authors');
    print "<body>";
    pageHeader();
    navigationMenu();

    print "<div id='content'>\n"
        . "<h2><u>Authors<h2>";


    /* This portion is used for when a new author has been added. It either
     * says it was successful or not and then brings the user to this full
     * page.
     */
    if ($logged_in) {
        if ($newauthor == "true") {
            echo "Author submitted successfully. You will be transported back to "
                . "the author page in 0.01 seconds.\n"
                . "<script language='JavaScript' type='text/JavaScript'>\n"
                . "location.replace('./list_author.php?type=view&admin=true');\n"
                . "</script>\n";
            exit();
        }

        if ($repeat == "true") {
            echo "<script language='Javascript'>\n"
                . "alert (\"Author already exists.\")\n"
                . "</script>\n";
        }
    }

    $tableAttrs = array('width' => '100%',
                        'border' => '0',
                        'cellpadding' => '6',
                        'cellspacing' => '0');
    $table = new HTML_Table($tableAttrs);
    $table->setAutoGrow(true);

    if (count($auth_array) > 0) {
        foreach ($auth_array as $auth) {
            unset($cells);
            $cells[] = "<a href='view_author.php?author_id=" . $auth->author_id
                . "'>" . $auth->name . "</a>";
            $attr[] = '';
            if ($logged_in) {
                $cells[] = "<a href='Admin/edit_author.php?pub_id="
                    . $auth->author_id . "'>Edit</a>";
                $cells[] = "<a href='Admin/delete_author.php?pub_id="
                    . $auth->author_id . "'>Delete</a>";
            }

            $table->addRow($cells);
        }
    }
    else {
        $table->addRow(array('No Authors'));
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

    print  $table->toHtml() . "</div>";

    $db->close();

    pageFooter();

    echo "</body>\n</html>\n";
}

?>

