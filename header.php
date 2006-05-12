<?php
  /**
   * \file
   */

  /**
   *
   * This is the public header a viewer sees on all the pages except the main
   * page.
   */

function pdHeader() {
    $tableAttrs = array('width' => '500',
                        'border' => '0',
                        'cellpadding' => '0',
                        'cellspacing' => '0');
    $table = new HTML_Table($tableAttrs);

    $spacer_fmt = "<img src='template/spacer.gif' width='%d' height='1' "
        . "border='0' alt=''>";

    $table->addRow(array(sprintf($spacer_fmt, 70),
                         sprintf($spacer_fmt, 167),
                         sprintf($spacer_fmt, 116),
                         sprintf($spacer_fmt, 82),
                         sprintf($spacer_fmt, 65),
                         sprintf($spacer_fmt, 1),
                       ));

    $table->addRow(array("<a href= './'><img name='header_r1_c1' "
                         . "src='template/header_r1_c1.gif' width='500' "
                         . "height='94'border='0' alt=''></a>",
                         "", "", "", "",
                         sprintf($spacer_fmt, 94)));
    $table->updateCellAttributes($table->getRowCount() - 1, 0,
                                 array('colspan' => '5'));

    $loginCell = "<a href='./Admin/direct.php?q="
        . $_SERVER["HTTP_HOST"] . $PHP_SELF;
    if ($_SERVER['QUERY_STRING'] != "")
        $loginCell .= "?" . $_SERVER['QUERY_STRING'];
    $loginCell .= "'><img name='header_r2_c5' src='template/header_r2_c5.gif' "
        . "width='65' height='26' border='0' alt=''></a>";

    $table->addRow(array("<a href='./advanced_search.php'>"
                         . "<img name='header_r2_c1' "
                         . "src='template/header_r2_c1.gif' "
                         . "width='70' height='26' border='0' alt=''></a>",
                         "<input type='text' name='search' size='18' "
                         . "maxlength='250' value=''>",
                         "<a href='./list_publication.php?type=view'>"
                         . "<img name='header_r2_c3' "
                         . "src='template/header_r2_c3.gif'"
                         . "width='116' height='26' border='0' alt=''></a>",
                         "<a href='./list_author.php?type=view'>"
                         . "<img name='header_r2_c4' "
                         . "src='template/header_r2_c4.gif'"
                         . "width='82' height='26' border='0' alt=''></a>",
                         $loginCell,
                         sprintf($spacer_fmt, 26)));
    $table->updateCellAttributes($table->getRowCount() - 1, 1,
                                 array('id' => 'formbg'));


    print "<form name='header' action='search_publication_db.php'"
        . "method='post' enctype='multipart/form-data'>\n"
        . "<input type='hidden' name='titlecheck' value='true'>\n"
        . "<input type='hidden' name='authorcheck' value='true'>\n"
        . "<input type='hidden' name='halfabstractcheck' value='true'>\n"
        . "<input type='hidden' name='datecheck' value='true'>\n";
    echo $table->toHtml();
    print "</form>";
}

?>