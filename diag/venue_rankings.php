<?php

//#!/usr/bin/env php

require_once '../includes/defines.php';
require_once 'includes/functions.php';
require_once 'includes/pdDb.php';
require_once 'includes/pdPubList.php';

$rankings = array(
    '1' => array('AAAI', 'ACL', 'AIJ', 'ALT', 'Bioinformatics',
                 'CCR', 'COLT', 'ComputingSurveys', 'IJCAI',
                 'ICML', 'IUI', 'J.LogicProgramming',
                 'JAIR', 'JMLR', 'KR', 'MLJ',  'NIPS',
                 'NAR', 'PODS', 'UAI', 'UM', 'WWW'),
    '2' => array('AIIA', 'ANZIIS', 'ANZCIIS',
                 'AustralianAI',
                 'CAI', 'CCAI', 'CIBCB', 'ECCV', 'ECML',
                 'FG', 'ICGI', 'ICMLA', 'IEEE-SMC(B)',
                 'ICPR', 'ICRA', 'ICTAI',
                 'IVCNZ',
                 'JCP', 'PKDD',
                 'Proceedings of Third UNB Artificial Intelligence Workshop'),
    '3' => array('AMFG2005', 'CHI2003 Workshop', 'CISGM', 'CLNL',
                 'Conference on Information Sciences and Systems',
                 'Continuum-ICML2003',
                 'IUI-BeyondPersonalization',
                 'ICCV-CVBIA', 'L&PinMP', 'MTNS', 'NIPS-BMforNL',
                 'PSB', 'RTDS', 'SARA',
                 'SRL2004',
                 'UBDM', 'VOI-NIPS'),
    '4' => array('ACB Annual Meeting', 'ISMB',
                 'MetabolomicsSymposium2006',
                 'Metabolomics Society Meeting',
                 'NYU-CRM',
                 'TSC2007'));

$db = new pdDb(array('name' => 'pubDB'));

$pubs = pdPubList::create($db);

if (count($pubs) == 0) {
    echo 'No publications in database';
    $db->close();
    exit;
}

foreach ($pubs as $pub) {
    $pub->dbLoad($db, $pub->pub_id);

    foreach ($rankings as $rank => $venues) {
        if (!isset($pub->venue->title)) continue;

        if (in_array($pub->venue->title, $venues)) {
            if ($pub->rank_id != $rank) {
                echo 'pub '. $pub->pub_id, ' old rank: ', $pub->rank_id, ' new rank: ', $rank, '<br/>';
                $pub->rank_id = $rank;
                $pub->dbSave($db);
            }
        }
    }

    //echo $pub->getCitationText(), "\n";
}

$db->close();

?>
