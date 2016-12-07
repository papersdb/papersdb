<?php

/**
 * Implements a class that builds a list of publications.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/** Requires the publication class.*/
require_once 'pdPublication.php';

/**
 * Class that builds a list of publications.
 *
 * @package PapersDB
 */
class pdPubList {
    protected static $cat_display_order = array('In Journal (refereed)',
                                                'In Journal (unrefereed)',
                                                'In Conference (refereed)',
                                                'In Conference (unrefereed)',
                                                'In Workshop',
                                                'Other');

    private function __construct() {}

    /**
     * The publications that are loaded depend on the $options array.
     *
     * @param object $db      Database access object.
     * @param array  $options An associative array.
     */
    public static function create($db, $options = null) {
        assert('is_object($db)');

        if (!isset($options['num_to_load']))
            $options['num_to_load'] = -1;

        if (!isset($options['sort']))
            $options['sort'] = -1;

        if (isset($options['auth_pubs'])) {
            return self::authorPubsNumGet($db, $options['auth_pubs']);
        }
        else if (isset($options['author_id'])) {
            return self::authorIdPubsDbLoad($db, $options['author_id'],
                                    $options['num_to_load']);
        }
        else if (isset($options['author_id_cat'])) {
            return self::authorIdCatPubsDbLoad($db, $options['author_id_cat']);
        }
        else if (isset($options['author_name'])
	        && isset($options['date_start'])
    	    && isset($options['date_end'])) {
    	    $pub_id_keys = false;
    	    if (isset($options['pub_id_keys']))
    	    	$pub_id_keys = $options['pub_id_keys'];

            return self::authorNamePubsDbLoad($db, $options['author_name'],
                                        	  $options['date_start'],
                                        	  $options['date_end'],
                                        	  $pub_id_keys);
        }
        else if (isset($options['venue_id'])) {
            return self::venuePubsDbLoad($db, $options['venue_id']);
        }
        else if (isset($options['venue_id_count'])) {
            return self::venuePubsCount($db, $options['venue_id_count']);
        }
        else if (isset($options['cat_id'])) {
            return self::categoryPubsDbLoad($db, $options['cat_id']);
        }
        else if (isset($options['keywords_list'])) {
            return self::keywordsList($db);
        }
        else if (isset($options['keyword'])) {
            return self::keywordPubsDBLoad($db, $options['keyword']);
        }
        else if (isset($options['year_list'])) {
            return self::yearsPubsDBLoad($db);
        }
        else if (isset($options['year'])) {
            return self::yearPubsDBLoad($db, $options['year']);
        }
        else if (isset($options['date_start']) && isset($options['date_end'])) {
            return self::datePubsDBLoad($db, $options['date_start'],
            	$options['date_end']);
        }
        else if (isset($options['year_cat'])) {
            return self::yearCategoryPubsDBLoad($db, $options['year_cat']);
        }
        else if (isset($options['title']) && is_array($options['title'])) {
            return self::titlePubsDBLoad($db, $options['title']);
        }
        else if (isset($options['pub_ids']) && is_array($options['pub_ids'])){
            return self::arrayPubsDBLoad($db, $options['pub_ids'], $options['sort']);
        }
        else if (isset($options['cat_pub_ids'])
                 && is_array($options['cat_pub_ids'])){
            return self::arrayPubsDBLoadByCategory($db, $options['cat_pub_ids']);
        }
        else if (isset($options['sort_by_updated'])) {
            return self::allPubsDbLoad($db, $options['sort_by_updated']);
        }
        else {
            return self::allPubsDbLoad($db);
        }
    }

    /**
     * Retrieves all publications.
     */
    private static function &allPubsDbLoad($db, $sortByDesc = false) {
        assert('is_object($db)');

        if ($sortByDesc)
            $order = 'updated DESC';
        else
            $order = 'title ASC';

        $q = $db->select('publication', '*', '', "pdPubList::allPubsDbLoad",
                         array('ORDER BY' => $order));

        $list = array();
        foreach ($q as $r) {
            $list[] = new pdPublication($r);
        }
        return $list;
    }

    /**
     * Retrieves the number of publications for a given author.
     */
    public static function authorPubsNumGet($db, $author_name) {
        assert('is_object($db)');
        assert('$author_name != ""');

        $q = $db->selectRow(array('author', 'publication', 'pub_author'),
                            'count(publication.pub_id) as pcount',
                            array('author.author_id=pub_author.author_id',
                                  'pub_author.pub_id=publication.pub_id',
                                  'author.name like "' . $author_name . '%"'),
                            "pdPubList::authorPubsDbLoad");

        if ($q === false) return 0;

        return $q->pcount;
    }

