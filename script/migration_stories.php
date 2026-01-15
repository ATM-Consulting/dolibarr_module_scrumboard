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
foreach ($TData as $fk_project => $stories) {
	if (empty($stories)) {
		$PDOdb->beginTransaction();

		$story = new TStory;

		$story->fk_projet = $fk_project;
		$story->storie_order = 1;
		$story->label = 'Sprint 1';
		$resql = $story->save($PDOdb);

		if ($resql) $PDOdb->commit();
		else {
			$PDOdb->rollBack();
			$error++;
		}
	} else {
		$TStorieLabel = explode(',', $stories);
		$PDOdb->beginTransaction();
		// Sinon, on lui réaffecte ceux qu'il utilisait
		foreach ($TStorieLabel as $k => $storie_label) {
			$story = new TStory;

			$story->fk_projet = $fk_project;
			$story->storie_order = $k+1;
			$story->label = trim($storie_label);
			$resql = $story->save($PDOdb);

			if (! $resql) $error++;
		}

		if (empty($error)) $PDOdb->commit();
		else $PDOdb->rollBack();
	}
}

if (empty($error)) {
	$extrafields=new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label('projet');
	if (! empty($extralabels['stories'])) {
		$extrafields->delete('stories', 'projet');
	}
}

// Ajout d'un index sur le champ "storie_order"
$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'projet_storie ADD INDEX idx_llx_projet_storie_story_order (storie_order)';
$resql = $db->query($sql);
if (! $resql && $db->errno() != 'DB_ERROR_KEY_NAME_ALREADY_EXISTS') {
	$errordb ++;
	$errors[] = $db->lasterror;
}
/**
 * Get data from database
 *
 * @return array  Data array
 */
function getData()
{
	global $db;

	// Vérifie si la colonne "stories" a été supprimée, car la 2e requête dépend de cette colonne
	$extrafields=new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label('projet');
	if (empty($extralabels['stories'])) {
		return array();
	}

	// Sélectionne tous les projets existants qui n'ont pas de sprint de créé dans la table "projet_storie"
	$sql = 'SELECT p.rowid, pe.stories';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet AS p';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_extrafields AS pe ON pe.fk_object=p.rowid';
	$sql .= ' WHERE p.rowid NOT IN (SELECT fk_projet FROM '.MAIN_DB_PREFIX.'projet_storie)';

	$resql = $db->query($sql);

	$TData = array();
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$TData[$obj->rowid] = $obj->stories;
		}
	}

	return $TData;
}
