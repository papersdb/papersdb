<?php ;

// $Id: pdVenue.php,v 1.23 2007/03/14 20:27:40 aicmltec Exp $

/**
 * Implements a class that accesses venue information from the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */
require_once 'includes/pdDbAccessor.php';

/**
 * Class that accesses venue information from the database.
 *
 * @package PapersDB
 */
class pdVenue extends pdDbAccessor {
    var $venue_id;
    var $title;
    var $name;
    var $url;
    var $type;
    var $data;
    var $editor;
    var $date;
    var $occurrences;

    /**
     * Constructor.
     */
    function pdVenue($mixed = null) {
        parent::pdDbAccessor($mixed);
    }

    /**
     * Loads a specific publication frobm the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $id) {
        assert('is_object($db)');

        if (count($this->occurrences) > 0)
            unset($this->occurrences);

        $q = $db->selectRow('venue', '*', array('venue_id' => $id),
                            "pdVenue::dbLoadVenue");
        if ($q === false) return false;
        $this->load($q);

        $q = $db->select('venue_occur', '*', array('venue_id' => $id),
                         "pdVenue::dbLoadVenue",
                         array('ORDER BY' => 'date'));
        $r = $db->fetchObject($q);
        while ($r) {
            $this->occurrences[] = $r;
            $r = $db->fetchObject($q);
        }
        return true;
    }

    /**
     *
     */
    function dbSave(&$db) {
        assert('is_object($db)');

        $values = array('title'    => $this->title,
                        'name'     => $this->name,
                        'url'      => $this->url,
                        'type'     => $this->type,
                        'data'     => $this->data,
                        'editor'   => $this->editor,
                        'date'     => $this->date);

        if ($this->venue_id != '') {
            $this->dbUpdateOccurrence($db);

            $db->update('venue',
                        $values, array('venue_id' => $this->venue_id),
                        'pdVenue::dbSave');
            $this->venue_id = $db->insertId();
            $this->dbUpdateOccurrence($db);
            return $db->affectedRows();
        }
        else {
            $db->insert('venue', $values, 'pdVenue::dbSave');
            $this->venue_id = $db->insertId();
            $this->dbUpdateOccurrence($db);
            return true;
        }
    }

    function dbUpdateOccurrence(&$db) {
        if (isset($this->venue_id))
            $db->delete('venue_occur', array('venue_id' => $this->venue_id),
                        'pdVenue::dbSave');

        if (!isset($this->occurrences)) return;

        $arr = array();
        foreach ($this->occurrences as $o) {
            array_push($arr, array('venue_id' => $this->venue_id,
                                   'location' => $o->location,
                                   'date'     => $o->date,
                                   'url'      => $o->url));
        }
        $db->insert('venue_occur', $arr, 'pdVenue::dbUpdateOccurrence');
    }

    /**
     *
     */
    function dbDelete (&$db) {
        assert('is_object($db)');
        $db->delete('venue', array('venue_id' => $this->venue_id),
                    'pdVenue::dbDelete');
        $db->delete('venue_occur', array('venue_id' => $this->venue_id),
                    'pdVenue::dbDelete');
        return $db->affectedRows();
    }

    function processVenueData($str) {
        $this->data = $str;

        if (preg_match("/([\w\s-]+)['-](\d+)/", $this->title, $venue_title)) {
            $year = '';
            if ($venue_title[2] != '')
                if ($venue_title[2] > 75)
                    $year = $venue_title[2] + 1900;
                else if ($venue_title[2] <= 75)
                    $year = $venue_title[2] + 2000;
        }
    }

    function addOccurrence($location, $date, $url) {
        assert('$location != ""');
        assert('($this->type == "Conference") || ($this->type == "Workshop")');

        $o = new stdClass;
        $o->location = $location;
        $o->date = $date;
        $o->url = $url;

        $this->occurrences[] = $o;
    }

    function deleteOccurrences() {
        unset($this->occurrences);
    }

    function toStr() {
        $str = $this->venue_id . ', '
            . $this->title . ', '
            . $this->name . ', '
            . $this->url . ', '
            . $this->type . ', '
            . $this->data . ', '
            . $this->editor . ', '
            . $this->date . ', ';

        if (count($this->occurrences) > 0) {
            foreach ($this->occurrences as $o) {
                $str .= '(' . $o->date;
                if ($o->location != '')
                    $str .= ', ' . $o->location;
                $str .= ') ';
            }
        }

        return $str;
    }

    function urlGet($year = null) {
        $url = null;

        if (($year != null) && (count($this->occurrences) > 0)) {
            foreach ($this->occurrences as $o) {
                $o_date = split('-', $o->date);
                if ($o_date[0] == $year) {
                    $url = $o->url;
                }
            }
        }

        // if no URL associated with occurrence try to get the URL from the
        // venue or name
        if ($url == null) {
            if (($this->url != '') && ($this->url != 'http://')) {
                $url = $this->url;
            }
            else if (strpos($this->name, '<a href=') !== false) {
                // try to get venue URL from the name
                //
                // note: some venue names with URLs don't close the <a href> tag
                $url = preg_replace(
                    '/<a href=[\'"]([^\'"]+)[\'"]>[^<]+(<\/a>)?.+/', '$1',
                    $this->name);
            }
        }

        if (($url != '') && ($url != 'http://')) {
            if (strpos($url, 'http://') === false)
                $url = 'http://' . $url;
        }

        return $url;
    }

    function locationGet($year = null) {
        $location = null;

        if (($year != null) && (count($this->occurrences) > 0)) {
            foreach ($this->occurrences as $o) {
                $o_date = split('-', $o->date);
                if ($o_date[0] == $year) {
                    $location = $o->location;
                }
            }
        }

        if (($this->type == 'Conference') && ($location == null)) {
            $location = $this->data;
        }

        return $location;
    }

    // note: some venue names in the database contain URLs. This function
    // returns the name without the URL text.
    function nameGet() {
        if (strpos($this->name, '<a href=') !== false) {
            return preg_replace('/<a href=[\'"][^\'"]+[\'"]>([^<]+)(?:<\/a>)?(.*)/',
                                '$1$2', $this->name);
        }
        return $this->name;
    }
}

?>
