<?php

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);

require ('../config.php');
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
dol_include_once('scrumboard/lib/scrumboard.lib.php');
dol_include_once('scrumboard/class/scrumboard.class.php');

$hookmanager->initHooks(array('scrumboardinterface'));

$get = GETPOST('get','alpha');
$put = GETPOST('put','alpha');

_put($db, $put);
_get($db, $get);

function _get(&$db, $case) {
	switch ($case) {
		case 'tasks' :
			$task = new Task($db);
			$extrafieldstask = new ExtraFields($db);
			$extrafieldstask->fetch_name_optionals_label($task->table_element);
			$search_array_options = $extrafieldstask->getOptionalsFromPost($task->table_element, '', 'search_');
            $TDateFilter = array(
                dol_mktime(0,   0,  0, GETPOST('start_date_aftermonth'),  GETPOST('start_date_afterday'),  GETPOST('start_date_afteryear')),
                dol_mktime(23, 59, 59, GETPOST('start_date_beforemonth'), GETPOST('start_date_beforeday'), GETPOST('start_date_beforeyear')),
                dol_mktime(0,   0,  0, GETPOST('end_date_aftermonth'),    GETPOST('end_date_afterday'),    GETPOST('end_date_afteryear')),
                dol_mktime(23, 59, 59, GETPOST('end_date_beforemonth'),   GETPOST('end_date_beforeday'),   GETPOST('end_date_beforeyear')),
            );
			$labelFilter = GETPOST('label');
			$countryFilter = GETPOST('country_id');
			$stateFilter = GETPOST('state_id');
			print json_encode(_tasks($db, (int)GETPOST('id_project'), GETPOST('status'), GETPOST('fk_user'), GETPOST('fk_soc'), GETPOST('soc_type'), $TDateFilter, $search_array_options, $task, $extrafieldstask, $labelFilter, $countryFilter, $stateFilter));

			break;
		case 'task' :

			print json_encode(getTaskDetailsForScrumboardCard($db, (int)GETPOST('id')));

			break;

		case 'velocity':

			print json_encode(_velocity($db, (int)GETPOST('id_project')));

			break;
        case 'get_state_selector':
            _print_state_selector($db, GETPOST('preselected_state_id'), GETPOST('country_id'));
	}

}

function _put(&$db, $case) {
	switch ($case) {
		case 'task' :

			print json_encode(getTaskDetailsForScrumboardCard($db, (int)GETPOST('id'), $_REQUEST));

			break;

		case 'sort-task' :
			$TTaskID = GETPOST('TTaskID');
			_sort_task($db, empty($TTaskID) ? array() : $TTaskID);

			break;
		case 'reset-date-task':

			_reset_date_task($db,(int)GETPOST('id_project'), (float)GETPOST('velocity') * 3600);

			break;
		case 'add_new_storie':
			_add_new_storie((int)GETPOST('id_project'), GETPOST('storie_name'));
			break;
		case 'toggle_storie_visibility':
			_toggle_storie_visibility((int)GETPOST('id_project'), (int)GETPOST('storie_order'));
			break;

	}

}

