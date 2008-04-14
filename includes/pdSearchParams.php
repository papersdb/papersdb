<?php ;



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
    public $search;
    public $cat_id;
    public $title;
    public $authors;
    public $author_myself;
    public $paper;
    public $abstract;
    public $venue;
    public $keywords;
    public $startdate;
    public $enddate;
    public $paper_rank;
    public $paper_rank_other;
    public $paper_col;
    public $show_internal_info;

    /**
     * Constructor.
     */
    public function __construct($mixed = null) {
        if (is_array($mixed)) {
            foreach (array_keys(get_class_vars(get_class($this))) as $member) {
                if (isset($mixed[$member])) {
                    $this->$member= $mixed[$member];
                }
            }
        }
    }

    public function paramGet($param) {
        if (!in_array($param, $this->params)) return null;

        return $this->$param;
    }

    public function paramsToHtmlQueryStr() {
        $results = array();

        foreach (array_keys(get_class_vars(get_class($this))) as $param) {
            if (isset($this->$param) && ($this->$param != '')) {
                if (is_array($this->$param)) {
                    foreach ($this->$param as $key => $value) {
                        if (strlen($value) > 0)
                            $results[] = $param . '[' . $key . ']='
                                . urlencode($value);
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
