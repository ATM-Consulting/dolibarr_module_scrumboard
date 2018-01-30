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

require('config.php');
dol_include_once('/scrumboard/lib/scrumboard.lib.php');

$action = GETPOST('action');
$confirm = GETPOST('confirm');
$step = GETPOST('step', 'int');

/**
 * Actions
 */
if($action == 'migration' && $confirm == 'yes') {
	$TData = unserialize(base64_decode(GETPOST('TData')));

	foreach($TData as $fk_project => $stories) {
		$TStorieLabel = explode(',', $stories);

		foreach($TStorieLabel as $k => $storie_label) {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'projet_storie(fk_projet, storie_order, label)';
			$sql .= ' VALUES('.$fk_project.', '.($k+1).', "'.ltrim($storie_label).'")';

			$db->query($sql);
		}
	}

	$extrafields=new ExtraFields($db);
	$extrafields->delete('stories', 'projet');

	$db->query($sql);

	if(empty($db->errors) && empty($db->error)) {
		setEventMessage('Migration terminée !');
	}
}

/**
 * View
 */
llxHeader('', $langs->trans('Migration') , '','',0,0, array('/scrumboard/script/scrum.js.php'));
$form = new Form($db);

if(empty($step)) {
	$TData = getData();
	$nbRow = count($TData);
	
	print '<h3>Migration stories</h3>';
	
	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="action" value="confirm_migration"/>';
	print '<input type="hidden" name="TData" value="'.base64_encode(serialize($TData)).'"/>';
	print '<input type="hidden" name="step" value="2"/>';

	print '<table class="border" width="100%">';

	print '<tr>';
	print '<td width="10%"><p>'.$nbRow.' ligne à modifier</p></td>';
	print '<td>';
	print '<input type="submit" name="submit" value="'.$langs->trans('Begin').'" class="butAction';

	if($nbRow == 0) print 'Refused" title="Aucune ligne à modifier"';
	else print '"';

	print ' /></td>';
	print '</tr>';

	print '</table>';
	print '</form>';
}

if($action == 'confirm_migration') {
	print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('Migration'), $langs->trans('ScrumBeginMigration'), 'migration', array(array('type' => 'hidden', 'name' => 'TData', 'value' => GETPOST('TData'))), 'no');
}


llxFooter();


function getData() {
	global $db, $dolibarr_main_db_name;

	// Vérifie si la colonne "stories" a été supprimée, car la 2e requête dépend de cette colonne
	$sql = 'SELECT COLUMN_NAME';
	$sql .= ' FROM INFORMATION_SCHEMA.COLUMNS';
	$sql .= ' WHERE TABLE_SCHEMA="'.$dolibarr_main_db_name.'"';
	$sql .= ' AND TABLE_NAME="'.MAIN_DB_PREFIX.'projet_extrafields"';
	$sql .= ' AND COLUMN_NAME="stories"';

	$resql = $db->query($sql);
	if(! $obj = $db->fetch_object($resql)) {
		return array();	// Extrafield déjà supprimé : La colonne stories n'existe plus
	}

	$sql = 'SELECT fk_object, stories';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_extrafields';
	$sql .= ' WHERE stories IS NOT NULL';

	$resql = $db->query($sql);

	$TData = array();
	while ($obj = $db->fetch_object($resql)) {
		$TData[$obj->fk_object] = $obj->stories;
	}

	return $TData;
}