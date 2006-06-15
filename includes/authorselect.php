<?php ;

// $Id: authorselect.php,v 1.1 2006/06/15 00:00:47 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

require_once 'HTML/QuickForm/advmultiselect.php';
require_once 'HTML/QuickForm/radio.php';


class authorselect extends HTML_QuickForm_advmultiselect {
    var $author_list;
    var $favorite_authors;
    var $most_used_authors;

    function authorselect($elementName = null,
                          $elementLabel = null,
                          $options = null,
                          $attributes = null) {

        $all_authors = array();
        foreach(array('author_list', 'favorite_authors', 'most_used_authors')
                as $list) {
            if (isset($options[$list])) {
                $this->$list = $options[$list];
                $all_authors += $options[$list];
            }
        }

        if ($this->author_list != null)

        if ($this->favorite_authors != null)
            $all_authors += $this->favorite_authors;

        if ($this->most_used_authors != null)
            $all_authors += $this->most_used_authors;

        parent::HTML_QuickForm_advmultiselect($elementName, $elementLabel,
                                              $all_authors, $attributes);

        $this->setLabel(array('Authors:', 'Selected', 'Available'));
        $this->setButtonAttributes('add', array('value' => '<<',
                                                'class' => 'inputCommand'));
        $this->setButtonAttributes('remove', array('value' => '>>',
                                                   'class' => 'inputCommand'));
        $this->setButtonAttributes('moveup', array('class' => 'inputCommand'));
        $this->setButtonAttributes('movedown',
                                   array('class' => 'inputCommand'));

       $this->_elementTemplate = <<<JS_END
{javascript}
<table{class}>
<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->
<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->
<tr>
  <td valign="top">{selected}</td>
  <td align="center">{add}<br/>{remove}<br/>{moveup}<br/>{movedown}</td>
  <td valign="top">{unselected}</td>
</tr>
</table>
JS_END;
    }

    function toHtml() {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }
        $selectName = $this->getName() . '[]';

        // set name of Select From Box
        $this->_attributesUnselected = array(
            'name' => '__'.$selectName,
            'ondblclick' => $this->_jsPrefix . $this->_jsPostfix
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"], "add")');
        $this->_attributesUnselected
            = array_merge($this->_attributes,
                          $this->_attributesUnselected);
        $attrUnselected
            = $this->_getAttrString($this->_attributesUnselected);

        // set name of Select To Box
        $this->_attributesSelected = array(
            'name' => '_'.$selectName,
            'ondblclick' => $this->_jsPrefix . $this->_jsPostfix
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"], "remove")');
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
                if (is_array($this->_values) &&
                    in_array((string)$option['attr']['value'],
                             $this->_values)) {
                    // Get the post order
                    $key = array_search($option['attr']['value'],
                                        $this->_values);

                    // The items is *selected* so we want to put it in the
                    // 'selected' multi-select
                    $arrHtmlSelected[$key] = $option;
                    // Add it to the 'hidden' multi-select and set it as
                    // 'selected'
                    $option['attr']['selected'] = 'selected';
                    $arrHtmlHidden[$key] = $option;
                } else {
                    // The item is *unselected* so we want to put it in the
                    // 'unselected' multi-select
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
        $strHtmlUnselected = "<select$attrUnselected>";
        if (count($arrHtmlUnselected) > 0) {
            foreach ($arrHtmlUnselected as $data) {
                $strHtmlUnselected .= $tabs . $tab
                    . '<option' . $this->_getAttrString($data['attr']) . '>'
                    . $data['text'] . '</option>' ;
            }
        }
        $strHtmlUnselected .= '</select>';

        // The 'selected' multi-select which appears on the right
        $strHtmlSelected = "<select$attrSelected>";
        if (count($arrHtmlSelected) > 0) {
            foreach ($arrHtmlSelected as $data) {
                $strHtmlSelected .= $tabs . $tab
                    . '<option' . $this->_getAttrString($data['attr']) . '>'
                    . $data['text'] . '</option>' ;
            }
        }
        $strHtmlSelected .= '</select>';

        // The 'hidden' multi-select
        $strHtmlHidden = "<select$attrHidden>";
        if (count($arrHtmlHidden) > 0) {
            foreach ($arrHtmlHidden as $data) {
                $strHtmlHidden .= $tabs . $tab
                    . '<option' . $this->_getAttrString($data['attr']) . '>'
                    . $data['text'] . '</option>' ;
            }
        }
        $strHtmlHidden .= '</select>';

        // build the remove button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . $this->_jsPostfix
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"], "remove"); '
            . 'return false;');
        $this->_removeButtonAttributes
            = array_merge($this->_removeButtonAttributes, $attributes);
        $attrStrRemove = $this->_getAttrString($this->_removeButtonAttributes);
        $strHtmlRemove = "<input$attrStrRemove />";

        // build the add button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . $this->_jsPostfix
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"], "add"); '
            . 'return false;');
        $this->_addButtonAttributes
            = array_merge($this->_addButtonAttributes, $attributes);
        $attrStrAdd = $this->_getAttrString($this->_addButtonAttributes);
        $strHtmlAdd = "<input$attrStrAdd />";

        // build the select all button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . $this->_jsPostfix
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"], "all"); '
            . 'return false;');
        $this->_allButtonAttributes
            = array_merge($this->_allButtonAttributes, $attributes);
        $attrStrAll = $this->_getAttrString($this->_allButtonAttributes);
        $strHtmlAll = "<input$attrStrAll />";

