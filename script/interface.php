<?php
/* Copyright (C) 2025 ATM Consulting
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal

$res = @include '../../main.inc.php'; // From htdocs directory
if (!$res) {
	$res = @include '../../../main.inc.php'; // From "custom" directory
}
if (!$res) {
	die('Include of main fails');
}
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once __DIR__ . '/../lib/scrumboard.lib.php';
require_once __DIR__ . '/../class/scrumboard.class.php';

$hookmanager->initHooks(array('scrumboardinterface'));

/**
 * Answer a refused call as JSON, which is what the script expects.
 *
 * @param  string $message Reason
 * @return void
 */
function _deny(string $message): void
{
	http_response_code(403);
	header('Content-Type: application/json');
	print json_encode(array('error' => true, 'message' => $message));
	exit;
}

if (!isModEnabled('scrumboard') || !$user->hasRight('projet', 'lire')) {
	_deny('Access denied');
}
$postedToken = GETPOST('token', 'alpha');
if ($postedToken === '' || !hash_equals(currentToken(), $postedToken)) {
	_deny('Bad token');
}

$get = GETPOST('get', 'aZ09');
$put = GETPOST('put', 'aZ09');

/**
 * Tell if the user can read a project, and write it when asked.
 *
 * @param  int  $id_project Project ID, 0 for the shared board
 * @param  bool $write      True to check the right to modify the project too
 * @return bool
 */
function _canAccessProject($id_project, $write = false)
{
	global $user;

	if ($write && !$user->hasRight('projet', 'creer')) {
		return false;
	}

	return (int) $id_project <= 0 ? !$write : (bool) checkUserAccessToObject($user, array('projet'), (int) $id_project);
}

/**
 * Tell if the user can modify the project of a task.
 *
 * @param  int $id_task Task ID
 * @return bool
 */
function _canWriteTask($id_task)
{
	global $db;

	$task = new Task($db);

	return $id_task > 0 && $task->fetch($id_task) > 0 && _canAccessProject($task->fk_project, true);
}

// phpcs:disable PEAR.NamingConventions.ValidFunctionName
_put($db, $put);
_get($db, $get);

// phpcs:disable PEAR.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore
/**
 * Get data
 *
 * @param   DoliDB  $db     Database handler
 * @param   string  $case   Case action
 * @return  void
 */
function _get(&$db, $case)
{
	switch ($case) {
		case 'tasks' :
			if (!_canAccessProject((int) GETPOST('id_project', 'int'))) {
				_deny('Access denied');
			}
			$task = new Task($db);
			$extrafieldstask = new ExtraFields($db);
			$extrafieldstask->fetch_name_optionals_label($task->table_element);
			$search_array_options = $extrafieldstask->getOptionalsFromPost($task->table_element, '', 'search_');
			$TDateFilter = array(
				dol_mktime(0,   0,  0, GETPOST('start_date_aftermonth', 'int'),  GETPOST('start_date_afterday', 'int'),  GETPOST('start_date_afteryear', 'int')),
				dol_mktime(23, 59, 59, GETPOST('start_date_beforemonth', 'int'), GETPOST('start_date_beforeday', 'int'), GETPOST('start_date_beforeyear', 'int')),
				dol_mktime(0,   0,  0, GETPOST('end_date_aftermonth', 'int'),    GETPOST('end_date_afterday', 'int'),    GETPOST('end_date_afteryear', 'int')),
				dol_mktime(23, 59, 59, GETPOST('end_date_beforemonth', 'int'),   GETPOST('end_date_beforeday', 'int'),   GETPOST('end_date_beforeyear', 'int')),
			);
			$labelFilter = GETPOST('label', 'alphanohtml');
			$countryFilter = GETPOST('country_id', 'int');
			$stateFilter = GETPOST('state_id', 'int');
			print json_encode(_tasks($db, (int) GETPOST('id_project', 'int'), GETPOST('status', 'aZ09'), GETPOST('fk_user', 'int'), GETPOST('fk_soc', 'int'), GETPOST('soc_type', 'aZ09'), $TDateFilter, $search_array_options, $task, $extrafieldstask, $labelFilter, $countryFilter, $stateFilter));

			break;
		case 'task' :

			$id_task = (int) GETPOST('id', 'int');
			$readTask = new Task($db);
			if ($id_task <= 0 || $readTask->fetch($id_task) <= 0 || !_canAccessProject($readTask->fk_project)) {
				_deny('Access denied');
			}
			print json_encode(getTaskDetailsForScrumboardCard($db, $id_task));

			break;

		case 'velocity':

			if (!_canAccessProject((int) GETPOST('id_project', 'int')) || (int) GETPOST('id_project', 'int') <= 0) {
				_deny('Access denied');
			}
			print json_encode(_velocity($db, (int) GETPOST('id_project', 'int')));

			break;
		case 'get_state_selector':
			_print_state_selector($db, GETPOST('preselected_state_id', 'int'), GETPOST('country_id', 'int'));
	}
}

