#!/usr/bin/env php

<?php

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/pdDb.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdPubList.php';

$db = new pdDb(array('name' => 'pubDB'));

$authors = pdAuthorList::create($db);

if (count($authors) == 0) {
    echo 'No authors in database';
    $db->close();
    exit;
}

foreach ($authors as $auth_id => $name) {
    $author = new pdAuthor();
    $author->dbLoad($db, $auth_id);

    foreach ($author->pub_list as $pub) {
        $pub->dbLoad($db, $pub->pub_id);
        $auth_names = array();

        foreach ($pub->authors as $other_auths) {
            $auth_names[] = $other_auths->name;
        }

        echo $author->name, "\t", $pub->title, "\t";
        echo implode("\t", array_diff($auth_names, array($author->name)));
        echo "\n";
    }
}

$db->close();

?>