function _velocity(&$db, $id_project) {
global $langs;

	$Tab=array();

	$velocity = scrum_getVelocity($db, $id_project);
	$Tab['velocity'] = $velocity;
	$Tab['current'] = convertSecondToTime($velocity).$langs->trans('HoursPerDay');

	if( (float)DOL_VERSION <= 3.4 ) {
		// ne peut pas gérér la résolution car pas de temps plannifié
	}
	else {
		if($velocity>0 && !empty($id_project)) {

			$time = time();
			$res=$db->query("SELECT SUM(planned_workload-duration_effective) as duration
				FROM ".MAIN_DB_PREFIX."projet_task
				WHERE fk_projet=".$id_project." AND progress>0 AND progress<100");
			if($obj=$db->fetch_object($res)) {
				//time rest in second
				$time_end_inprogress = $time + $obj->duration / $velocity * 86400;
			}

			if($time_end_inprogress<$time)$time_end_inprogress = $time;

			$res=$db->query("SELECT SUM(planned_workload-duration_effective) as duration
				FROM ".MAIN_DB_PREFIX."projet_task
				WHERE fk_projet=".$id_project." AND progress=0");
			if($obj=$db->fetch_object($res)) {
				//time rest in second
				$time_end_todo = $time_end_inprogress + $obj->duration / $velocity * 86400;
			}

			if($time_end_todo<$time)$time_end_todo = $time;

			if($time_end_todo>$time_end_inprogress) $Tab['todo']=', '.$langs->trans('EndedThe').' '.date('d/m/Y', $time_end_todo);
			$Tab['inprogress']=', '.$langs->trans('EndedThe').' '.date('d/m/Y', $time_end_inprogress);


		}



	}

	return $Tab;

}


function _sort_task(&$db, $TTask) {
	global $user;

	foreach($TTask as $rank=>$id) {
		$task=new Task($db);
		$task->fetch($id);
		$task->rang = $rank;
		$task->update($user);
	}

}
function _set_values(&$object, $values) {

	foreach($values as $k=>$v) {

		if(property_exists($object, $k)) {

			$object->{$k} = $v;

		}

	}

}

/**
 * @param DoliDB $db
 * @param int $id_project
 * @param int $status
 * @param int $fk_user
 * @param int $fk_soc
 * @param string $soc_type
 * @param array $TPostExtrafields
 * @param Task $object used by extrafields_list_search_sql.tpl.php
 * @param ExtraFields $extrafieldstask
 * @return array
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
		$ret = array('error' => True, 'message' => $langs->trans('ErrorInvalidSQL', $langs->trans($status)));
		if ($user->admin  && !$dolibarr_main_prod ) {
			// debugging context only for admins in a non-production environment
			$ret['sql'] = $sql;
			$ret['dblasterror'] = $db->lasterror();
		}
		return $ret;
	}

	$TTask = array();

	if($status == 'unknownColumn') {
		$scrumboardColumn = new ScrumboardColumn;
		$PDOdb=new TPDOdb;
		$scrumboardColumn->LoadAllBy($PDOdb, array('entity'=>$conf->entity));
		$defaultColumn = $scrumboardColumn->getDefaultColumn();
	}

	while($obj = $db->fetch_object($res)) {
		if($status == 'unknownColumn') $obj->scrum_status = $defaultColumn;
		$TTask[] = array_merge(getTaskDetailsForScrumboardCard($db, $obj->rowid) , array('status'=>$status,'story_k'=>$obj->story_k,'scrum_status'=>$obj->scrum_status));
	}

	return $TTask;
}

function _add_new_storie($id_project, $storie_name) {
	$story = new TStory;
	$PDOdb = new TPDOdb;

	$storie_order = GETPOST('storie_order', 'int');
	$storie_date_start = dol_mktime(12, 0, 0, GETPOST('add_storie_date_startmonth'), GETPOST('add_storie_date_startday'), GETPOST('add_storie_date_startyear'));
	$storie_date_end = dol_mktime(12, 0, 0, GETPOST('add_storie_date_endmonth'), GETPOST('add_storie_date_endday'), GETPOST('add_storie_date_endyear'));

	if($storie_date_start > $storie_date_end) {
		setEventMessage('DateStartAfterDateEnd', 'errors');
		return;
	}

	$story->label = $storie_name;
	$story->fk_projet = $id_project;
	$story->storie_order = $storie_order;

	if(! empty($storie_date_start)) $story->date_start = $storie_date_start;
	if(! empty($storie_date_end)) $story->date_end = $storie_date_end;

	$story->save($PDOdb);
}

function _toggle_storie_visibility($id_project, $storie_order) {
	$story = new TStory;
	$story->loadStory($id_project, $storie_order);

	$story->toggleVisibility();
}

/**
 * Prints a <select> element whose options only include the states of the provided
 * country.
 * @param $db
 * @param $preselected_state_id
 * @param $country_id
 */
function _print_state_selector($db, $preselected_state_id, $country_id){
    dol_include_once('/core/class/html.formcompany.class.php');
    $formcompany = new FormCompany($db);
    echo $formcompany->select_state($preselected_state_id, $country_id, 'state_id');
}