/**
 * Put data
 *
 * @param   DoliDB  $db     Database handler
 * @param   string  $case   Case action
 * @return  void
 */
function _put(&$db, $case)
{
	switch ($case) {
		case 'task' :

			if (!_canWriteTask((int) GETPOST('id', 'int'))) {
				_deny('Access denied');
			}
			print json_encode(getTaskDetailsForScrumboardCard($db, (int) GETPOST('id', 'int'), array(
				'label' => GETPOST('label', 'alphanohtml'),
				'progress' => GETPOST('progress', 'int'),
				'status' => GETPOST('status', 'aZ09'),
				'story_k' => GETPOST('story_k', 'int'),
				'scrum_status' => GETPOST('scrum_status', 'aZ09'),
			)));

			break;

		case 'sort-task' :
			$TTaskID = array_map('intval', GETPOST('TTaskID', 'array'));
			foreach ($TTaskID as $idToSort) {
				if (!_canWriteTask($idToSort)) {
					_deny('Access denied');
				}
			}
			_sort_task($db, $TTaskID);

			break;
		case 'reset-date-task':

			if (!_canAccessProject((int) GETPOST('id_project', 'int'), true)) {
				_deny('Access denied');
			}
			_reset_date_task($db, (int) GETPOST('id_project', 'int'), (float) price2num(GETPOST('velocity', 'alphanohtml')) * 3600);

			break;
		case 'add_new_storie':
			if (!_canAccessProject((int) GETPOST('id_project', 'int'), true)) {
				_deny('Access denied');
			}
			_add_new_storie((int) GETPOST('id_project', 'int'), GETPOST('storie_name', 'alphanohtml'));
			break;
		case 'toggle_storie_visibility':
			if (!_canAccessProject((int) GETPOST('id_project', 'int'), true)) {
				_deny('Access denied');
			}
			_toggle_storie_visibility((int) GETPOST('id_project', 'int'), (int) GETPOST('storie_order', 'int'));
			break;
	}
}

/**
 * Velocity
 *
 * @param   DoliDB  $db         Database handler
 * @param   int     $id_project Project ID
 * @return  array               Velocity data
 */
