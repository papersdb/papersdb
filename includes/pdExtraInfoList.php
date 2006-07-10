<?php ;

// $Id: pdExtraInfoList.php,v 1.1 2006/07/10 19:55:35 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

/**
 * \brief
 */
class pdExtraInfoList {
    var $list;

    /**
     * Constructor.
     */
    function pdExtraInfoList(&$db) {
        assert('is_object($db)');

        $this->list = array();

        $q = $db->select('publication', array('DISTINCT extra_info'), '',
                         "pdExtraInfoList::dbLoad");
        if ($q === false) return;
        $r = $db->fetchObject($q);

        $re = '/(\#?\([^()]+\))[,;]?/';

        // if text is in parentheses then it is a single item
        while ($r) {
            $info = $r->extra_info;
            $result = preg_match($re, $info, $match);
            while ($result != 0) {
                if (count($match) > 1) {
                    if (!in_array($match[1], $this->list))
                        $this->list[] = $match[1];
                }
                $info = preg_replace($re, '', $info, 1);
                $result = preg_match($re, $info, $match);
            }

            $info = str_replace(';', ',',  $info);
            $items = split(',', $info);
            foreach($items as $i) {
                $i = preg_replace('/^\s+/', '', $i);
                $i = preg_replace('/\s+$/', '', $i);
                if (($i != '') && (!in_array($i, $this->list))
                    && ($i != 'with'))
                    $this->list[] = $i;
            }
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
        sort($this->list);
    }
}

?>
