<?php ;

// $Id: authorselect.php,v 1.17 2006/09/25 19:59:09 aicmltec Exp $

/**
 * Custom HTML_QuickForm element that allows a user to select authors from
 * the database.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'HTML/QuickForm/advmultiselect.php';
require_once 'HTML/QuickForm/radio.php';


function sortselect($a, $b) {
    if (strtolower($a['text']) == strtolower($b['text'])) return 0;

    return (strtolower($a['text']) < strtolower($b['text'])) ? -1 : 1;
}

/**
 * Custom HTML_QuickForm element that allows a user to select authors from
 * the database.
 *
 * @package PapersDB
 */
class authorselect extends HTML_QuickForm_advmultiselect {
    var $form_name;
    var $author_list;
    var $favorite_authors;
    var $most_used_authors;

    function authorselect($elementName = null,
                          $elementLabel = null,
                          $options = null,
                          $attributes = null) {

        $all_authors = array();
        foreach(array('form_name', 'author_list', 'favorite_authors',
                      'most_used_authors')
                as $opt) {
            if (isset($options[$opt])) {
                $this->$opt = $options[$opt];
                if (is_array($options[$opt]))
                    foreach ($options[$opt] as $key => $value)
                        $all_authors[$opt . ':' . $key]= $value;
            }
        }

        parent::HTML_QuickForm_advmultiselect($elementName, $elementLabel,
                                              $all_authors, $attributes);

        $this->setLabel(array($this->_label, 'Selected', 'Available'));
        $this->setButtonAttributes('add', array('value' => 'Add',
                                                'class' => 'inputCommand'));
        $this->setButtonAttributes('remove', array('value' => 'Remove',
                                                   'class' => 'inputCommand'));
        $this->setButtonAttributes('moveup', array('class' => 'inputCommand'));
        $this->setButtonAttributes('movedown',
                                   array('class' => 'inputCommand'));

       $this->_elementTemplate = <<<END
{javascript}
<table{class}>
<tr>
  <th>&nbsp;</th>
  <!-- BEGIN label_2 --><th>{label_2}</th><!-- END label_2 -->
  <th>&nbsp;</th>
  <!-- BEGIN label_3 --><th>{label_3}</th><!-- END label_3 -->
</tr>
<tr>
  <td valign="middle">{moveup}<br/>{movedown}<br/>{remove}</td>
  <td valign="top">{selected}</td>
  <td valign="middle">{add}</td>
  <td valign="top">{unselected}</td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>
    <input name="which_list" value="author_list" type="radio" id="author_list"
           onclick="buildSelect('author_list')" checked>
      <label for="author_list">All Authors</label><br/>
    <input name="which_list" value="favorite_authors" type="radio"
           onclick="buildSelect('favorite_authors')">
      <label for="favorite_authors">Favourite Authors</label><br/>
    <input name="which_list" value="most_used_authors" type="radio"
           onclick="buildSelect('most_used_authors')">
      <label for="most_used_authors">Most Used Authors</label>
  </td>
</tr>
</table>
END;
    }