function _velocity(&$db, $id_project)
{
	global $langs;

	$Tab=array();

	$velocity = scrum_getVelocity($db, $id_project);
	$Tab['velocity'] = $velocity;
	$Tab['current'] = convertSecondToTime($velocity).$langs->trans('HoursPerDay');

	if ($velocity>0 && !empty($id_project)) {
		$time = time();
		$res=$db->query("SELECT SUM(planned_workload-duration_effective) as duration
          FROM ".$db->prefix()."projet_task
          WHERE fk_projet=".intval($id_project)." AND progress>0 AND progress<100");
		if ($obj=$db->fetch_object($res)) {
			//time rest in second
			$time_end_inprogress = $time + $obj->duration / $velocity * 86400;
		}

		if ($time_end_inprogress<$time)$time_end_inprogress = $time;

		$res=$db->query("SELECT SUM(planned_workload-duration_effective) as duration
          FROM ".$db->prefix()."projet_task
          WHERE fk_projet=".intval($id_project)." AND progress=0");
		if ($obj=$db->fetch_object($res)) {
			//time rest in second
			$time_end_todo = $time_end_inprogress + $obj->duration / $velocity * 86400;
		}

		if ($time_end_todo<$time)$time_end_todo = $time;

		if ($time_end_todo>$time_end_inprogress) $Tab['todo']=', '.$langs->trans('EndedThe').' '.date('d/m/Y', $time_end_todo);
		$Tab['inprogress']=', '.$langs->trans('EndedThe').' '.date('d/m/Y', $time_end_inprogress);
	}
	return $Tab;
}

/**
 * Sort tasks
 *
 * @param   DoliDB  $db     Database handler
 * @param   array   $TTask  Array of tasks
 * @return  void
 */
function _sort_task(&$db, $TTask)
{
	global $user;

	foreach ($TTask as $rank=>$id) {
		$task=new Task($db);
		$task->fetch($id);
		$task->rang = $rank;
		$task->update($user);
	}
}

/**
 * Get tasks
 *
 * @param DoliDB        $db                     Database handler
 * @param int           $id_project             Project ID
 * @param int           $status                 Status
 * @param int           $fk_user                User ID
 * @param int           $fk_soc                 Societe ID
 * @param string        $soc_type               Societe type
 * @param array         $TDateFilters           Date filters
 * @param array         $search_array_options   Options
 * @param Task          $object                 used by extrafields_list_search_sql.tpl.php
 * @param ExtraFields   $extrafieldstask        Extrafields
 * @param string        $label_filter           Label filter
 * @param int           $country_filter         Country filter
 * @param int           $state_filter           State filter
 * @return array                                Array of tasks
 */
function _tasks(&$db, $id_project, $status, $fk_user, $fk_soc, $soc_type, $TDateFilters, $search_array_options, $object, $extrafieldstask, $label_filter, $country_filter, $state_filter)
{
	global $conf, $hookmanager;
	$sql = getSQLForTasks(
		$db,
		$id_project,
		$status,
		$fk_user,
		$fk_soc,
		$soc_type,
		$TDateFilters,
		$search_array_options,
		$object,
		$extrafieldstask,
		$label_filter,
		$country_filter,
		$state_filter
	);

	$res = $db->query($sql);
	if (empty($res)) {
		global $langs, $user, $dolibarr_main_prod;
		$ret = array('error' => true, 'message' => $langs->trans('ErrorInvalidSQL', $langs->trans($status)));
		if ($user->admin  && !$dolibarr_main_prod ) {
			// debugging context only for admins in a non-production environment
			$ret['sql'] = $sql;
			$ret['dblasterror'] = $db->lasterror();
		}
		return $ret;
	}

	$TTask = array();
	if ($status == 'unknownColumn') {
		$scrumboardColumn = new ScrumboardColumn;
		$scrumboardColumn->LoadAllBy($db, array('entity' => $conf->entity));
		$defaultColumn = $scrumboardColumn->getDefaultColumn();
	}

	while ($obj = $db->fetch_object($res)) {
		if ($status == 'unknownColumn') $obj->scrum_status = $defaultColumn;
		$TTask[] = array_merge(getTaskDetailsForScrumboardCard($db, $obj->rowid), array('status'=>$status,'story_k'=>$obj->story_k,'scrum_status'=>$obj->scrum_status));
	}

	return $TTask;
}

/**
 * Add new story
 *
 * @param   int     $id_project     Project ID
 * @param   string  $storie_name    Story name
 * @return  void
 */
function _add_new_storie($id_project, $storie_name)
{
	global $db, $langs;

	$story = new TStory($db);

	$storie_order = GETPOST('storie_order', 'int');
	$storie_date_start = dol_mktime(12, 0, 0, GETPOST('add_storie_date_startmonth', 'int'), GETPOST('add_storie_date_startday', 'int'), GETPOST('add_storie_date_startyear', 'int'));
	$storie_date_end = dol_mktime(12, 0, 0, GETPOST('add_storie_date_endmonth', 'int'), GETPOST('add_storie_date_endday', 'int'), GETPOST('add_storie_date_endyear', 'int'));

	if ($storie_date_start > $storie_date_end) {
		setEventMessage($langs->trans('DateStartAfterDateEnd'), 'errors');
		return;
	}

	$story->label = $storie_name;
	$story->fk_projet = $id_project;
	$story->storie_order = $storie_order;

	if (! empty($storie_date_start)) $story->date_start = $storie_date_start;
	if (! empty($storie_date_end)) $story->date_end = $storie_date_end;

	if ($story->save() < 0) {
		setEventMessages($story->error, null, 'errors');
	}
}

/**
 * Toggle story visibility
 *
 * @param   int     $id_project     Project ID
 * @param   int     $storie_order   Story order
 * @return  void
 */
function _toggle_storie_visibility($id_project, $storie_order)
{
	global $db;

	$story = new TStory($db);
	if ($story->loadStory($id_project, $storie_order) > 0) {
		$story->toggleVisibility();
	}
}

/**
 * Prints a <select> element whose options only include the states of the provided
 * country.
 *
 * @param   DoliDB  $db                     Database handler
 * @param   int     $preselected_state_id   Preselected state
 * @param   int     $country_id             Country ID
 * @return  void
 */
function _print_state_selector($db, $preselected_state_id, $country_id)
{
	dol_include_once('/core/class/html.formcompany.class.php');
	$formcompany = new FormCompany($db);
	echo $formcompany->select_state($preselected_state_id, $country_id, 'state_id');
}
// phpcs:enable
