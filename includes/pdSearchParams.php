<?php ;

// $Id: pdSearchParams.php,v 1.9 2007/03/27 22:03:15 aicmltec Exp $

/**
 * Storage and retrieval of user data to / from the database.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/**
 * Class for storage and retrieval of user to / from the
 * database.
 *
 * @package PapersDB
 */
class pdSearchParams {
    var $search;
    var $cat_id;
    var $title;
    var $author_myself;
    var $authortyped;
    var $authorselect;
    var $paper;
    var $abstract;
    var $venue;
    var $keywords;
    var $startdate;
    var $enddate;
    var $paper_rank;
    var $paper_rank_other;
    var $paper_col;

    /**
     * Constructor.
     */
    function pdSearchParams($mixed = null) {
        if (is_array($mixed)) {
            foreach (array_keys(get_class_vars(get_class($this))) as $member) {
                if (isset($mixed[$member])) {
                    $this->$member= $mixed[$member];
                }
            }
        }
    }

    function paramGet($param) {
        if (!in_array($param, $this->params)) return null;

        return $this->$param;
    }

    function paramsToHtmlQueryStr() {
        $results = array();

        foreach (array_keys(get_class_vars(get_class($this))) as $param) {
            if (isset($this->$param) && ($this->$param != '')) {
                if (is_array($this->$param)) {
                    foreach ($this->$param as $key => $value) {
                        $results[]
                            = $param . '[' . $key . ']=' . urlencode($value);
                    }
                }
                else {
                    $results[] = $param . '=' . urlencode($this->$param);
                }
            }
        }

        if (count($results) > 0) {
            return implode('&', $results);
        }
        return null;
    }

}

?>
