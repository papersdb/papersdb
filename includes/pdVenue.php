<?php ;

// $Id: pdVenue.php,v 1.9 2006/08/03 21:54:48 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of venue data to / from the database.
 *
 *
 */

/**
 *
 * \brief Class for storage and retrieval of venue to / from the
 * database.
 */
class pdVenue {
    var $venue_id;
    var $title;
    var $name;
    var $url;
    var $type;
    var $data;
    var $editor;
    var $date;
    var $occurrence;
    var $venue_table = 'venue';


    /**
     * Constructor.
     */
    function pdVenue($obj = NULL) {
        if (!is_null($obj))
            $this->load($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $id) {
        assert('is_object($db)');

        $q = $db->selectRow($this->venue_table, '*', array('venue_id' => $id),
                         "pdVenue::dbLoadVenue");
        if ($q === false) return false;
        $this->load($q);

        $q = $db->select('venue_occur', '*', array('venue_id' => $id),
                         "pdVenue::dbLoadVenue");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->occurrence[$r->year] = $r->location;
            $r = $db->fetchObject($q);
        }
        return true;
    }

    function changeTable() {
        $this->venue_table = 'venue2';
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

            $db->update($this->venue_table,
                        $values, array('venue_id' => $this->venue_id),
                        'pdVenue::dbSave');
            $this->venue_id = $db->insertId();
            return $db->affectedRows();
        }
        else {
            $db->insert($this->venue_table, $values, 'pdVenue::dbSave');
            $this->venue_id = $db->insertId();
            $this->dbUpdateOccurrence($db);
            return true;
        }
    }

    function dbUpdateOccurrence(&$db) {
        if ($this->venue_table != 'venue2') return;

        if (isset($this->venue_id))
            $db->delete('venue_occur', array('venue_id' => $this->venue_id),
                        'pdVenue::dbSave');

        if (count($this->occurrence) > 0) {
            $arr = array();
            foreach ($this->occurrence as $year => $location) {
                array_push($arr, array('venue_id' => $this->venue_id,
                                       'year' => $year,
                                       'location' => $location));
            }
            $db->insert('venue_occur', $arr, 'pdVenue::dbSave');
        }
    }

    /**
     *
     */
    function dbDelete (&$db) {
        assert('is_object($db)');
        $db->delete($this->venue_table, array('venue_id' => $this->venue_id),
                    'pdVenue::dbDelete');
        return $db->affectedRows();
    }

    /**
     * Loads publication data from the object passed in
     */
    function load($mixed) {
        if (is_object($mixed)) {
            if (isset($mixed->venue_id))
                $this->venue_id = $mixed->venue_id;
            if (isset($mixed->title))
                $this->title = $mixed->title;
            if (isset($mixed->name))
                $this->name = $mixed->name;
            if (isset($mixed->url))
                $this->url = $mixed->url;
            if (isset($mixed->type))
                $this->type = $mixed->type;
            if (isset($mixed->editor))
                $this->editor = $mixed->editor;
            if (isset($mixed->date))
                $this->date = $mixed->date;

            $this->processVenueData($mixed->data);
        }
        else if (is_array($mixed)) {
            if (isset($mixed['venue_id']))
                $this->venue_id = $mixed['venue_id'];
            if (isset($mixed['title']))
                $this->title = $mixed['title'];
            if (isset($mixed['name']))
                $this->name = $mixed['name'];
            if (isset($mixed['url']))
                $this->url = $mixed['url'];
            if (isset($mixed['type']))
                $this->type = $mixed['type'];
            if (isset($mixed['editor']))
                $this->editor = $mixed['editor'];
            if (isset($mixed['date']))
                $this->date = $mixed['date'];

            $this->processVenueData($mixed['data']);
        }

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

            if ($this->type == 'Conference') {
                if ($str != '')
                    $this->occurrence[$year] = $str;
                else
                    $this->occurrence[$year] = '';
            }
        }
    }

    function addOccurrence($year, $location) {
        assert('$year != ""');

        if ($this->type == 'Conference') {
            if ($location != '')
                $this->occurrence[$year] = $location;
            else
                $this->occurrence[$year] = '';
        }
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

        if (count($this->occurrence) > 0) {
            foreach ($this->occurrence as $year => $location) {
                $str .= '(' . $year;
                if ($location != '')
                    $str .= ', ' . $location;
                $str .= ') ';
            }
        }

        return $str;
    }
}

?>
