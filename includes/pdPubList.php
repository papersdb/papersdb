<?php ;

// $Id: pdPubList.php,v 1.25 2007/08/21 20:16:06 aicmltec Exp $

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
    var $list;
    var $type;
    var $count;

    /**
     * The publications that are loaded depend on the $options array.
     *
     * @param object $db      Database access object.
     * @param array  $options An associative array.
     */
    function pdPubList($db, $options = null) {
        assert('is_object($db)');

        if (!isset($options['num_to_load']))
            $options['num_to_load'] = -1;

        if (isset($options['auth_pubs'])) {
            $this->authorPubsNumGet($db, $options['auth_pubs']);
        }
        else if (isset($options['author_id'])) {
            $this->authorIdPubsDbLoad($db, $options['author_id'],
                                    $options['num_to_load']);
        }
        else if (isset($options['author_name'])) {
            $this->authorNamePubsDbLoad($db, $options['author_name'],
                                        $options['date_start'],
                                        $options['date_end']);
        }
        else if (isset($options['venue_id'])) {
            $this->venuePubsDbLoad($db, $options['venue_id']);
        }
        else if (isset($options['cat_id'])) {
            $this->categoryPubsDbLoad($db, $options['cat_id']);
        }
        else if (isset($options['keywords_list'])) {
            $this->keywordsList($db);
        }
        else if (isset($options['keyword'])) {
            $this->keywordPubsDBLoad($db, $options['keyword']);
        }
        else if (isset($options['year_list'])) {
            $this->yearsPubsDBLoad($db);
        }
        else if (isset($options['year'])) {
            $this->yearPubsDBLoad($db, $options['year']);
        }
        else if (isset($options['title']) && is_array($options['title'])) {
            $this->titlePubsDBLoad($db, $options['title']);
        }
        else if (isset($options['pub_ids']) && is_array($options['pub_ids'])){
            $this->arrayPubsDBLoad($db, $options['pub_ids']);
        }
        else if (isset($options['cat_pub_ids'])
                 && is_array($options['cat_pub_ids'])){
            $this->arrayPubsDBLoadByCategory($db, $options['cat_pub_ids']);
        }
        else if (isset($options['sort_by_updated'])) {
            $this->allPubsDbLoad($db, $options['sort_by_updated']);
        }
        else {
            $this->allPubsDbLoad($db);
        }
    }

    /**
     * Retrieves all publications.
     */
    function allPubsDbLoad($db, $sortByDesc = false) {
        assert('is_object($db)');

        if ($sortByDesc)
            $order = 'updated DESC';
        else
            $order = 'title ASC';

        $q = $db->select('publication', '*', '', "pdPubList::allPubsDbLoad",
                         array('ORDER BY' => $order));
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = new pdPublication($r);
            $r = $db->fetchObject($q);
        }
        if (isset($this->list))
            assert('is_array($this->list)');
    }

    /**
     * Retrieves the number of publications for a given author.
     */
    function authorPubsNumGet($db, $author_name) {
        assert('is_object($db)');
        assert('$author_name != ""');

        $q = $db->selectRow(array('author', 'publication', 'pub_author'),
                            'count(publication.pub_id) as pcount',
                            array('author.author_id=pub_author.author_id',
                                  'pub_author.pub_id=publication.pub_id',
                                  'author.name like "' . $author_name . '%"'),
                            "pdPubList::authorPubsDbLoad",
                            array('ORDER BY' => 'publication.title ASC'));

        if ($q === false) {
            $this->count = 0;
            return;
        }

        $this->count = $q->pcount;
    }

    /**
     * Retrieves publications for a given author.
     */
    function authorIdPubsDbLoad($db, $author_id, $numToLoad) {
        assert('is_object($db)');
        assert('$author_id != ""');

        if ($numToLoad == 0) return;

        $q = $db->select(array('publication', 'pub_author'),
                         array('publication.pub_id', 'publication.title',
                               'publication.paper', 'publication.abstract',
                               'publication.keywords', 'publication.published',
                               'publication.updated'),
                         array('pub_author.pub_id=publication.pub_id',
                               'pub_author.author_id'
                               => quote_smart($author_id)),
                         "pdPubList::authorIdPubsDbLoad",
                         array('ORDER BY' => 'publication.published ASC'));

        if ($db->numRows($q) == 0) return;

        // if $numToLoad is -1 then we load all publications
        if ($numToLoad == -1)
            $numToLoad = $db->numRows($q);

        $r = $db->fetchObject($q);
        while ($r && ($numToLoad > 0)) {
            $this->list[] = new pdPublication($r);
            $r = $db->fetchObject($q);
            $numToLoad--;
        }

        if (is_array($this->list))
            uasort($this->list, array('pdPublication', 'pubsDateSortDesc'));
    }

    /**
     * Retrieves publications for a given author name.
     */
    function authorNamePubsDbLoad($db, $author_name, $date_start = null,
                                  $date_end = null) {
        assert('is_object($db)');
        assert('$author_name != ""');

        $conds = array('pub_author.pub_id=publication.pub_id',
                       'author.author_id=pub_author.author_id',
                       'author.name LIKE "' . $author_name . '%"');

        if ($date_start != null) {
            if ($date_end == null)
                $date_end = date('Y-m-d');

            $conds[]
                = 'publication.published BETWEEN \'' . $date_start
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

        if ($db->numRows($q) == 0) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = new pdPublication($r);
            $r = $db->fetchObject($q);
        }

        if (is_array($this->list))
            uasort($this->list, array('pdPublication', 'pubsDateSortDesc'));
    }

    /**
     * Retrieves publications for a given category.
     */
    function venuePubsDbLoad($db, $venue_id) {
        assert('is_object($db)');
        assert('$venue_id != ""');

        $q = $db->select('publication', '*',
                         array('venue_id' => $venue_id),
                         "pdPubList::venuePubsDbLoad");

        if ($db->numRows($q) == 0) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = new pdPublication($r);
            $r = $db->fetchObject($q);
        }

        if (is_array($this->list))
            uasort($this->list, array('pdPublication', 'pubsDateSortDesc'));
    }

    /**
     * Retrieves publications for a given category.
     */
    function categoryPubsDbLoad($db, $cat_id) {
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

        if ($db->numRows($q) == 0) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = new pdPublication($r);
            $r = $db->fetchObject($q);
        }

        if (is_array($this->list))
            uasort($this->list, array('pdPublication', 'pubsDateSortDesc'));
    }

    /**
     *
     */
    function authorNumPublications ($db, $author_id) {
        $q = $db->select(array('publication', 'pub_author'),
                         'publication.pub_id',
                         array('publication.pub_id=pub_author.pub_id',
                               'pub_author.author_id' => $author_id),
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'publication.title ASC'));
        return $db->numRows($q);
    }

    function pubTitle($pub_id) {
        assert('count($this->list) > 0');
        foreach ($this->list as $pub) {
            if ($pub->pub_id == $pub_id) return $pub->title;
        }
        return null;
    }

    function arrayPubsDBLoad($db, $pub_ids) {
        assert('is_object($db)');
        assert('is_array($pub_ids)');

        if (count($pub_ids) == 0) return;

        foreach ($pub_ids as $pub_id) {
            if (!is_numeric($pub_id)) continue;

            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id, PD_PUB_DB_LOAD_BASIC);
            if ($result !== false)
                $this->list[$pub_id] = $pub;

        }

        if (is_array($this->list))
            uasort($this->list, array('pdPublication', 'pubsDateSortDesc'));
    }

    function arrayPubsDBLoadByCategory($db, $pub_ids) {
        assert('is_object($db)');
        assert('is_array($pub_ids)');

        if (count($pub_ids) == 0) return;

        foreach ($pub_ids as $pub_id) {
            if (!is_numeric($pub_id)) continue;

            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id,
                                   PD_PUB_DB_LOAD_BASIC
                                   | PD_PUB_DB_LOAD_CATEGORY);
            if ($result !== false) {
                if (is_object($pub->category))
                    switch ($pub->category->category) {
                        case 'In Conference':
                        case 'In Journal':
                        case 'In Workshop':
                            $this->list[$pub->category->category][] = $pub;
                            break;

                        default:
                            $this->list['Other'][] = $pub;
                            break;
                    }
                else
                    $this->list['Other'][] = $pub;
            }
        }

        ksort($this->list);

        foreach ($this->list as $category => $pubs) {
            if (is_array($this->list[$category]))
                uasort($this->list[$category],
                       array('pdPublication', 'pubsDateSortDesc'));
        }

        $this->type = 'category';
    }

    function toPubIdList() {
        $result = array();
        foreach ($this->list as $pub) {
            array_push($resutl, $pub->pub_id);
        }
        return $result;
    }

    function yearsPubsDBLoad($db) {
        assert('is_object($db)');

        $q = $db->select('publication',
                         'distinct year(published) as year', '',
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'published DESC'));

        if ($db->numRows($q) == 0) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = array('year' => $r->year);
            $r = $db->fetchObject($q);
        }

        foreach ($this->list as $key => $item) {
            $q = $db->selectRow('publication', 'count(pub_id) as count',
                             array('year(published)' => $item['year']),
                             "pdPubList::publicationsDbLoad");
            $this->list[$key]['count'] = $q->count;
        }
    }

    function yearPubsDBLoad($db, $year) {
        assert('is_object($db)');

        $q = $db->select('publication', '*',
                         array('year(published)' => $year),
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'published DESC'));

        if ($db->numRows($q) == 0) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = new pdPublication($r);
            $r = $db->fetchObject($q);
        }
    }

    function titlePubsDBLoad($db, $title) {
        assert('is_object($db)');

        $title = str_replace(' ', '%', $title);

        $q = $db->select(array('publication'), '*',
                         array('LOWER(title) LIKE' => 'LOWER(%' . $title . '%)'),
                         "pdPubList::titlePubsDBLoad",
                         array( 'ORDER BY' => 'published ASC'));

        if ($db->numRows($q) == 0) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = new pdPublication($r);
            $r = $db->fetchObject($q);
        }
    }

    function keywordsList($db) {
        assert('is_object($db)');

        $q = $db->select('publication', 'keywords', '',
                         "pdPubList::publicationsDbLoad");

        if ($db->numRows($q) == 0) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $keywords = split('; *', $r->keywords);
            foreach ($keywords as $kw) {
                if ($kw != '')
                    $list[$kw] = true;
            }
            $r = $db->fetchObject($q);
        }

        $this->list = array_keys($list);
    }

    function keywordPubsDBLoad($db, $kw) {
        assert('is_object($db)');

        $q = $db->select(array('publication'), '*',
                         array('keywords like "%' . $kw . '%"'),
                         "pdPubList::publicationsDbLoad",
                         array( 'ORDER BY' => 'title ASC'));

        if ($db->numRows($q) == 0) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = new pdPublication($r);
            $r = $db->fetchObject($q);
        }

        if (is_array($this->list))
            uasort($this->list, array('pdPublication', 'pubsDateSortDesc'));
    }
}

?>
