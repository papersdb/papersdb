<?php ;

// $Id: pdVenue.php,v 1.33 2007/10/29 21:35:25 loyola Exp $

/**
 * Implements a class that accesses venue information from the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */
require_once 'includes/pdDbAccessor.php';
require_once 'includes/pdCategory.php';

/* TODO: Explore the possibility of using a hierarchy in the database for
 * workshops that are associated with conferences.
 */

/**
 * Class that accesses venue information from the database.
 *
 * @package PapersDB
 */
class pdVenue extends pdDbAccessor {
    public $venue_id;
    public $title;
    public $name;
    public $url;
    public $type;
    public $cat_id;
    public $data;
    public $editor;
    public $date;
    public $occurrences;
    public $v_usage;
    public $rank_id;
    public $ranking;
    public $category;
    private static $vopts = null;

    /**
     * Constructor.
     */
    public function __construct($mixed = null) {
        parent::__construct($mixed);
    }

    /**
     * Loads a specific publication frobm the database.
     *
     * Use flags to load individual tables
     */
    public function dbLoad($db, $id) {
        assert('is_object($db)');

        if (count($this->occurrences) > 0)
            unset($this->occurrences);

        $q = $db->selectRow('venue', '*', array('venue_id' => $id),
                            "pdVenue::dbLoadVenue");
        if ($q === false) return false;
        $this->load($q);

        if ($this->v_usage)
            $this->v_usage = 'single';
        else
            $this->v_usage = 'all';

        $q = $db->select('venue_occur', '*', array('venue_id' => $id),
                         "pdVenue::dbLoadVenue",
                         array('ORDER BY' => 'date'));
        $r = $db->fetchObject($q);
        while ($r) {
            $this->occurrences[] = $r;
            $r = $db->fetchObject($q);
        }

        if (isset($this->rank_id)) {
            if ($this->rank_id > 0) {
                $q = $db->selectRow('venue_rankings', 'description',
                                    array('rank_id' => $this->rank_id),
                                    "pdVenue::dbLoad");
                if ($q !== false)
                    $this->ranking = $q->description;
            }
            else if ($this->rank_id == -1) {
                $q = $db->selectRow('venue_rankings', 'description',
                                    array('venue_id'  => $this->venue_id),
                                    "pdVenue::dbLoad");
                if ($q !== false) {
                    $this->rank_id = $q->rank_id;
                    $this->ranking = $q->description;
                }
            }
        }

        if (!empty($this->cat_id) && ($this->cat_id > 0)) {
            $this->category = new pdCategory();
            $result = $this->category->dbLoad($db, $this->cat_id);
            assert('$result');
        }
        
        if (self::$vopts == null)
        	self::voptsGet($db);
        
        // venue options
        $q = $db->select('venue_vopts', '*', array('venue_id' => $id),
                         "pdVenue::dbLoadVenue");
        if ($q !== false) {
	        $r = $db->fetchObject($q);
    	    while ($r) {
    	    	$member = self::$vopts[$r->vopt_id];
        	    $this->$member = $r->value;
            	$r = $db->fetchObject($q);
	        }
        }

        return true;
    }

    /**
     *
     */
    public function dbSave($db) {
        assert('is_object($db)');

        $values = $this->membersAsArray();
        unset($values['occurrences']);
        unset($values['category']);
        
        // venue options
        $db->delete('venue_vopts', array('venue_id' => $this->venue_id),
                    'pdVenue::dbSave');
        
        if (!empty($this->data)) {
        	$vopt_id = 0;
        	
        	if ($this->type == 'Workshop')
        		$vopt_id = 2;
        	else if ($this->type == 'Journal')
        		$vopt_id = 1;
        	else if ($this->type != 'Conference')
        		$vopt_id = 3;
        	
        	if ($vopt_id > 0)
 	      		$db->insert('venue_vopts', 
    	           			array('venue_id' => $this->venue_id,
        	       				  'vopt_id'  => $vopt_id,
      			        		  'value'    => $this->data),
                	         'pdVenue::dbSave');	
        }        

        // rank_id
        $db->delete('venue_rankings', array('venue_id' => $this->venue_id),
                    'pdVenue::dbSave');

        if ($this->rank_id == -1) {
            $db->insert('venue_rankings', array('venue_id' => $this->venue_id,
                                                'description' => $this->ranking),
                        'pdVenue::dbSave');
            $this->rank_id = $db->insertId();

            $db->update('publication',
                        array('rank_id' => $this->rank_id),
                        array('venue_id' => $this->venue_id),
                        'pdVenue::dbSave');
        }
        unset($values['ranking']);

        if ($this->v_usage == 'single')
            $values['v_usage'] = '1';

        if ($this->cat_id == -1)
            $values['cat_id'] = null;

        if ($this->venue_id != '') {
            $this->dbUpdateOccurrence($db);

            $db->update('venue', $values, array('venue_id' => $this->venue_id),
                        'pdVenue::dbSave');
            $this->venue_id = $db->insertId();
            $this->dbUpdateOccurrence($db);
        }
        else {
            $db->insert('venue', $values, 'pdVenue::dbSave');
            $this->venue_id = $db->insertId();
            $this->dbUpdateOccurrence($db);
        }
    }