        // build the select none button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . $this->_jsPostfix
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"], "none"); '
            . 'return false;');
        $this->_noneButtonAttributes
            = array_merge($this->_noneButtonAttributes, $attributes);
        $attrStrNone = $this->_getAttrString($this->_noneButtonAttributes);
        $strHtmlNone = "<input$attrStrNone />";

        // build the toggle button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . $this->_jsPostfix
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"], "toggle"); '
            . 'return false;');
        $this->_toggleButtonAttributes
            = array_merge($this->_toggleButtonAttributes, $attributes);
        $attrStrToggle = $this->_getAttrString($this->_toggleButtonAttributes);
        $strHtmlToggle = "<input$attrStrToggle />";

        // build the move up button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . 'moveUp'
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"]); return false;');
        $this->_upButtonAttributes
            = array_merge($this->_upButtonAttributes, $attributes);
        $attrStrUp = $this->_getAttrString($this->_upButtonAttributes);
        $strHtmlMoveUp = "<input$attrStrUp />";

        // build the move down button with all its attributes
        $attributes = array(
            'onclick' => $this->_jsPrefix . 'moveDown'
            . '(this.form.elements["__' . $selectName . '"], '
            . 'this.form.elements["_' . $selectName . '"], '
            . 'this.form.elements["' . $selectName . '"]); return false;');
        $this->_downButtonAttributes
            = array_merge($this->_downButtonAttributes, $attributes);
        $attrStrDown = $this->_getAttrString($this->_downButtonAttributes);
        $strHtmlMoveDown = "<input$attrStrDown />";

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
        if (defined('HTML_QUICKFORM_ADVMULTISELECT_'.$jsfuncName.'_EXISTS'))
            return;

        // We only want to include the javascript code once per form
        define('HTML_QUICKFORM_ADVMULTISELECT_'.$jsfuncName.'_EXISTS', true);

        $js .= <<<JS_END
            /* begin javascript for authorselect */
            function {$jsfuncName}(selectLeft, selectRight, selectHidden, action) {
            if (action == 'add' || action == 'all' || action == 'toggle') {
                menuFrom = selectLeft;
                menuTo = selectRight;
            } else {
                menuFrom = selectRight;
                menuTo = selectLeft;
            }

            // Don't do anything if nothing selected. Otherwise we throw
            // javascript errors.
            if ((menuFrom.selectedIndex == -1)
                && ((action == 'add') || (action == 'remove'))) {
                return;
            }

            maxTo = menuTo.length;

            // Add items to the 'TO' list.
            for (i=0; i < menuFrom.length; i++) {
                if ((action == 'all') || (action == 'none')
                    || (action == 'toggle') || menuFrom.options[i].selected) {
                    var optionName = menuFrom.options[i].text;
                    if (optionName.match(/\d+\./g)) {
                        optionName.replace(/\d+\./g, "");
                    }
                    else {
                        var optionNum = menuTo.length + 1;
                        optionName =  optionNum + ". " + optionName;
                    }
                    menuTo.options[menuTo.length]
                        = new Option(optionName, menuFrom.options[i].value);
                }
            }

            // Remove items from the 'FROM' list.
            for (i=(menuFrom.length - 1); i>=0; i--){
                if ((action == 'all') || (action == 'none')
                    || (action == 'toggle') || menuFrom.options[i].selected) {
                    menuFrom.options[i] = null;
                }
            }

            // Add items to the 'FROM' list for toggle function
            if (action == 'toggle') {
                for (i=0; i < maxTo; i++) {
                    menuFrom.options[menuFrom.length]
                        = new Option(menuTo.options[i].text, menuTo.options[i].value);
                }
                for (i=(maxTo - 1); i>=0; i--) {
                    menuTo.options[i] = null;
                }
            }

            // Sort list if required
            {$this->_jsPrefix}sortList(menuTo, {$this->_jsPrefix}compareText);

            // Set the appropriate items as 'selected in the hidden select.
            // These are the values that will actually be posted with the form.
            {$this->_jsPrefix}updateHidden(selectHidden, selectRight);
        }

        function {$this->_jsPrefix}sortList(list, compareFunction) {
            var options = new Array (list.options.length);
            for (var i = 0; i < options.length; i++) {
                options[i] = new Option (
                    list.options[i].text,
                    list.options[i].value,
                    list.options[i].defaultSelected,
                    list.options[i].selected
                    );
            }
            options.sort(compareFunction);
            {$reverse}
            list.options.length = 0;
            for (var i = 0; i < options.length; i++) {
                list.options[i] = options[i];
            }
        }

        function {$this->_jsPrefix}compareText(option1, option2) {
            if (option1.text == option2.text) {
                return 0;
            }
            return option1.text < option2.text ? -1 : 1;
        }

        function {$this->_jsPrefix}updateHidden(h,r) {
            for (i=0; i < h.length; i++) {
                h.options[i].selected = false;
            }

            for (i=0; i < r.length; i++) {
                h.options[h.length] = new Option(r.options[i].text, r.options[i].value);
                h.options[h.length-1].selected = true;
            }
        }

        function {$this->_jsPrefix}moveUp(l,h) {
            var indice = l.selectedIndex;
            if (indice < 0) {
                return;
            }
            if (indice > 0) {
                {$this->_jsPrefix}moveSwap(l, indice, indice-1);
                {$this->_jsPrefix}updateHidden(h, l);
            }
        }

        function {$this->_jsPrefix}moveDown(l,h) {
            var indice = l.selectedIndex;
            if (indice < 0) {
                return;
            }
            if (indice < l.options.length-1) {
                {$this->_jsPrefix}moveSwap(l, indice, indice+1);
                {$this->_jsPrefix}updateHidden(h, l);
            }
        }

        function {$this->_jsPrefix}moveSwap(l,i,j) {
            var valeur = l.options[i].value;
            var texte = l.options[i].text;
            l.options[i].value = l.options[j].value;
            l.options[i].text = l.options[j].text;
            l.options[j].value = valeur;
            l.options[j].text = texte;
            l.selectedIndex = j
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