    function toHtml() {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $tabs    = $this->_getTabs();
        $tab     = $this->_getTab();
        $strHtml = '';

        $selectName = $this->getName() . '[]';
        $jsfuncName = $this->_jsPrefix . $this->_jsPostfix;

        // set name of Select From Box
        $this->_attributesUnselected = array(
            'name' => '__'.$selectName,
            'ondblclick' => $jsfuncName . '(\'add\')');
        $this->_attributesUnselected
            = array_merge($this->_attributes,
                          $this->_attributesUnselected);
        $attrUnselected
            = $this->_getAttrString($this->_attributesUnselected);

        // set name of Select To Box
        $this->_attributesSelected = array(
            'name' => '_'.$selectName,
            'ondblclick' => $jsfuncName . '(\'remove\')');
        $this->_attributesSelected
            = array_merge($this->_attributes, $this->_attributesSelected);
        $attrSelected = $this->_getAttrString($this->_attributesSelected);

        // set name of Select hidden Box
        $this->_attributesHidden = array(
            'name' => $selectName,
            'style' => 'overflow: hidden; visibility: hidden; width: 1px; height: 0;');
        $this->_attributesHidden
            = array_merge($this->_attributes, $this->_attributesHidden);
        $attrHidden = $this->_getAttrString($this->_attributesHidden);

        // prepare option tables to be displayed as in POST order
        $append = count($this->_values);
        if ($append > 0) {
            $arrHtmlSelected = array_fill(0, $append, ' ');
        } else {
            $arrHtmlSelected = array();
        }

        $options = count($this->_options);
        $arrHtmlUnselected = array();
        if ($options > 0) {
            $arrHtmlHidden = array_fill(0, $options, ' ');

            foreach ($this->_options as $option) {
                $value = (string) $option['attr']['value'];
                $auth_id = substr($value, strpos($value, ':') + 1);

                $key = false;

                if (is_array($this->_values)) {
                    // Get the post order
                    if (in_array($auth_id, $this->_values)) {
                        // $this->_values set by user
                        $key = array_search($auth_id, $this->_values);
                    }
                    else if (in_array($value, $this->_values)) {
                        // $this->_values set internally
                        $key = array_search($value, $this->_values);
                    }
                }

                if ($key !== false) {
                    // The item is *selected* so we want to put it in the
                    // 'selected' multi-select
                    $arrHtmlSelected[$key] = $option;
                    // Add it to the 'hidden' multi-select and set it as
                    // 'selected'
                    $option['attr']['selected'] = 'selected';
                    $arrHtmlHidden[$key] = $option;
                }
                else {
                    // The item is *unselected* so we want to put it in
                    // the 'unselected' multi-select
                    $arrHtmlUnselected[] = $option;

                    // Add it to the hidden multi-select as 'unselected'
                    $arrHtmlHidden[$append] = $option;
                    $append++;
                }
            }
        }
        else {
            $arrHtmlHidden = array();
        }

        // The 'unselected' multi-select which appears on the left
        uasort($arrHtmlUnselected, sortselect);

        $strHtmlUnselected = "<select$attrUnselected>\n";
        if (count($arrHtmlUnselected) > 0) {
            foreach ($arrHtmlUnselected as $data) {
                $strHtmlUnselected .= $tabs . $tab
                    . '<option' . $this->_getAttrString($data['attr']) . '>'
                    . $data['text'] . '</option>' . "\n";
            }
        }
        $strHtmlUnselected .= '</select>';

        // The 'selected' multi-select which appears on the right
        $strHtmlSelected = "<select$attrSelected>\n";
        if (count($arrHtmlSelected) > 0) {
            foreach ($arrHtmlSelected as $data) {
                $strHtmlSelected .= $tabs . $tab
                    . '<option' . $this->_getAttrString($data['attr']) . '>'
                    . $data['text'] . '</option>' . "\n";
            }
        }
        $strHtmlSelected .= '</select>';

        // The 'hidden' multi-select
        $strHtmlHidden = "<select$attrHidden>\n";
        if (count($arrHtmlHidden) > 0) {
            foreach ($arrHtmlHidden as $data) {
                $strHtmlHidden .= $tabs . $tab
                    . '<option' . $this->_getAttrString($data['attr']) . '>'
                    . $data['text'] . '</option>' . "\n";
            }
        }
        $strHtmlHidden .= '</select>';

        // build the remove button with all its attributes
        $attributes = array(
            'onclick' => $jsfuncName . '(\'remove\'); '
            . 'return false;');
        $this->_removeButtonAttributes
            = array_merge($this->_removeButtonAttributes, $attributes);
        $attrStrRemove = $this->_getAttrString($this->_removeButtonAttributes);
        $strHtmlRemove = "<input$attrStrRemove />\n";

        // build the add button with all its attributes
        $attributes = array(
            'onclick' => $jsfuncName . '(\'add\'); '
            . 'return false;');
        $this->_addButtonAttributes
            = array_merge($this->_addButtonAttributes, $attributes);
        $attrStrAdd = $this->_getAttrString($this->_addButtonAttributes);
        $strHtmlAdd = "<input$attrStrAdd />\n";

        // build the select all button with all its attributes
        $attributes = array(
            'onclick' => $jsfuncName . '(\'all\'); '
            . 'return false;');
        $this->_allButtonAttributes
            = array_merge($this->_allButtonAttributes, $attributes);
        $attrStrAll = $this->_getAttrString($this->_allButtonAttributes);
        $strHtmlAll = "<input$attrStrAll />\n";

        // build the select none button with all its attributes
        $attributes = array(
            'onclick' => $jsfuncName . '(\'none\'); '
            . 'return false;');
        $this->_noneButtonAttributes
            = array_merge($this->_noneButtonAttributes, $attributes);
        $attrStrNone = $this->_getAttrString($this->_noneButtonAttributes);
        $strHtmlNone = "<input$attrStrNone />\n";

        // build the toggle button with all its attributes
        $attributes = array(
            'onclick' => $jsfuncName . '(\'toggle\'); '
            . 'return false;');
        $this->_toggleButtonAttributes
            = array_merge($this->_toggleButtonAttributes, $attributes);
        $attrStrToggle = $this->_getAttrString($this->_toggleButtonAttributes);
        $strHtmlToggle = "<input$attrStrToggle />\n";

        // build the move up button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . 'moveUp(); return false;');
        $this->_upButtonAttributes
            = array_merge($this->_upButtonAttributes, $attributes);
        $attrStrUp = $this->_getAttrString($this->_upButtonAttributes);
        $strHtmlMoveUp = "<input$attrStrUp />\n";

        // build the move down button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . 'moveDown(); return false;');
        $this->_downButtonAttributes
            = array_merge($this->_downButtonAttributes, $attributes);
        $attrStrDown = $this->_getAttrString($this->_downButtonAttributes);
        $strHtmlMoveDown = "<input$attrStrDown />\n";

        // render all part of the multi select component with the template
        $strHtml = $this->_elementTemplate;

        // Prepare multiple labels
        $labels = $this->getLabel();
        if (is_array($labels)) {
            array_shift($labels);
        }
        // render extra labels, if any
        if (is_array($labels)) {
            foreach($labels as $key => $text) {
                $key  = is_int($key)? $key + 2: $key;
                $strHtml = str_replace("{label_{$key}}", $text, $strHtml);
                $strHtml = str_replace("<!-- BEGIN label_{$key} -->", '', $strHtml);
                $strHtml = str_replace("<!-- END label_{$key} -->", '', $strHtml);
            }
        }
        // clean up useless label tags
        if (strpos($strHtml, '{label_')) {
            $strHtml = preg_replace('/\s*<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->\s*/i', '', $strHtml);
        }

        $placeHolders = array(
            '{stylesheet}', '{javascript}', '{class}',
            '{unselected}', '{selected}',
            '{add}', '{remove}',
            '{all}', '{none}', '{toggle}',
            '{moveup}', '{movedown}'
        );
        $htmlElements = array(
            $this->getElementCss(false),
            $this->getElementJs(false),
            $this->_tableAttributes, $strHtmlUnselected,
            $strHtmlSelected . $strHtmlHidden,
            $strHtmlAdd, $strHtmlRemove,
            $strHtmlAll, $strHtmlNone, $strHtmlToggle,
            $strHtmlMoveUp, $strHtmlMoveDown
        );

        $strHtml = str_replace($placeHolders, $htmlElements, $strHtml);

        return $strHtml;
    }

