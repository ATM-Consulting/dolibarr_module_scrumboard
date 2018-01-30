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
	// Vérifie le type de donnée de la colonne scrum_status
	$sql = 'SELECT DATA_TYPE as type';
	$sql .= ' FROM INFORMATION_SCHEMA.COLUMNS IC';
	$sql .= ' WHERE TABLE_SCHEMA=\''.$dolibarr_main_db_name.'\'';
	$sql .= ' AND TABLE_NAME = \''.MAIN_DB_PREFIX.'projet_task\'';
	$sql .= ' AND COLUMN_NAME = \'scrum_status\'';

	$res = $db->query($sql);
	if($obj = $db->fetch_object($res)) {
		if($obj->type == 'varchar') {
			$db->query('ALTER TABLE '.MAIN_DB_PREFIX.'projet_task DROP scrum_status');
			$db->query('ALTER TABLE '.MAIN_DB_PREFIX.'projet_task ADD scrum_status INTEGER NOT NULL DEFAULT 1');
		}
	}
	$TData = unserialize(base64_decode(GETPOST('TData')));

	foreach($TData as $task) {
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task';
		$sql .= ' SET scrum_status=\''.$task['scrum_status'].'\'';
		$sql .= ' WHERE rowid='.$task['rowid'];

		$db->query($sql);
	}

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


function getData($returnNbRow = false) {
	global $db;

	$sql = 'SELECT rowid, scrum_status';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task';
	$sql .= ' WHERE scrum_status <> \'\'';

	$resql = $db->query($sql);

	// On remplace la valeur de l'ancien scrum_status par l'identifiant de la bonne colonne
	$TData = array();
	while ($obj = $db->fetch_object($resql)) {
		if(((int) $obj->scrum_status) != 0) continue;	// Si le scrum_status est déjà un entier : on ne fait rien

		if(empty($obj->scrum_status)) $fk_scrum_status = 1;
		else $fk_scrum_status = scrum_getColumnId($obj->scrum_status);

		$TData[] = array('rowid' => $obj->rowid, 'scrum_status' => $fk_scrum_status);
	}
	if($returnNbRow) return count($TData);

	return $TData;
}