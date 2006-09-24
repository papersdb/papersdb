<?php ;

// $Id: pdSearchParams.php,v 1.3 2006/09/24 21:21:42 aicmltec Exp $

/**
 * Storage and retrieval of user data to / from the database.
 *
 * @package PapersDB
 */

/**
 * Class for storage and retrieval of user to / from the
 * database.
 *
 * @package PapersDB
 */
class pdSearchParams {
    /**
     * These are the only options allowed by this script. These can be passed
     * by either GET or POST methods.
     */
    var $params = array('search',
                        'cat_id',
                        'title',
                        'author_myself',
                        'authortyped',
                        'authorselect',
                        'paper',
                        'abstract',
                        'venue',
                        'keywords',
                        'startdate',
                        'enddate');

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
    var $enddat;

    /**
     * Constructor.
     */
    function pdSearchParams($mixed) {
        if (is_array($mixed)) {
            foreach ($this->params as $param) {
                if (isset($mixed[$param])) {
                    $this->$param= $mixed[$param];
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
