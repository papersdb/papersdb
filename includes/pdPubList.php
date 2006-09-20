<?php ;

// $Id: pdPubList.php,v 1.11 2006/09/20 15:54:19 aicmltec Exp $

/**
 * \file
 *
 * \brief Creates a list of all publications or for an individual author.
 *
 */

require_once 'pdPublication.php';

/**
 *
 * \brief Class for storage and retrieval of publication authors to / from
 * the database.
 */
class pdPubList {
    var $list;

    /**
     * Constructor.
     */
    function pdPubList(&$db, $options = null) {
        assert('is_object($db)');

        if (!isset($options['num_to_load']))
            $options['num_to_load'] = -1;

        if ($options['author_id'] != '') {
            $this->authorPubsDbLoad($db, $options['author_id'],
                                    $options['num_to_load']);
        }
        else if ($options['cat_id'] != '') {
            $this->categoryPubsDbLoad($db, $options['cat_id'],
                                      $options['num_to_load']);
        }
        else if (is_array($options['pub_ids'])){
            $this->arrayPubsDBLoad($db, $options['pub_ids']);
        }
        else {
            $this->allPubsDbLoad($db, $options['sort_by_updated']);
            return;
        }
    }

    /**
     * Retrieves all publications.
     */
    function allPubsDbLoad(&$db, $sortByDesc = false) {
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
        assert('is_array($this->list)');
    }

    /**
     * Retrieves publications for a given author.
     */
    function authorPubsDbLoad(&$db, $author_id, $numToLoad) {
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
                         "pdPubList::authorPubsDbLoad",
                         array('ORDER BY' => 'publication.title ASC'));

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
    }

    /**
     * Retrieves publications for a given category.
     */
    function categoryPubsDbLoad(&$db, $cat_id, $numToLoad) {
        assert('is_object($db)');
        assert('$cat_id != ""');

        if ($numToLoad == 0) return;

        $q = $db->select(array('publication', 'pub_cat'),
                         'publication.pub_id',
                         array('pub_cat.pub_id=publication.pub_id',
                               'pub_cat.cat_id' => $cat_id),
                         "pdPubList::categoryPubsDbLoad");

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
    }


    /**
     *
     */
    function authorNumPublications (&$db, $author_id) {
        $q = $db->select(array('publication', 'pub_author'),
                         'publication.pub_id',
                         array('publication.pub_id=pub_author.pub_id',
                               'pub_author.author_id' => $author_id),
                         "pdAuthor::publicationsDbLoad",
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

    function arrayPubsDBLoad(&$db, &$pub_ids) {
        assert('is_object($db)');
        assert('is_array($pub_ids)');

        if (count($pub_ids) == 0) return;

        foreach ($pub_ids as $pub_id) {
            $q = $db->selectRow('publication', '*', array('pub_id' => $pub_id),
                                "pdPubList::arrayPubsDBLoad",
                                array('ORDER BY' => 'title ASC'));

            if ($q === false) continue;

            $this->list[] = new pdPublication($q);
        }

        uasort($this->list, pubsTitleSort);
    }

    function toPubIdList() {
        $result = array();
        foreach ($this->list as $pub) {
            array_push($resutl, $pub->pub_id);
        }
        return $result;
    }
}

?>