    /**
     * Retrieves publications for a given author.
     */
    private static function authorIdPubsDbLoad($db, $author_id, $numToLoad) {
        assert('is_object($db)');
        assert('!empty($author_id)');

        $list = array();
        if ($numToLoad == 0) return $list;

        $q = $db->select(array('publication', 'pub_author'),
                         array('publication.pub_id', 'publication.title',
                               'publication.paper', 'publication.abstract',
                               'publication.keywords', 'publication.published',
                               'publication.updated'),
                         array('pub_author.pub_id=publication.pub_id',
                               'pub_author.author_id'
                               => $db->quote_smart($author_id)),
                         "pdPubList::authorIdPubsDbLoad",
                         array('ORDER BY' => 'publication.published ASC'));

        $count = count($q);
        if ($count == 0) return $list;

        // if $numToLoad is -1 then we load all publications
        if ($numToLoad == -1)
            $numToLoad = $count;

        foreach ($q as $r) {
            if ($numToLoad <= 0) break;
            $list[] = new pdPublication($r);
            $numToLoad--;
        }

        uasort($list, array('pdPublication', 'pubsDateSortDesc'));
        return $list;
    }

    /**
     * Retrieves publications for a given author.
     */
    private static function authorIdCatPubsDbLoad($db, $author_id) {
        assert('is_object($db)');
        assert('$author_id != ""');

        $q = $db->select(array('publication', 'pub_author'),
                         'publication.pub_id',
                         array('pub_author.pub_id=publication.pub_id',
                               'pub_author.author_id'
                               => $db->quote_smart($author_id)),
                         "pdPubList::authorIdPubsDbLoad",
                         array('ORDER BY' => 'publication.published ASC'));

        $pub_ids = array();
        foreach ($q as $r) {
            $pub_ids[] = $r->pub_id;
        }

        return self::arrayPubsDBLoadByCategory($db, $pub_ids);
    }

    /**
     * Retrieves publications for a given author name.
     */
    private static function authorNamePubsDbLoad($db, $author_name,
    											 $date_start = null,
                                  				 $date_end = null,
                                  				 $pub_id_keys = false) {
        assert('is_object($db)');
        assert('$author_name != ""');

        $conds = array('pub_author.pub_id=publication.pub_id',
                       'author.author_id=pub_author.author_id',
                       'author.name LIKE "' . $author_name . '%"');

        if ($date_start != null) {
            if ($date_end == null)
                $date_end = date('Y-m-d');

            $conds[] = 'publication.published BETWEEN \'' . $date_start
                . '\' AND \'' . $date_end . '\'';
        }

        $q = $db->select(array('publication', 'author', 'pub_author'),
                         array('publication.pub_id', 'publication.title',
                               'publication.paper', 'publication.abstract',
                               'publication.keywords', 'publication.published',
                               'publication.updated'),
                         $conds,
                         "pdPubList::authorIdPubsDbLoad",
                         array('ORDER BY' => 'publication.published ASC'));

        $list = array();
        foreach ($q as $r) {
            if ($pub_id_keys)
        		$list[$r->pub_id] = new pdPublication($r);
        	else
            	$list[] = new pdPublication($r);
        }

        uasort($list, array('pdPublication', 'pubsDateSortDesc'));
        return $list;
    }

    /**
     * Retrieves publications for a given category.
     */
    private static function venuePubsDbLoad($db, $venue_id) {
        assert('is_object($db)');
        assert('$venue_id != ""');

        $q = $db->select('publication', '*', array('venue_id' => $venue_id),
                         "pdPubList::venuePubsDbLoad");

        $list = array();
        foreach ($q as $r) {
            $list[] = new pdPublication($r);
        }

        uasort($list, array('pdPublication', 'pubsDateSortDesc'));
        return $list;
    }

    /**
     * Retrieves publications for a given category.
     */
    private static function venuePubsCount($db, $venue_id) {
        assert('is_object($db)');
        assert('$venue_id != ""');

        $q = $db->selectRow('publication', 'count(pub_id) as pcount',
 						    array('venue_id' => $venue_id),
                            "pdPubList::venuePubsCount");

        if ($q === false) return 0;

        return $q->pcount;
    }

    /**
     * Retrieves publications for a given category.
     */
    private static function categoryPubsDbLoad($db, $cat_id) {
        assert('is_object($db)');
        assert('$cat_id != ""');

        $q = $db->select(array('publication', 'pub_cat'),
                         array('publication.pub_id', 'publication.title',
                               'publication.paper', 'publication.abstract',
                               'publication.keywords', 'publication.published',
                               'publication.updated'),
                         array('pub_cat.pub_id=publication.pub_id',
                               'pub_cat.cat_id' => $cat_id),
                         "pdPubList::categoryPubsDbLoad");

        $list = array();
        foreach ($q as $r) {
            $list[] = new pdPublication($r);
        }

        uasort($list, array('pdPublication', 'pubsDateSortDesc'));
        return $list;
    }

