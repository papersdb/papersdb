<?php

/**
 * This script is meant to be called in response to an AJAX request.
 * 
 * It returns a listing of all the publication by a specified author. The
 * author is specified in the URL query string.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/htmlUtils.php';
require_once 'includes/pdDb.php';
require_once 'includes/pdAuthor.php';

if (!isset($_GET['author_id'])) {
    exit('script called with invalid arguments');
}

$db = pdDb::newFromParams();
$auth = new pdAuthor();
$auth->dbLoad($db, $_GET['author_id'],
    pdAuthor::DB_LOAD_PUBS_ALL | pdAuthor::DB_LOAD_INTERESTS);
    
if (!is_array($auth->pub_list)) {
    exit('Author with id ' . $_GET['author_id'] 
        . ' does not have any publication entries in the database');
}

echo displayPubList($db, $auth->pub_list);

?>