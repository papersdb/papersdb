<?php ;

// $Id: add_publication.php,v 1.26 2006/06/13 21:54:42 aicmltec Exp $

/**
 * \file
 *
 * \brief This page is the form for adding/editing a publication.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdPubList.php';

class add_publication extends pdHtmlPage {
    function add_publication() {
        parent::pdHtmlPage('add_publication');
        $db =& dbCreate();

        if (isset($_GET['venue_id']) && ($_GET['venue_id'] != '')) {
            $venue_id = intval($_GET['venue_id']);
            $venue = new pdVenue();
            $venue->dbLoad($db, $venue_id);
        }
        else
            $venue_id = null;

        if (isset($_GET['cat_id']) && ($_GET['cat_id'] != '')) {
            $cat_id = intval($_GET['cat_id']);
            if ($cat_id > 0) {
                $category = new pdCategory();
                $result = $category->dbLoad($db, $cat_id);
                assert('$result');
            }
        }
        else
            $cat_id = null;

        if (isset($_GET['ext']) && ($_GET['ext'] != ''))
            $ext = intval($_GET['ext']);
        else
            $ext = 0;

        if (isset($_GET['intpoint']) && ($_GET['intpoint'] != ''))
            $intpoint = intval($_GET['intpoint']);
        else
            $intpoint = 0;

        if (isset($_GET['numMaterials']) && $_GET['numMaterials'] != '')
            $numMaterials = intval($_GET['numMaterials']);
        else
            $numMaterials = 0;

        $form = new HTML_QuickForm('pubForm');

        if ($edit) {
            $form->addElement('hidden', 'pub_id', $_GET['pub_id']);
        }

        // Venue
        if (($venue_id > 0) && ($cat_id == null)) {
            $category = new pdCategory();

            if (($category->category == '')
                || ($category->category == 'In Conference')
                || ($category->category == 'In Workshop')
                || ($category->category == 'In Journal')) {
                if ($venue->type == 'Conference') {
                    $result = $category->dbLoad($db, null,'In Conference');
                    assert('$result');
                }
                else if ($venue->type == 'Workshop') {
                    $result = $category->dbLoad($db, null,'In Workshop');
                    assert('$result');
                }
                else if ($venue->type == 'Journal') {
                    $result = $category->dbLoad($db, null,'In Journal');
                    assert('$result');
                }
            }
        }

        $venue_list = new pdVenueList($db);
        $options = array(''   => '--- Select a Venue ---',
                         -1 => '-- Add New Venue to Database--',
                         -2 => 'No Venue',
                         -3 => 'Unique Venue');
        $options += $venue_list->list;
        $form->addElement('select', 'venue_id', null, $options,
                          array('onChange' => 'dataKeep(\'none\');'));

        // Category
        unset($options);
        $category_list = new pdCatList($db);
        $options = array('' => '--- Please Select a Category ---',
                         -1 => '-- Add New Category to Database--');
        $options += $category_list->list;
        $form->addElement('select', 'cat_id', null, $options,
                          array('onChange' => 'dataKeep(\'none\');'));

        if (isset($category) && is_object($category)
            && is_array($category->info)) {
            foreach (array_values($category->info) as $name) {
                $form->addElement('text', $name, null,
                                  array('size' => 50, 'maxlength' => 250));
            }
        }

        // title
        $form->addElement('text', 'title', null,
                          array('size' => 60, 'maxlength' => 250));

        // Authors
        $auth_list = new pdAuthorList($db);
        $authSelect =& $form->addElement('advmultiselect', 'authors',
                                         null, $auth_list->list,
                                         array('class' => 'pool',
                                               'style' => 'width:150px;'),
                                         SORT_ASC);
        $authSelect->setLabel(array('Authors:', 'Selected', 'Available'));
        $authSelect->setButtonAttributes('add',
                                         array('value' => '<<',
                                               'class' => 'inputCommand'));
        $authSelect->setButtonAttributes('remove',
                                         array('value' => '>>',
                                               'class' => 'inputCommand'));
        $authSelect->setButtonAttributes('moveup',
                                         array('class' => 'inputCommand'));
        $authSelect->setButtonAttributes('movedown',
                                         array('class' => 'inputCommand'));

        // template for a dual multi-select element shape
        $template =
            '<table{class}>'
            . '<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->'
            . '<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->'
            . '<tr>'
            . '  <td>{selected}</td>'
            . '  <td align="center">'
            . '    {add}<br />{remove}<br /><br />{moveup}<br />{movedown}'
            . '  </td>'
            . '  <td>{unselected}</td>'
            . '</tr>'
            . '</table>'
            . '{javascript}';
        $authSelect->setElementTemplate($template);

        $form->addElement('advcheckbox', 'add_author', null,
                          'Add new author(s) to database and this publication',
                          null, array('no', 'yes'));

        $form->addElement('textarea', 'abstract', null,
                          array('cols' => 60, 'rows' => 10));

        if ($venue_id == -3)
            $form->addElement('textarea', 'venue_name', null,
                              array('cols' => 60, 'rows' => 5));
        $form->addElement('textarea', 'extra_info', null,
                          array('cols' => 60, 'rows' => 5));
        $form->addElement('button', 'extra_info_select',
                          'Select from a list of previously used information options',
                          'onClick="dataKeepPopup(\'extra_info.php\');"');

        $form->addElement('hidden', 'ext', $ext);

        if ($ext == 0) {
            $form->addElement('button', 'ext_ptr_add',
                              'Add an External Pointer',
                              'onClick="dataKeep(\'addext\');"');
        }
        else {
            $form->addElement('button', 'ext_ptr_add',
                              'Add Another External Pointer',
                              'onClick="dataKeep(\'addext\');"');
            $form->addElement('button', 'ext_ptr_remove',
                              'Remove the above Pointer',
                              'onClick="dataKeep(\'remext\');"');

            for ($e = 1; $e <= $ext; $e++) {
                $form->addElement('text', 'extname' . $e, null,
                                  array('size' => 12, 'maxlength' => 250));
                $form->addElement('text', 'extvalue' . $e, null,
                                  array('size' => 18, 'maxlength' => 250));
                $form->addElement('text', 'extlink' . $e, null,
                                  array('size' => 25, 'maxlength' => 250));
            }
        }

        $form->addElement('hidden', 'intpoint', $intpoint);

        if ($intpoint == 0) {
            $form->addElement('button', 'int_ptr_add',
                              'Add an Internal Pointer',
                              'onClick="dataKeep(\'addint\');"');
        }
        else {
            $form->addElement('button', 'int_ptr_add',
                              'Add Another Internal Pointer',
                              'onClick="dataKeep(\'addint\');"');
            $form->addElement('button', 'int_ptr_remove',
                              'Remove the above Pointer',
                              'onClick="dataKeep(\'remint\');"');

            $pub_list = new pdPubList($db);
            unset($options);
            $options[''] = '--- Link to a publication --';
            foreach ($pub_list->list as $pub) {
                if (strlen($pub->title) > 70)
                    $options[$pub->pub_id] = substr($pub->title, 0, 67) . '...';
                else
                    $options[$pub->pub_id] = $pub->title;
            }
            for ($e = 1; $e <= $intpoint; $e++) {
                $form->addElement('select', 'intpointer' . $e, null, $options);
            }
        }

        $form->addElement('text', 'keywords', null,
                          array('size' => 55, 'maxlength' => 250));

        $form->addElement('text', 'date_published', null,
                          array('size' => 10, 'maxlength' => 10));

        $form->addElement('radio', 'nopaper', null, null, 'false');
        $form->addElement('radio', 'nopaper', null, 'no paper at this time', 'true');
        $form->addElement('file', 'uploadpaper', null,
                          array('size' => 45, 'maxlength' => 250));

        // other materials
        $form->addElement('hidden', 'numMaterials', $numMaterials);
        $form->addElement('button', 'materials_add',
                          'Add Other Material',
                          'onClick="dataKeep(\'addnum\');"');

        if ($numMaterials > 0) {
            $form->addElement('button', 'materials_remove',
                              'Remove This Material',
                              'onClick="dataKeep(\'remnum\');"');
            for ($i = 1; $i <= $numMaterials; $i++) {
                $form->addElement('text', 'type' . $i, null,
                                  array('size' => 17, 'maxlength' => 250));
                $form->addElement('text', 'uploadadditional' . $i, null,
                                  array('size' => 50, 'maxlength' => 250));
            }
        }

        $form->addElement('submit', 'Save', 'Add Publication');
        $form->addElement('reset', 'Clear', 'Clear');

        //
        // Set form defaults
        //
        $form->setDefaults($_GET);

        if (isset($category) && is_object($category)) {
            $form->setDefaults(array('cat_id' => $category->cat_id));
        }

        if ($numMaterials > 0) {
            for ($i = 1; $i <= $numMaterials; $i++) {
                if (!isset($_GET['type' . $i]) || ($_GET['type' . $i] = '')) {
                    $materials['type' . $i] = 'Additional Material ' . $i;
                }
            }
            $form->setDefaults($materials);
        }

        if ($ext > 0) {
            for ($e = 1; $e <= $ext; $e++) {
                if (!isset($_GET['extname'.$e]) || $_GET['extname'.$e] == '')
                    $defaults['extname'.$e] = "Pointer Type";
                if (!isset($_GET['extvalue'.$e]) || $_GET['extvalue'.$e] == '')
                    $defaults['extvalue'.$e] = "http://";
                if (!isset($_GET['extlink'.$e]) || $_GET['extlink'.$e] == '')
                    $defaults['extlink'.$e] = "Title of link";
            }
            $form->setDefaults($defaults);
        }

        $rend = new HTML_QuickForm_Renderer_QuickHtml();
        $form->accept($rend);

        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));
        $table->setAutoGrow(true);

        $table->addRow(array('<hr/>'), array('colspan' => 2));
        $table->addRow(array('<a name="step1"></a>Step 1:'));
        $table->addRow(array($this->helpTooltip('Publication Venue', 'venueHelp')
                             . ':',
                             $rend->elementToHtml('venue_id')));

        if ($venue_id == -3) {
            $table->addRow(array('Unique Venue:'
                                 . '<br/><div id="small">HTML Enabled</div>',
                                 $rend->elementToHtml('venue_name')));
        }
        $table->addRow(array($this->helpTooltip('Category', 'categoryHelp')
                             . ':',
                             $rend->elementToHtml('cat_id')));
        $table->addRow(array($this->helpTooltip('Title', 'titleHelp') . ':',
                             $rend->elementToHtml('title')));
        $table->addRow(array($this->helpTooltip('Author(s)', 'authorsHelp') . ':',
                             $rend->elementToHtml('authors')));
        $table->addRow(array('', $rend->elementToHtml('add_author')));
        $table->addRow(array($this->helpTooltip('Abstract', 'abstractHelp')
                             . ':<br/><div id="small">HTML Enabled</div>',
                             $rend->elementToHtml('abstract')));

        // Show venue info
        if ($venue_id > 0) {
            assert('is_object($venue)');
            $cell1 = '';
            $cell2 = '';

            if ($venue->type != '')
                $cell1 .= $venue->type;

            if ($venue->url != '')
                $cell2 .= '<a href="' . $venue->url
                    . '" target="_blank">';

            if ($venue->name != '')
                $cell2 .= $venue->name;

            if ($venue->url != '')
                $cell2 .= '</a>';

            $table->addRow(array($cell1 . ':', $cell2));

            $cell1 = '';
            if ($venue->type == 'Conference')
                $cell1 = 'Location:';
            else if ($venue->type == 'Journal')
                $cell1 = 'Publisher:';
            else if ($venue->type == 'Workshop')
                $cell1 = 'Associated Conference:';

            $table->addRow(array($cell1, $venue->data));
        }

        $table ->addRow(array($this->helpTooltip('Extra Information',
                                                 'extraInfoHelp')
                              . ':<br/><div id="small">optional</div>',
                              $rend->elementToHtml('extra_info')
                              . $rend->elementToHtml('extra_info_select')));

        // External Pointers
        if ($ext == 0) {
            $table->addRow(array('<a name="pointers"></a>'
                                 . $this->helpTooltip('External Pointers',
                                                      'externalPtrHelp')
                                 . ':<br/><div id="small">optional</div>',
                                 $rend->elementToHtml('ext_ptr_add')));
        }
        else {
            for ($e = 1; $e <= $ext; $e++) {
                $cell = '';
                if ($e == 1) {
                    $cell = '<a name="pointers"></a>'
                        . $this->helpTooltip('External Pointers',
                                             'externalPtrHelp')
                        . '<br/><div id="small">optional</div>';
                }

                $table->addRow(array($cell,
                                     $rend->elementToHtml('extname'.$e)
                                     . ' ' . $rend->elementToHtml('extvalue'.$e)
                                     . ' ' . $rend->elementToHtml('extlink'.$e)));

            }
            $table->addRow(array('',
                                 $rend->elementToHtml('ext_ptr_add')
                                 . '&nbsp;' .
                                 $rend->elementToHtml('ext_ptr_remove')));
        }

        // Internal Pointers
        if ($intpoint == 0) {
            $table->addRow(array($this->helpTooltip('Internal Pointers',
                                                    'internalPtrHelp')
                                 . ':<br/><div id="small">optional</div>',
                                 $rend->elementToHtml('int_ptr_add')));
        }
        else {
            for ($e = 1; $e <= $intpoint; $e++) {
                $cell = '';
                if ($e == 1)
                    $cell = $this->helpTooltip('Internal Pointers', 'internalPtrHelp')
                        . ':<br/><div id="small">optional</div>';
                $table->addRow(array($cell,
                                     $rend->elementToHtml('intpointer' . $e)));
            }
            $table->addRow(array('',
                                 $rend->elementToHtml('int_ptr_add')
                                 . '&nbsp;' .
                                 $rend->elementToHtml('int_ptr_remove')));
        }

        $table->addRow(array($this->helpTooltip('Keywords', 'keywordsHelp') . ':',
                             $rend->elementToHtml('keywords')
                             . ' <div id="small">separate using semicolon (;)</div>'));

        // Additional Information
        if (isset($category) && is_object($category)
            && is_array($category->info)) {
            foreach (array_values($category->info) as $name) {
                $table->addRow(array($name . ':', $rend->elementToHtml($name)));
            }
        }

        $table->addRow(array($this->helpTooltip('Date Published', 'datePublishedHelp') . ':',
                             $rend->elementToHtml('date_published')
                             . '<a href="javascript:doNothing()" '
                             . 'onClick="setDateField('
                             . 'document.pubForm.date_published);'
                             . 'top.newWin=window.open(\'../calendar.html\','
                             . '\'cal\',\'dependent=yes,width=230,height=250,'
                             . 'screenX=200,screenY=300,titlebar=yes\')">'
                             . '<img src="../calendar.gif" border=0></a> '
                             . '(yyyy-mm-dd) '
                           ));

        $table->addRow(array('<hr/>'), array('colspan' => 2));
        $table->addRow(array('<a name="step2"></a>Step 2:'));
        $table->addRow(array('Paper:',
                             $rend->elementToHtml('nopaper', 'false')
                             . ' ' . $rend->elementToHtml('uploadpaper')));
        $table->addRow(array('', $rend->elementToHtml('nopaper', 'true')));
        if ($numMaterials > 0) {
            $table->addRow(array('Additional Materials:'));

            for ($i = 1; $i <= $numMaterials; $i++) {
                $table->addRow(array($rend->elementToHtml('type' . $i),
                                     ':' . $rend->elementToHtml('uploadadditional' . $i)));
            }
            $table->addRow(array('',
                                 $rend->elementToHtml('materials_add')
                                 . '&nbsp;'
                                 . $rend->elementToHtml('materials_remove')));
        }
        else {
            $table->addRow(array('',
                                 $rend->elementToHtml('materials_add')));
        }

        $table->addRow(array('<hr/>'), array('colspan' => 2));
        $table->addRow(array('',
                             $rend->elementToHtml('Save')
                             . ' ' . $rend->elementToHtml('Clear')));

        $table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));

        // emphasize the 'step' cells
        for ($i = 0 ; $i < $table->getRowCount(); $i++) {
            if ((strpos($table->getCellContents($i, 0), 'Step 1:') !== false)
                || (strpos($table->getCellContents($i, 0), 'Step 2:') !== false))
                $table->updateCellAttributes($i, 0, array('id' => 'emph_large'));
        }

        $this->contentPre .= '<a name="start"></a><h3>';
        if ($edit)
            $this->contentPre .= 'Edit';
        else
            $this->contentPre .= 'Add';
        $this->contentPre .= ' Publication</h3>';

        if (!$edit) {
            $this->contentPre .= 'Adding a publication takes two steps:<br/>'
                . '1. Fill in the appropriate fields<br/>'
                . '2. Upload the paper and any additional '
                . 'materials';
        }

        $this->form = $form;
        $this->renderer = $rend;
        $this->table = $table;

        $this->javascript($ext, $intpoint, $numMaterials);

        $db->close();
    }

    function javascript($ext, $intpoint, $numMaterials) {
        $ext_next = $ext + 1;
        $ext_prev = $ext - 1;
        $intpoint_next = $intpoint + 1;
        $intpoint_prev = $intpoint - 1;
        $numMaterials_next = $numMaterials + 1;
        $numMaterials_prev = $numMaterials - 1;

        $this->js = <<<JS_END
            <script language="JavaScript" src="../calendar.js"></script>
            <script language="JavaScript" type="text/JavaScript">

            window.name="add_publication.php";
        var venueHelp=
            "Where the paper was published -- specific journal, conference, "
            + "workshop, etc. If many of the database papers are in the same "
            + "venue, you can create a single &quot;label&quot; for that "
            + "venue, to specify name of the venue, location, date, editors "
            + "and other common information. You will then be able to use "
            "and re-use that information.";

        var categoryHelp=
            "Category describes the type of document that you are submitting "
            + "to the site. For examplethis could be a journal entry, a book "
            + "chapter, etc.<br/><br/>"
            + "Please use the drop down menu to select an appropriate "
            + "category to classify your paper. If you cannot find an "
            + "appropriate category you can select 'Add New Category' from "
            + "the drop down menu and you will be asked for the new category "
            + "information on a subsequent page.<br/><br/>";

        var titleHelp=
            "Title should contain the title given to your document.<br/><br/>"
            +  "Please enter the title of your document in the field provided.";

        var authorsHelp=
            "This field is to store the author(s) of your document in the database."
            + "<br/><br/>"
            + "To use this field select the author(s) of your document from the "
            + "listbox. You can select multiple authors by holding down the control "
            + "key and clicking. If you do not see the name of the author(s) of the "
            + "document listed in the listbox then you must add them with the Add "
            + "Author button.";

        var abstractHelp=
            "Abstract is an area for you to provide an abstract of the document you "
            + "are submitting.<br/><br/>"
            + "To do this enter a plain text abstract for your paper in the field "
            + "provided. HTML tags can be used.";

        var extraInfoHelp=
            "Specify auxiliary information, to help classify this publication. "
            + "Eg, &quot;with student&quot; or &quot;best paper&quot;, etc. Note "
            + "that, by default, this information will NOT be shown when this "
            + "document is presented. Separate using semicolons(;). You can see "
            + "previously enterred entries by clicking the &quot;Select from a list "
            + "of previously used information options&quot;, and just check off "
            + "those that apply to the current publication.";

        var externalPtrHelp=
            "These can be used to connect this publication to an outside source "
            + "such as a website or a publication that is not in the current "
            + "database."
            + "<ul>"
            + "<li>The &quot;Pointer Type&quot; is the kind of object you are linking "
            + "with. eg website or publication,</li>"
            + "<li>The &quot;Title of link&quot; would be the name of the website, or "
            + "the nameof the publication</li>"
            + "<li>The &quot;http://&quot; would be where you would enter the url."
            + "</li>"
            + "</ul>";

        var internalPtrHelp=
            "These can be used to connect this publication with another publication "
            + "inside the database.";


        var keywordsHelp=
            "Keywords is a field where you can enter keywords that will be used to "
            + "possibly locate your paper by others searching the database. You may "
            + "want to enter multiple terms that are associated with your document. "
            + "Examples may include words like: medical imaging; robotics; data "
            + "mining.<br/><br/>"
            + "Please enter keywords used to describe your paper, each keyword should "
            + "be seperated by a semicolon.";

        var datePublishedHelp=
            "Specifies the date that this document was published. If you have "
            + "specified a publication_venue that include a date, then this date "
            + "field will already be enterred.";

        function dataKeep(tab) {
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
                var element = document.forms["pubForm"].elements[i];
                if ((element.type != "submit") && (element.type != "reset")
                    && (element.type != "button")
                    && (element.type != "checkbox")
                    && (element.value != "") && (element.value != null)) {

                    if (element.name == "authors[]") {
                        var author_count = 0;

                        for (j = 0; j < element.length; j++) {
                            if (element[j].selected) {
                                qsArray.push("authors[" + author_count
                                             + "]=" + element[j].value);
                                author_count++;
                            }
                        }
                    }
                    else if (element.name == "comments")
                        qsArray.push(element.name + "="
                                     + element.value.replace("\"","'"));

                    else if (element.name == "nopaper") {
                        if (element.checked)
                            qsArray.push(element.name + "=" + element.value);
                    }
                    else if (element.name == "ext"){
                        if (tab == "addext")
                            qsArray.push(element.name + "={$ext_next}");
                        else if (tab == "remext")
                            qsArray.push(element.name + "={$ext_prev}");
                        else
                            qsArray.push(element.name + "={$ext}");
                    }
                    else if (element.name == "intpoint"){
                        if (tab == "addint")
                            qsArray.push(element.name + "={$intpoint_next}");
                        else if (tab == "remint")
                            qsArray.push(element.name + "={$intpoint_prev}");
                        else
                            qsArray.push(element.name + "={$intpoint}");
                    }
                    else if (element.name == "numMaterials"){
                        if (tab == "addnum")
                            qsArray.push(element.name
                                         + "={$numMaterials_next}");
                        else if (tab == "remnum")
                            qsArray.push(element.name
                                         + "={$numMaterials_prev}");
                    }
                    else
                        qsArray.push(element.name + "=" + element.value);
                }
            }

            if ((tab == "addnum") || (tab == "remnum"))
                qsArray.push("#step2");
            else if (((tab == "addext") || (tab == "remext"))
                     || ((tab == "addint") || (tab == "remint")))
                qsArray.push("#pointers");
            else if (tab != "none")
                qsArray.push("#" + tab);

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace("\"", "?");
                qsString.replace(" ", "%20");
            }

            location.href
                = "http://" + "{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }

        function verify(num) {
            var pubform = document.forms["pubForm"];
            if (pubform.elements["category"].value == "") {
                alert("Please select a category for the publication.");
                return false;
            }
            else if (pubform.elements["title"].value == "") {
                alert("Please enter a title for the publication.");
                return false;
            }
            else if ((pubform.elements["nopaper"].value == "false")
                     && (pubform.elements["uploadpaper"].value == "")) {
                alert("Please choose a paper to upload or select \"No Paper\".");
                return false;
            }
            else if (pubform.elements["selected_authors"].value == "") {
                alert("Please select the author(s) of this publication.");
                return false;
            }
            else if (pubform.elements["abstract"].value == "") {
                alert("Please enter the abstract for this publication.");
                return false;
            }
            else if (pubform.elements["keywords"].value == "") {
                alert("Please enter the keywords for this publication.");
                return false;
            }

            return true;
        }

        function resetAll() {
            location.href="./add_publication.php";
        }
        function refresher() { window.location.reload(true);}

        </script>
JS_END;
    }
}


//User's 10 most popular Authors
function popularauthors(){
    $userauthorcount = 0;
    $user_query
        = "SELECT pub_author.author_id "
        . "FROM pub_author, publication, user "
		. "WHERE publication.submit = user.name "
        . "AND publication.pub_id = pub_author.pub_id "
        . "AND user.login=\"" .$_SERVER['PHP_AUTH_USER'] . "\"";

    $user_result  = mysql_query($user_query)
        or die("Query failed: " . mysql_error());
    while($user_array = mysql_fetch_array($user_result, MYSQL_ASSOC)){
		$popular_users[$user_array['author_id']]++;
		$listofauthors[$userauthorcount++] = $user_array['author_id'];
    }
    if ($userauthorcount < 10) $length = $userauthorcount; else $length = 10;
    for($count = 0; $count < $length; $count++){
        $largest = "";
        $largestvalue = 0;
        for($index = 0; $index< $userauthorcount; $index++)
            if ($popular_users[$listofauthors[$index]] > $largestvalue){
                $largestvalue = $popular_users[$listofauthors[$index]];
                $largest = $listofauthors[$index];
            }
        $finallist[$count] = $largest;
        $popular_users[$largest] = 0;
    }
    return $finallist;

}

// Global variable to keep track of what we're doing - can change this
// to not be a boolean if we want to deal with more than 2 different
// operations (save, new) on this page
$edit = FALSE;

//////////////////////EDIT START/////////////////////////////////
// Check to see if we've been passed a publication ID
if ((isset($_GET['pub_id']) && $_GET['pub_id'] != "")
    && ($_GET['new'] != "false")) {

	// Set "edit mode" to true - we could just check for the existence
	// of pub_id in the GET variables, but this is more clear.
	$edit = TRUE;
	// Get publication info
    $pub = new pdPublication();
    $pub->dbLoad($db, $_GET['pub_id']);

	// Check if the publication actually exists
	if (!isset($pub->pub_id)) {
        "Error: Publication with ID " . $_GET['pub_id'] . " doesn't exist.";
        echo "</div>\n";
        pageFooter();
        echo "</body></html>";
        $db->close();
		exit;
	}
    if (($intpoint == "")&&($ext == "")){
        $point_query = "SELECT type, name, value FROM pointer WHERE pub_id="
            . $_GET['pub_id'];
        $point_result = query_db($point_query);
        $intpoint = 0;
        $ext = 0;
        while($point_line = mysql_fetch_array($point_result, MYSQL_ASSOC)){
            if ($point_line[type] == "int"){
                $internal = "intpointer".($intpoint++);
                $$internal = $point_line[value];
            }
            else if ($point_line[type] == "ext"){
                $externalname = "extname".$ext;
                $$externalname = $point_line[name];

                $temparray1 = split("<a href=\"",$point_line[value]);
                $temparray2 = split("\" target=\"_blank\">",$temparray1[1]);
                $temparray3 = split("</a>",$temparray2[1]);

                $externalvalue = "extvalue".$ext;
                $$externalvalue = $temparray3[0];
                $externallink = "extlink".($ext++);
                $$externallink = $temparray2[0];
            }
        }
    }

	// Set the variables to be set in the page as initial values.  We have to
	// check and see if there's a value that's already been posted back to us,
	// and use that instead, in case it changes between page updates.

	$catvals = get_category($_GET['pub_id']);
	$category_id = $catvals['cat_id'];

	if ($_GET['category'] == "") {
		$category = $catvals['category'];
	}

	if ($_GET['title'] == "") {
		$title = $pubInfo['title'];
	}

	if ($_GET['abstract'] == "") {
		$abstract = $pubInfo['abstract'];
	}

	if ($_GET['venue'] == "") {
		$venue = $pubInfo['venue'];
	}

	if ($_GET['extra_info'] == "") {
		$extra_info = $pubInfo['extra_info'];
	}

	if ($_GET['keywords'] == "") {
		$keywords = $pubInfo['keywords'];
	}

	// Deal with the publication date.
	// variables we care about are $month, $day, $year.
	$published = $pubInfo['published'];

	$myYear = strtok($published,"-");
	$myMonth = strtok("-");
	$myDay = strtok("-");

	if ($_GET['month'] == "") {
		$month = $myMonth;
	}

	if ($_GET['day'] == "") {
		$day = $myDay;
	}

	if ($_GET['year'] == "") {
		$year = $myYear;
	}


	// Check the number of materials
	// Don't allow the user to set the number of materials less
	// than what currently exist in the DB.
	$dbMaterials = get_num_db_materials ($pub_id);

	if ($_GET['numMaterials'] != "") {
		if ($_GET['numMaterials'] < $dbMaterials) {
			$numMaterials = $dbMaterials;
		}
	}
	else {
		$numMaterials = $dbMaterials;
	}


	// andy_note: Paper is a special case! For now we'll use strtok to
	// get only the name of the file and discard the rest.
	$paper = $pubInfo['paper'];
	$paperTmp = strtok($paper,"/");

	// Since strtok will return a "false" as the last element, the
	// item we're actually interested in is the item that appears
	// *second to last*.  So we set $paper = $paperTmp and then get
	// the right thing.
	while ($paperTmp) {
		$paper = $paperTmp;
		$paperTmp = strtok("/");
	}

	$authors_from_db = get_authors($_GET['pub_id']);
}
/////////////////////EDIT END///////////////////////////////////////

$page = new add_publication($_GET['edit'], $_GET['venue_id'], $ext,
                            $intpoint, $numMaterials);
echo $page->toHtml();


?>