    /**
     *
     */
    public static function authorNumPublications ($db, $author_id) {
        $q = $db->select(array('publication', 'pub_author'),
                         'publication.pub_id',
                         array('publication.pub_id=pub_author.pub_id',
                               'pub_author.author_id' => $author_id),
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'publication.title ASC'));
        return count($q);
    }

    private static function arrayPubsDBLoad($db, $pub_ids, $sort = true) {
        assert('is_object($db)');
        assert('is_array($pub_ids)');

        $list = array();
        if (count($pub_ids) == 0) return $list;

        foreach ($pub_ids as $pub_id) {
            assert('is_numeric($pub_id)');

            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id, pdPublication::DB_LOAD_BASIC);
            if ($result !== false)
                $list[$pub_id] = $pub;

        }

        if ($sort)
        	uasort($list, array('pdPublication', 'pubsDateSortDesc'));
        return $list;
    }

    private static function arrayPubsDBLoadByCategory($db, $pub_ids) {
        assert('is_object($db)');
        assert('is_array($pub_ids)');

        $list = array();
        if (count($pub_ids) == 0) return $list;

        foreach ($pub_ids as $pub_id) {
            if (!is_numeric($pub_id)) continue;

            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id,
                                   pdPublication::DB_LOAD_BASIC
                                   | pdPublication::DB_LOAD_CATEGORY);
    		if ($result !== false)
    			$pubs[] = $pub;
        }

        return self::arrayPubsSortByCategory($db, $pubs);
    }

    private static function &arrayPubsSortByCategory($db, $pubs) {
        $list['type'] = 'category';

        foreach ($pubs as $pub) {
            if (is_object($pub->category))
                switch ($pub->category->category) {
                    case 'In Journal':
                    case 'In Conference':
                        if ($pub->rank_id <= 3)
                            $app = ' (refereed)';
                        else
                            $app = ' (unrefereed)';
                        $list[$pub->category->category . $app][] = $pub;
                        break;

                    case 'In Workshop':
                        $list[$pub->category->category][] = $pub;
                        break;

                    default:
                        $list['Other'][] = $pub;
                        break;
                }
            else
                $list['Other'][] = $pub;
        }

        ksort($list);

        foreach ($list as $category => $pubs) {
            if (is_array($list[$category]))
                uasort($list[$category],
                       array('pdPublication', 'pubsDateSortDesc'));
        }
        return $list;
    }

    private static function &yearsPubsDBLoad($db) {
        assert('is_object($db)');

        $q = $db->select('publication', 'distinct year(published) as year', '',
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'year DESC'));

        $list = array();
        foreach ($q as $r) {
            $list[] = array('year' => $r->year);
        }

        foreach ($list as $key => $item) {
            $q = $db->selectRow('publication', 'count(pub_id) as count',
                             array('year(published)' => $item['year']),
                             "pdPubList::publicationsDbLoad");
            $list[$key]['count'] = ($q !== false ? $q->count : 0);
        }
        return $list;
    }

    private static function &yearPubsDBLoad($db, $year) {
        assert('is_object($db)');

        $q = $db->select('publication', '*',
                         array('year(published)' => $year),
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'published DESC'));

        $list = array();
        foreach ($q as $r) {
            $list[] = new pdPublication($r);
        }
        return $list;
    }

    private static function &datePubsDBLoad($db, $date_start, $date_end) {
        assert('is_object($db)');

        $between = '\'' . $date_start . '\' AND \'' . $date_end . '\'';

        $q = $db->select('publication', '*',
                         array('published BETWEEN '. $between),
                         "pdPubList::datePubsDBLoad",
                         array( 'ORDER BY' => 'published DESC'));

        $list = array();
        foreach ($q as $r) {
            $list[] = new pdPublication($r);
        }
        return $list;
    }

    private static function yearCategoryPubsDBLoad($db, $year) {
        assert('is_object($db)');

        $q = $db->select('publication', 'pub_id',
                         array('year(published)' => $year),
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'published DESC'));

        $pub_ids = array();
        foreach ($q as $r) {
            $pub_ids[] = $r->pub_id;
        }

        return self::arrayPubsDBLoadByCategory($db, $pub_ids);
    }

    private static function &titlePubsDBLoad($db, $title) {
        assert('is_object($db)');

        $title = str_replace(' ', '%', $title);

        $q = $db->select(array('publication'), '*',
                         array('LOWER(title) LIKE' => 'LOWER(%' . $title . '%)'),
                         "pdPubList::titlePubsDBLoad",
                         array( 'ORDER BY' => 'published ASC'));

        $list = array();
        foreach ($q as $r) {
            $list[] = new pdPublication($r);
        }
        return $list;
    }

    private static function keywordsList($db) {
        assert('is_object($db)');

        $q = $db->select('publication', 'keywords', '',
                         "pdPubList::publicationsDbLoad");

        $list = array();
        foreach ($q as $r) {
            foreach (split('; *', $r->keywords) as $kw) {
                if ($kw != '') {
                    $list[$kw] = true;
                }
            }
        }

        return array_keys($list);
    }

    private static function &keywordPubsDBLoad($db, $kw) {
        assert('is_object($db)');

        $q = $db->select(array('publication'), '*',
                         array('keywords like "%' . $kw . '%"'),
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'title ASC'));

        $list = array();
        foreach ($q as $r) {
            $list[] = new pdPublication($r);
        }

        uasort($list, array('pdPublication', 'pubsDateSortDesc'));
        return $list;
    }

    public static function catDisplayOrder() {
        return self::$cat_display_order;
    }
}

?>
