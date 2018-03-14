<?php
/* Copyright (C) 2014 Alexis Algoud        <support@atm-conuslting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       /scrumboard/migration.php
 *	\ingroup    projet
 *	\brief      Project card
 */

//require('config.php');
set_time_limit(0);

dol_include_once('/scrumboard/lib/scrumboard.lib.php');
dol_include_once('/scrumboard/class/scrumboard.class.php');

/**
 * Actions
 */
global $db, $conf;

$PDOdb = new TPDOdb;
$error = 0;
$TData = getData();
foreach($TData as $fk_project => $stories) {
	if(empty($stories)) {
		$db->begin();

		$story = new TStory;

		$story->fk_projet = $fk_project;
		$story->storie_order = 1;
		$story->label = 'Sprint 1';
		$resql = $story->save($PDOdb);

		if($resql) $db->commit();
		else {
			$db->rollback();
			$error++;
		}
	}
	else {
		$TStorieLabel = explode(',', $stories);
		$db->begin();
		// Sinon, on lui réaffecte ceux qu'il utilisait
		foreach($TStorieLabel as $k => $storie_label) {
			$story = new TStory;

			$story->fk_projet = $fk_project;
			$story->storie_order = $k+1;
			$story->label = trim($storie_label);
			$resql = $story->save($PDOdb);

			if(! $resql) $error++;
		}

		if(empty($error)) $db->commit();
		else $db->rollback();
	}
}

if(empty($error)) {
	$extrafields=new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label('projet');
	if(! empty($extralabels['stories'])) {
		$extrafields->delete('stories', 'projet');
	}
}

// Ajoute un lien element_element entre les tâches et la ligne de propale associée
$taskPrefix = 'TA';

$sql = 'SELECT rowid, ref, fk_projet';
$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task';
$sql .= " WHERE ref LIKE '".$taskPrefix."%'";
$sql .= " AND ref NOT LIKE 'TK%'";	// Ne pas prendre en compte les tâches créées depuis le scrumboard
$sql .= " AND rowid NOT IN (SELECT fk_target FROM ".MAIN_DB_PREFIX."element_element WHERE targettype='project_task')";	// On ne prend pas la tâche si elle possède déjà un lien

$resql = $db->query($sql);
if($resql) {
	while($obj = $db->fetch_object($resql)) {
		$fk_line = substr($obj->ref, strlen($taskPrefix));	// Récupère l'identifiant de la ligne de propale en omettant le préfixe des tâches : 'TA125' ==> '125'

		$task = new Task($db);
		$task->fetch($obj->rowid);
		$task->fetchObjectLinked();

		if($taskPrefix == 'TA') $sourceType = 'propaldet';
		if(! empty($sourceType) && empty($task->linkedObjectsIds[$sourceType])) $task->add_object_linked($sourceType, $fk_line);
	}
}

function getData() {
	global $db;

	// Vérifie si la colonne "stories" a été supprimée, car la 2e requête dépend de cette colonne
	$extrafields=new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label('projet');
	if(empty($extralabels['stories'])) {
		return array();
	}

	// Sélectionne tous les projets existants qui n'ont pas de sprint de créé dans la table "projet_storie"
	$sql = 'SELECT p.rowid, pe.stories';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet AS p';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_extrafields AS pe ON pe.fk_object=p.rowid';
	$sql .= ' WHERE p.rowid NOT IN (SELECT fk_projet FROM '.MAIN_DB_PREFIX.'projet_storie)';

	$resql = $db->query($sql);

	$TData = array();
	if($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$TData[$obj->rowid] = $obj->stories;
		}
	}

	return $TData;
}