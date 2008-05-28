<?php

require_once 'pdUser.php';
require_once 'HTML/Table.php';

function relativeUrlGet() {
    $pos = strpos($_SERVER['PHP_SELF'], SITE_NAME);
    if ($pos !== false) {
    	return substr($_SERVER['PHP_SELF'], $pos + strlen(SITE_NAME) + 1);
    }
    if (strpos($_SERVER['PHP_SELF'], 'http://papersdb:8080/') === 0) {
        return substr($_SERVER['PHP_SELF'], 21);    	
    }
    return '';
}

/**
 * Creates an HTML formatted citation list for the publications contained
 * in $pub_list.
 *
 * @param object $db
 * @param array $pub_list
 * @param boolean $enumerate
 * @param integer $max
 * @param array $additional
 * @param array $options
 * @param string $url_prefix
 * @return unknown
 */
function displayPubList(&$db, &$pub_list, $enumerate = true, 
    $max = -1, $additional = null, $options = null, $url_prefix = '') {
    assert('is_object($db)');
    assert('is_array($pub_list)');

    if (isset($pub_list['type']) && ($pub_list['type'] == 'category')) {
        return displayPubListByCategory($db, $pub_list, $enumerate, $max,
            $options, $url_prefix);
    }

    if (count($pub_list) == 0) {
        return 'No Publications';
    }

    $col_desciptions = pdPublication::collaborationsGet($db);

    $result = '';
    $count = 0;
    foreach ($pub_list as $pub_id => $pub) {
        ++$count;
        $pub->dbload($db, $pub->pub_id);

        $cells = array();
        $table = new HTML_Table(array('class' => 'publist',
                                          'cellpadding' => '0',
                                          'cellspacing' => '0'));
        $table->setAutoGrow(true);
    
    
        $citation = $pub->getCitationHtml($url_prefix) . '&nbsp;' 
            . getPubIcons($db, $pub, 0xf, $url_prefix);

        if ((is_array($options) && !empty($options['show_internal_info'])
            && $options['show_internal_info'])
            || (isset($_SESSION['user'])
            && ($_SESSION['user']->showInternalInfo()))) {
            $citation .= '<br/><span style="font-size:80%">';
            if (isset($pub->ranking))
            $citation .= 'Ranking: ' . $pub->ranking;

            if (is_array($pub->collaborations)
            && (count($pub->collaborations) > 0)) {

                $values = array();
                foreach ($pub->collaborations as $col_id) {
                    $values[] = $col_desciptions[$col_id];
                }

                $citation .= '<br/>Collaboration:' . implode(', ', $values);
            }
            $citation .= '</span>';
        }

        if (isset($additional[$pub_id]))
        $citation .= '<br/><span style="font-size:90%;color:#006633;font-weight:bold;">'
        . $additional[$pub_id] . '</span>';

        if ($enumerate) {
        	$cells[] = $count . '.';
        }

        $cells[] = $citation;

        $table->addRow($cells);

        if ($enumerate) {
        	$table->updateColAttributes(0, array('class' => 'item'), NULL);
        }

        $result .= $table->toHtml();
        unset($table);

        if (($max > 0) && ($count >= $max)) break;
    }

    return $result;
}

