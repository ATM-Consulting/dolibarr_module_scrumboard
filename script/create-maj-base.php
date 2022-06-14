<?php
/*
 * script créant et vérifiant que les champs requis s'ajoutent bien
 */
if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);
	require('../config.php');
}


dol_include_once('/scrumboard/class/scrumboard.class.php');

$PDOdb=new TPDOdb;
$o=new ScrumboardColumn;
$o->init_db_by_vars($PDOdb);

$o=new TStory;
$o->init_db_by_vars($PDOdb);

dol_include_once('/scrumboard/script/migration_stories.php');
