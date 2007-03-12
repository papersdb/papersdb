<?php ;

// $Id: pdSearchParams.php,v 1.7 2007/03/12 05:25:45 loyola Exp $

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

    /**
     * Constructor.
     */
    function pdSearchParams($mixed = null) {
        if (is_array($mixed)) {
            foreach (array_keys(get_class_vars('pdSearchParams')) as $member) {
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

        foreach ($this->params as $param) {
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