    public function dbUpdateOccurrence($db) {
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
    public function dbDelete ($db) {
        assert('is_object($db)');

        $tables = array('venue', 'venue_occur', 'venue_rankings');

        foreach ($tables as $table) {
            $db->delete($table, array('venue_id' => $this->venue_id),
                        'pdVenue::dbDelete');
        }
        return $db->affectedRows();
    }

    public function processVenueData($str) {
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

    public function addOccurrence($location, $date, $url) {
        assert('$location != ""');
        assert('is_object($this->category)');

        // make sure this venue is a conference or workshop
        if (($this->category->category != "In Conference")
            && ($this->category->category != "In Workshop")) {
            assert('0');
        }

        $o = new stdClass;
        $o->location = $location;
        $o->date = $date;
        $o->url = $url;

        $this->occurrences[] = $o;
    }

    public function deleteOccurrences() {
        unset($this->occurrences);
    }

    public function urlGet($year = null) {
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

    public function locationGet($year = null) {
        $location = null;

        if (($year != null) && (count($this->occurrences) > 0)) {
            foreach ($this->occurrences as $o) {
                $o_date = split('-', $o->date);
                if ($o_date[0] == $year) {
                    $location = $o->location;
                }
            }
        }

        if (is_object($this->category)
            && ($this->category->category == 'Conference')
            && ($location == null)) {
            $location = $this->data;
        }

        return $location;
    }

    // note: some venue names in the database contain URLs. This function
    // returns the name without the URL text.
    public function nameGet() {
        if (strpos($this->name, '<a href=') !== false) {
            return preg_replace('/<a href=[\'"][^\'"]+[\'"]>([^<]+)(?:<\/a>)?(.*)/',
                                '$1$2', $this->name);
        }
        return $this->name;
    }

    public function rankingsGlobalGet(&$db) {
        $q = $db->select('venue_rankings', '*', 'venue_id is NULL',
                         "pdVenue::dbLoad");
        assert('$q !== false');

        $r = $db->fetchObject($q);
        while ($r) {
            $rankings[$r->rank_id] = $r->description;
            $r = $db->fetchObject($q);
        }

        return $rankings;
    }

    public function categoryAdd($db, $cat_id) {
        if ($cat_id > 0) {
            $this->category = new pdCategory();
            $result = $this->category->dbLoad($db, $cat_id);
            assert('$result');
        }
    }

    public function categoryGet() {
        if (!is_object($this->category))
            return null;

        if (strpos($this->category->category, 'In ') === 0)
            return substr($this->category->category, 3);
        return $this->category->category;
    }
    
    private static function voptsGet($db) {
        assert('is_object($db)');
    
    	if (is_array(self::$vopts)) return;
    	
        $q = $db->select('vopts', '*', null, "pdVenue::dbLoadVenue");
        
        if ($q === false) return null;
        self::$vopts = array();
        
        $r = $db->fetchObject($q);
        while ($r) {
            self::$vopts[$r->vopt_id] = $r->name;
            $r = $db->fetchObject($q);
        }
    }
    
    public function venueVoptsGet() {
    	$result = array();
    	foreach (self::$vopts as $vopt) {
    		if (! empty($this->$vopt))
    			$result[$vopt] = $this->$vopt;
    	}
    	return $result;
    }
}

?>
