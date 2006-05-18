<?php ;

// $Id: pdPubList.php,v 1.1 2006/05/18 21:57:45 aicmltec Exp $

/**
 * \file
 *
 * \brief Creates a list of all publications or for an individual author.
 *
 */

require_once('pdPublication.php');

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
    function pdPubList(&$db, $author_id = '', $numToLoad = -1) {
        assert('is_object($db)');

        if ($author_id == '') {
            $this->allPubsDbLoad($db);
        }
        else {
            $this->authorPubsDbLoad($db, $author_id, $numToLoad);
        }
    }

    /**
     * Retrieves all publications.
     */
    function allPubsDbLoad(&$db) {
        assert('is_object($db)');

        $q = $db->select(array('publication'), '*', '',
                         "pdPubList::allPubsDbLoad",
                         array('ORDER BY' => 'title ASC'));
        $r = $db->fetchObject($q);
        while ($r) {
            $pub = new pdPublication($r);
            $this->list[] = $pub;
            $r = $db->fetchObject($q);
        }
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
}

?>
