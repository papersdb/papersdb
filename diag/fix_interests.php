<?php

 /**
  * Script that reports statistics for thepublications made by AICML PIs, PDFs,
  * students and staff.
  *
  * @package PapersDB
  */

/** Requries the base class and classes to access the database. */
require_once '../includes/functions.php';
require_once '../includes/pdDb.php';
require_once '../includes/pdAuthor.php';
require_once '../includes/pdAuthInterests.php';
require_once '../includes/pdAuthorList.php';

$db = new pdDb(array('name' => 'pubDB'));
$all_interests = pdAuthInterests::createList($db);
$numeric_interests = array();
foreach ($all_interests as $k => $i) {
    if (is_numeric($i)) $numeric_interests[$k] = $i;
}

if (count($numeric_interests) == 0) exit(0);

//pdDb::debugOn();
//debugVar('n', $numeric_interests);

$q = $db->select(array('author', 'author_interest', 'interest'), 
	array('author.author_id', 'interest.interest_id', 'interest.interest'),
    array('interest.interest_id' => array_keys($numeric_interests),
        'interest.interest_id=author_interest.interest_id',
        'author.author_id=author_interest.author_id'));
    
$authors = array();    
$author_info_list = array();
foreach ($q as $r) {
    if (!isset($authors[$r->author_id])) {
        $authors[$r->author_id] = pdAuthor::newFromDb($db, $r->author_id, 
                pdAuthor::DB_LOAD_BASIC | pdAuthor::DB_LOAD_INTERESTS);
    }
                
    $author_info_list[] = array(
    	'interest_id' => $r->interest_id,
        'new_interest' => array($all_interests[$r->interest_id] 
                                => $all_interests[$all_interests[$r->interest_id]]),
        'author'      => &$authors[$r->author_id]);
}
//debugVar('$author_info_list', $author_info_list);

foreach ($author_info_list as $info) {
    $info['author']->interests = $info['author']->interests + $info['new_interest'];
    unset($info['author']->interests[$info['interest_id']]);
}
//debugVar('$authors', $authors);

foreach ($authors as $author) {
    $author->dbSave($db);
}

debugVar('$numeric_interests', $numeric_interests);
foreach ($numeric_interests as $k => $i) {
    $q = $db->select('author_interest', '*', array('interest_id' => $k));
    if (count($q) == 0) {
        $result = $db->delete('interest', array('interest_id' => $k));
        echo "$result\n";
    }
}

?>
