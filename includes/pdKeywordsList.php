<?php ;

// $Id: pdKeywordsList.php,v 1.1 2006/08/08 21:30:47 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

/**
 * \brief
 */
class pdKeywordsList {
    var $list;

    /**
     * Constructor.
     */
    function pdKeywordsList(&$db) {
        assert('is_object($db)');

        $this->list = array();

        $q = $db->select('publication', array('DISTINCT keywords'), '',
                         "pdKeywordsList::dbLoad");
        if ($q === false) return;
        $r = $db->fetchObject($q);

        $re = '/(\#?\([^()]+\))[,;]?/';

        // if text is in parentheses then it is a single item
        while ($r) {
            $info = $r->keywords;
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