    function getElementJs($raw = true) {
        $js = '';
        $jsfuncName = $this->_jsPrefix . $this->_jsPostfix;
        if (defined('AUTHORSELECT_'.$jsfuncName.'_EXISTS'))
            return;

        // We only want to include the javascript code once per form
        define('AUTHORSELECT_'.$jsfuncName.'_EXISTS', true);

        $selectName = $this->getName() . '[]';

        $js .= <<<JS_END
            /* begin javascript for authorselect */
            function buildSelect(list) {
            var availAuthors = document.forms["{$this->form_name}"].elements["__{$selectName}"];
            var selectedAuthors = document.forms["{$this->form_name}"].elements["_{$selectName}"];
            var allAuthors = document.forms["{$this->form_name}"].elements["{$selectName}"];
            var re = new RegExp(list, "g");

            var isSelected;

            availAuthors.options.length = 0;
            for (i=0; i < allAuthors.length; i++) {
                if ((list == "author_list") || allAuthors.options[i].value.match(re)) {
                    // do not add those already selected
                    isSelected = false;
                    for (j=0; j < selectedAuthors.length; j++) {
                        if (allAuthors.options[i].text == selectedAuthors.options[j].text) {
                            isSelected = true;
                        }
                    }
                    if (!isSelected) {
                        availAuthors.options[availAuthors.length] = new Option(allAuthors.options[i].text, allAuthors.options[i].value);
                    }
                }
            }
            {$this->_jsPrefix}sortList(availAuthors, {$this->_jsPrefix}compareText);
        }

        function {$jsfuncName}(action) {
            var availAuthors = document.forms["{$this->form_name}"].elements["__{$selectName}"];
            var selectedAuthors = document.forms["{$this->form_name}"].elements["_{$selectName}"];
            var allAuthors = document.forms["{$this->form_name}"].elements["{$selectName}"];
            var menuFrom;
            var menuTo;

            if (action == 'add' || action == 'all' || action == 'toggle') {
                menuFrom = availAuthors;
                menuTo = selectedAuthors;
            } else {
                menuFrom = selectedAuthors;
                menuTo = availAuthors;
            }

            // Don't do anything if nothing selected. Otherwise we throw
            // javascript errors.
            if ((menuFrom.selectedIndex == -1) && ((action == 'add') || (action == 'remove'))) {
                return;
            }

            maxTo = menuTo.length;

            // Add items to the 'TO' list.
            var selectedListRadio = document.forms["{$this->form_name}"].elements["which_list"];

            for (i=0; i < selectedListRadio.length; i++) {
                if (selectedListRadio[i].checked) {
                    selectedListRadiovalue = selectedListRadio[i].value;
                }
            }

            for (i=0; i < menuFrom.length; i++) {
                if ((action == 'all') || (action == 'none') || (action == 'toggle') || menuFrom.options[i].selected) {
                    var option = menuFrom.options[i];

                    if (menuTo == availAuthors) {
                        // only add if author belongs to the author list
                        if (option.value.indexOf(selectedListRadiovalue) >= 0) {
                            menuTo.options[menuTo.length] = new Option(option.text, option.value);
                        }
                    } else {
                        menuTo.options[menuTo.length] = new Option(option.text, option.value);
                    }
                }
            }

            // Remove items from the 'FROM' list.
            for (i = menuFrom.length - 1; i >= 0; i--) {
                if ((action == 'all') || (action == 'none') || (action == 'toggle') || menuFrom.options[i].selected) {
                    menuFrom.options[i] = null;
                }
            }

            // Add items to the 'FROM' list for toggle function
            if (action == 'toggle') {
                for (i=0; i < maxTo; i++) {
                    menuFrom.options[menuFrom.length] = new Option(menuTo.options[i].text, menuTo.options[i].value);
                }
                for (i=(maxTo - 1); i>=0; i--) {
                    menuTo.options[i] = null;
                }
            }

            // Sort list if required
            if (menuTo == document.forms["{$this->form_name}"].elements["__{$selectName}"]) {
                {$this->_jsPrefix}sortList(menuTo, {$this->_jsPrefix}compareText);
            }

            // Set the appropriate items as 'selected in the hidden select.
            // These are the values that will actually be posted with the form.
            {$this->_jsPrefix}updateHidden(document.forms["{$this->form_name}"].elements["_{$selectName}"]);
        }

        function {$this->_jsPrefix}sortList(list, compareFunction) {
            var options = new Array (list.options.length);
            for (var i = 0; i < options.length; i++) {
                options[i] = new Option (list.options[i].text,
                                         list.options[i].value,
                                         list.options[i].defaultSelected,
                                         list.options[i].selected);
            }
            options.sort(compareFunction);
            list.options.length = 0;
            for (i = 0; i < options.length; i++) {
                list.options[i] = options[i];
            }
        }

        function {$this->_jsPrefix}compareText(option1, option2) {
            if (option1.text == option2.text) return 0;

            return (option1.text.toLowerCase() < option2.text.toLowerCase() ? -1 : 1);
        }

        function {$this->_jsPrefix}updateHidden(s) {
            var allAuthors = document.forms["{$this->form_name}"].elements["{$selectName}"];

            for (i=0; i < allAuthors.length; i++) {
                allAuthors.options[i].selected = false;
            }

            for (i=0; i < s.length; i++) {
                for (j=0; j < allAuthors.length; j++) {
                    if (s[i].value == allAuthors.options[j].value) {
                        allAuthors.options[j] = null;
                    }
                }
            }

            for (i=0; i < s.length; i++) {
                allAuthors.options[allAuthors.length] = new Option(s[i].text, s.options[i].value);
                allAuthors.options[allAuthors.length - 1].selected = true;
            }
        }

        function {$this->_jsPrefix}moveUp() {
            var selectedAuthors = document.forms["{$this->form_name}"].elements["_{$selectName}"];
            var index = selectedAuthors.selectedIndex;

            if (index < 0) return;

            if (index > 0) {
                {$this->_jsPrefix}moveSwap(index, index-1);
                {$this->_jsPrefix}updateHidden(selectedAuthors);
            }
        }

        function {$this->_jsPrefix}moveDown() {
            var selectedAuthors = document.forms["{$this->form_name}"].elements["_{$selectName}"];
            var index = selectedAuthors.selectedIndex;

            if (index < 0) return;

            if (index < selectedAuthors.options.length-1) {
                {$this->_jsPrefix}moveSwap(index, index+1);
                {$this->_jsPrefix}updateHidden(selectedAuthors);
            }
        }

        function {$this->_jsPrefix}moveSwap(i,j) {
            var selectedAuthors = document.forms["{$this->form_name}"].elements["_{$selectName}"];
            var value = selectedAuthors.options[i].value;
            var text = selectedAuthors.options[i].text;
            selectedAuthors.options[i].value = selectedAuthors.options[j].value;
            selectedAuthors.options[i].text = selectedAuthors.options[j].text;
            selectedAuthors.options[j].value = value;
            selectedAuthors.options[j].text = text;
            selectedAuthors.selectedIndex = j;
        }

        /* end javascript for authorselect */
JS_END;

        if ($raw !== true) {
            $js = '<script type="text/javascript">'
                . '//<![CDATA[' . $js . '//]]>'
                . '</script>';
        }

        return $js;
    }
}

if (class_exists('HTML_QuickForm')) {
    HTML_QuickForm::registerElementType('authorselect',
                                        'includes/authorselect.php',
                                        'authorselect');
}

?>
