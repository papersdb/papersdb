<?php ;

//#!/usr/bin/env php

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/pdPubList.php';

$rankings = array('1' => array('AAAI', 'AIJ', 'ALT', 'Bioinformatics',
                               'CCR', 'IJCAI',
                               'ICML', 'IUI', 'JAIR', 'JMLR', 'MLJ',  'NIPS',
                               'NAR', 'UAI', 'UM', 'WWW'),
                  '2' => array('ACL', 'AIIA', 'ANZIIS',
                               'CAI', 'CIBCB', 'ECCV', 'ECML',
                               'FG', 'ICGI', 'ICMLA', 'ICPR', 'ICTAI',
                               'IVCNZ',
                               'JCP', 'PKDD'),
                  '3' => array('AMFG2005', 'CHI2003 Workshop',
                               'IUI-BeyondPersonalization',
                               'ICCV-CVBIA', 'NIPS-BMforNL', 'PSB',
                               'SRL2004',
                               'UBDM', 'VOI-NIPS'),
                  '4' => array('ACB Annual Meeting', 'TSC2007',
                               'MetabolomicsSymposium2006',
                               'Metabolomics Society Meeting',
                               'NYU-CRM'));

$db = dbCreate();

$pubs = new pdPubList($db);

if (count($pubs->list) == 0) {
    echo 'No publications in database';
    $db->close();
    exit;
}

foreach ($pubs->list as $pub) {
    $pub->dbLoad($db, $pub->pub_id);

    foreach ($rankings as $rank => $venues) {
        if (!isset($pub->venue->title)) continue;

        if (in_array($pub->venue->title, $venues)) {
            if ($pub->rank_id != $rank) {
                echo 'pub '. $pub->pub_id . ' old rank: ' . $pub->rank_id
                    . ' new rank: ' . $rank . '<br/>';
                $pub->rank_id = $rank;
                $pub->dbSave($db);
            }
        }
    }

    //echo $pub->getCitationText() . "\n";
}

$db->close();

?>
