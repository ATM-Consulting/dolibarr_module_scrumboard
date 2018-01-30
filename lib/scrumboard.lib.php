<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/scrumboard.lib.php
 *	\ingroup	scrumboard
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function scrumboardAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("scrumboard@scrumboard");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/scrumboard/admin/scrumboard_setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/scrumboard/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@scrumboard:/scrumboard/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@scrumboard:/scrumboard/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'scrumboard');

    return $head;
}

function scrum_getVelocity(&$db, $id_project) {
	global $conf;
	
	$t2week= strtotime('-2weeks');
	
	$projet=new Project($db);
	$projet->fetch($id_project);
	
	if($projet->date_start>$t2week) $t2week = $projet->date_start;
	
	$res=$db->query("SELECT SUM(tt.task_duration) as task_duration 
	FROM ".MAIN_DB_PREFIX."projet_task_time tt LEFT JOIN ".MAIN_DB_PREFIX."projet_task t ON (tt.fk_task=t.rowid)
	WHERE tt.task_date>='".date('Y-m-d', $t2week)."' AND t.fk_projet=".$id_project);
	
	$velocity = 0;
	if($obj=$db->fetch_object($res)) {
		 $velocity = round($obj->task_duration / ((time() - $t2week) / 86400));
	}
	
	if($velocity==0)$velocity = (int)$conf->global->SCRUM_DEFAULT_VELOCITY * 3600;

	return $velocity;	
}

/**
 * Retourne toutes les colonnes actives du scrumboard.
 * S'il existe des tâches liées à des colonnes inexistantes ou inactives, on les lies à la colonne "toDo"
 * @param	int $fk_project
 * @return array
 */
function scrum_getAllColumns($fk_project) {
	global $db;
	$TColumns = array();

	$sql = 'SELECT rowid, label, active, code';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'c_scrum_columns';
	$sql .= ' ORDER BY col_order ASC';

	$resql = $db->query($sql);
	while ($obj = $db->fetch_object($resql)) {
		if(! empty($obj->active)) $TColumns[] = $obj;
	}
	scrum_setTasksColumn($fk_project);

	return $TColumns;
}

/**
 * Rattache les tâches liées aux colonnes non actives à la colonne "toDo"
 * @param int $fk_project
 */
function scrum_setTasksColumn($fk_project) {
	global $db, $langs;
	$show_message = false;

	$sql = 'SELECT rowid, scrum_status';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task';
	$sql .= ' WHERE fk_projet='.$fk_project;

	$resql = $db->query($sql);
	while ($objTask = $db->fetch_object($resql)) {
		// Pour chaque tâche du projet, on vérifie que la colonne existe dans la table correspondante
		$sql = 'SELECT rowid, active';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'c_scrum_columns';
		$sql .= ' WHERE rowid='.$objTask->scrum_status;
		$sql .= ' ORDER BY col_order ASC';

		$res = $db->query($sql);
		if($objCol = $db->fetch_object($res)) {
			// Si la colonne existe mais qu'elle n'est pas active : on change de colonne pour la tâche
			if(empty($objCol->active)) {
				$show_message = true;
				scrum_updateScrumStatus($fk_project, $objTask->scrum_status);
			}
		}
		else {
			// Si la colonne n'existe pas : on change de colonne pour la tâche
			$show_message = true;
			scrum_updateScrumStatus($fk_project, $objTask->scrum_status);
		}
	}
	if($show_message) {
		setEventMessage($langs->trans('ScrumTasksColumnChanged'), 'warnings'	);
	}
}

/**
 * Rattache les tâches identifiées par les paramètres à la colonne "toDo"
 * @param int $fk_project
 * @param int $old_scrum_status		identifiant de la colonne inactive ou inexistante
 */
function scrum_updateScrumStatus($fk_project, $old_scrum_status) {
	global $db;

	$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task';
	$sql .= ' SET scrum_status=1';
	$sql .= ' WHERE fk_projet='.$fk_project;
	$sql .= ' AND scrum_status='.$old_scrum_status;
	$db->query($sql);
}

/**
 * Renvoie l'id de la colonne dont le label correspond à $column_label
 * @param	int $column_label
 * @return int
 */
function scrum_getColumnId($column_label) {
	global $db;

	$sql = 'SELECT rowid, active';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'c_scrum_columns';
	$sql .= ' WHERE lower(label)=\''.$column_label.'\'';

	$resql = $db->query($sql);
	if($obj = $db->fetch_object($resql)) {
		if(! empty($obj->active)) {
			return $obj->rowid;
		}
	}

	return 0;
}

function scrum_getAllStories($fk_project) {
	global $db;

	$sql = 'SELECT storie_order, label, date_start, date_end';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_storie';
	$sql .= " WHERE fk_projet=$fk_project";
	$sql .= ' ORDER BY storie_order ASC';

	$resql = $db->query($sql);

	$TStories = array();
	while($obj = $db->fetch_object($resql)) {
		$TStories[$obj->storie_order] = $obj;
	}

	return $TStories;
}

function scrum_getStorie($fk_project, $storie_k) {
	global $db;

	$sql = 'SELECT label';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_storie';
	$sql .= " WHERE fk_projet=$fk_project";
	$sql .= " AND storie_order=$storie_k";

	$resql = $db->query($sql);

	if($obj = $db->fetch_object($resql)) {
		return $obj->label;
	}
	return '';
}

function scrum_updateStorie($fk_project, $storie_k, $storie_label, $date_start, $date_end) {
	global $db;
	
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_storie';
	$sql .= " SET label='$storie_label',";
	$sql .= ' date_start="'.date('Y-m-d', strtotime(preg_replace('/\//', '-', $date_start))).'",';
	$sql .= ' date_end="'.date('Y-m-d', strtotime(preg_replace('/\//', '-', $date_end))).'"';
	$sql .= " WHERE fk_projet=$fk_project";
	$sql .= " AND storie_order=$storie_k";

	$db->query($sql);
}

function scrum_deleteStorie($fk_project, $storie_k) {
	global $db;

	$sql = 'DELETE';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_storie';
	$sql .= " WHERE fk_projet=$fk_project";
	$sql .= " AND storie_order=$storie_k";

	$db->query($sql);
}

function scrum_isStorieVisible($fk_project, $storie_k) {
	global $db;

	$sql = 'SELECT visible';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_storie';
	$sql .= " WHERE fk_projet=$fk_project";
	$sql .= " AND storie_order=$storie_k";

	$resql = $db->query($sql);

	if($obj = $db->fetch_object($resql)) {
		return ($obj->visible == 1);
	}
	return false;
}