function displayPubListByCategory(&$db, &$pub_list, $enumerate = true,
$max = -1, $options = null, $url_prefix = '') {
    assert('is_object($db)');
    assert('is_array($pub_list)');
    $result = '';
    $count = 0;

    $col_desciptions = pdPublication::collaborationsGet($db);

    foreach (pdPubList::catDisplayOrder() as $category) {
        $pubs =& $pub_list[$category];

        if (empty($pubs)) continue;

        if ($category == 'Other')
        $result .= "<h3>Other Categories</h3>\n";
        else
        $result .= '<h3>' . $category . "</h3>\n";

        foreach ($pubs as $pub) {
            ++$count;
            $pub->dbLoad($db, $pub->pub_id);

            $cells = array();
            $table = new HTML_Table(array('class' => 'publist',
                                              'cellpadding' => '0',
                                              'cellspacing' => '0'));
            $table->setAutoGrow(true);

            $citation = $pub->getCitationHtml($url_prefix) . '&nbsp;' 
                . getPubIcons($db, $pub, 0xF, $url_prefix);

            if ((is_array($options) && !empty($options['show_internal_info'])
            && $options['show_internal_info'])
            || (isset($_SESSION['user'])
            && ($_SESSION['user']->showInternalInfo()))) {
                $citation .= '<br/><span style="font-size:80%">';
                if (isset($pub->ranking))
                $citation .= 'Ranking: ' . $pub->ranking;

                if (is_array($pub->collaborations)
                && (count($pub->collaborations) > 0)) {

                    $values = array();
                    foreach ($pub->collaborations as $col_id) {
                        $values[] = $col_desciptions[$col_id];
                    }

                    $citation .= '<br/>Collaboration:'
                    . implode(', ', $values);
                }
                $citation .= '</span>';
            }

            if ($enumerate)
            $cells[] = $count . '.';

            $cells[] = $citation;

            $table->addRow($cells);

            if ($enumerate)
            $table->updateColAttributes(
            0, array('class' => 'item'), NULL);

            $result .= $table->toHtml();
            unset($table);

            if (($max > 0) && ($count >= $max)) break;
        }
    }

    return $result;
}

/**
 * Returns the HTML text to display the icons to link to the PDF, view,
 * edit, or delete the publication entry.
 *
 * @param object $pub pdPublication object to display the icons for.
 * @param integer $flags the icons to display. 0x1 for the PDF/PS file,
 * 0x2 for the view icon, 0x4 for the edit icon, 0x8 for the delete icon.
 * @param string $url_prefix the prefix to use for URLs.
 * @return HTML text.
 */
function getPubIcons(&$db, &$pub, $flags = 0xf, $url_prefix = NULL) {
    $html = '';
    $access_level = pdUser::check_login($db);
    
    if (!isset($url_prefix)) {
        // get url_prefix from script's name
        $url_prefix = '';
        if (strstr(relativeUrlGet(), '/'))
            $url_prefix = '../';
    }

    if (($flags & 0x1) && (strtolower($pub->paper) != 'no paper')) {
        $html .= '<a href="' . $pub->paperAttGetUrl() . '">';

        if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
            $html .= '<img src="' . $url_prefix
            . 'images/pdf.gif" alt="PDF" '
            . 'height="18" width="17" border="0" align="top" />';
        }
        else if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
            $html .= '<img src="' . $url_prefix
            . 'images/ppt.gif" alt="PPT" height="18" '
            . 'width="17" border="0" align="top" />';
        }
        else if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
            $html .= '<img src="' . $url_prefix
            . 'images/ps.gif" alt="PS" height="18" '
            . 'width="17" border="0" align="top" />';
        }
        $html .= '</a>';
    }

    if ($flags & 0x2) {
        $html .= '<a href="' . $url_prefix
        . 'view_publication.php?pub_id='
        . $pub->pub_id . '">'
        . '<img src="' . $url_prefix
        .'images/viewmag.gif" title="view" alt="view" '
        . ' height="16" width="16" border="0" align="top" /></a>';
    }

    if (($flags & 0x4) && ($access_level > 0)) {
        $html .= '<a href="' . $url_prefix
        . 'Admin/add_pub1.php?pub_id='
        . $pub->pub_id . '">'
        . '<img src="' . $url_prefix
        . 'images/pencil.gif" title="edit" alt="edit" '
        . ' height="16" width="16" border="0" align="top" />'
        . '</a>';
    }

    if (($flags & 0x8) && ($access_level > 0)) {
        $html .= '<a href="' . $url_prefix
        . 'Admin/delete_publication.php?pub_id='
        . $pub->pub_id . '">'
        . '<img src="' . $url_prefix
        . 'images/kill.gif" title="delete" alt="delete" '
        . 'height="16" width="16" border="0" align="top" /></a>';
    }

    return $html;
}

?>