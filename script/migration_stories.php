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

set_time_limit(0);

require_once __DIR__.'/../lib/scrumboard.lib.php';
require_once __DIR__.'/../class/scrumboard.class.php';

/**
 * Actions
 */
global $db, $conf;

$error = 0;
$TData = getData();
foreach ($TData as $fk_project => $stories) {
	$TStorieLabel = empty($stories) ? array('Sprint 1') : explode(',', $stories);

	$db->begin();
	foreach ($TStorieLabel as $k => $storie_label) {
		$story = new TStory($db);
		$story->fk_projet = $fk_project;
		$story->storie_order = $k + 1;
		$story->label = trim($storie_label);
		if ($story->save() <= 0) {
			$error++;
		}
	}

	if (empty($error)) {
		$db->commit();
	} else {
		$db->rollback();
	}
}

if (empty($error)) {
	$extrafields=new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label('projet');
	if (! empty($extralabels['stories'])) {
		$extrafields->delete('stories', 'projet');
	}
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
	$sql .= ' FROM '.$db->prefix().'projet AS p';
	$sql .= ' LEFT JOIN '.$db->prefix().'projet_extrafields AS pe ON pe.fk_object=p.rowid';
	$sql .= ' WHERE p.rowid NOT IN (SELECT fk_projet FROM '.$db->prefix().'projet_storie)';

	$resql = $db->query($sql);

	$TData = array();
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$TData[$obj->rowid] = $obj->stories;
		}
	}

	return $TData;
}
