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
 *	\file       /scrumboard/scrum.php
 *	\ingroup    projet
 *	\brief      Project card
 */


	require('config.php');
	dol_include_once('/scrumboard/lib/scrumboard.lib.php');
	dol_include_once('/scrumboard/class/scrumboard.class.php');
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

    $hookmanager->initHooks(array('scrumboardcard'));

	$TArrayOfCss = array();
	$TArrayOfCssClasses = array();

    $newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];


	if((float) DOL_VERSION == 6.0) {
		$TArrayOfCss[] = '/theme/common/fontawesome/css/font-awesome.css';
	}
	if(!empty($conf->global->SCRUM_SHOW_DATES)) {
		$TArrayOfCssClasses[] = 'withDatesOnTasks';
	}



	if (GETPOST('submitAction') === 'getCSV' && $user->rights->scrumboard->export) {

		$projectId = intval(GETPOST('id', 'int'));
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
		$labelFilter = GETPOST('label', 'alpha');
		$countryFilter = GETPOST('country_id', 'int');
		$stateFilter = GETPOST('state_id', 'int');


		$scrumboardColumn = new ScrumboardColumn;
		$TColumn = $scrumboardColumn->getTColumnOrder();
		$TColumn[] = (object) array('code' => 'unknownColumn', 'label' => $TColumn[0]->label);
		$fk_user = GETPOST('fk_user', 'int');
		$fk_soc = GETPOST('fk_soc', 'int');
		$soc_type = GETPOST('soc_type');

		// CMMCM pour rester iso avec l’écran

		$task = new Task($db);

		// Configure columns for the CSV export
		$selectedColumns = array(
			'ID'          => 'id',
			'Ref'         => 'ref',
			'ScrumStatus' => 'scrum_status',
			'Label'       => 'label',
			'Contact' => function (&$obj) use ($db) {
				$task = new Task($db);
				if ($task->fetch($obj['id']) > 0) {
					$tab = $task->liste_contact(-1, 'internal');
					$contactAndRole=array();
					foreach ($tab as $k=>$v)
					{
						if ($v['source']=='internal')
						{
							$contactAndRole[]=$v['lastname'].' '.$v['firstname']. ' ('.$v['libelle'].')';
						}
					}
					return implode(' - ',$contactAndRole);
				}
				return '';
			},
			'DateStart'   => 'date_start',
			'DateEnd'     => 'date_end',
			'ProjectRef' => function (&$obj) use ($db) {
				$project = new Project($db);
				if ($project->fetch($obj['fk_project']) > 0) return $project->ref;
				return '-';
			},
			'ProjectTitle' => function (&$obj) use ($db) {
				$project = new Project($db);
				if ($project->fetch($obj['fk_project']) > 0) return $project->title;
				return '-';
			},
			'ProjectStatus' => function (&$obj) use ($db) {
				$project = new Project($db);
				if ($project->fetch($obj['fk_project']) > 0) return $project->getLibStatut(0);
				return '-';
			},
			'PlannedWorkload' => function (&$obj) use ($db) {
				return secondsAsHoursMinutes(intval($obj['planned_workload']), false);
			},
			'DurationEffective' => function (&$obj) use ($db) {
				return secondsAsHoursMinutes(intval($obj['duration_effective']), false);
			},
			'ProgressCalculated' => function(&$obj) {
				return round(100 * $obj['duration_effective'] / $obj['planned_workload'], 2) . '%';
			},
			'Progress'     => 'progress',
//			'TimeToBill'   => 'tobill',
//			'TimeBilled'   => 'billed',
			'Description'  => 'description',
			'DateCreation' => 'date_c',
		);
		// Automatically add extrafields in the CSV export
		$extrafields = new ExtraFields($db);
		$extralabels = $extrafields->fetch_name_optionals_label($task->table_element);

		// adapted from Extrafields::showOutputField() (we can't use it directly as it outputs HTML, not plain text)
		if (is_array($extralabels)) {
			foreach ($extralabels as $key => $label) {
				$extrafieldType = $extrafields->attribute_type[$key];
				$extrafieldParam = $extrafields->attribute_param[$key];
//				var_dump($key, $extrafieldType);
				switch ($extrafieldType) {
					case 'date':
						$callback = function (&$obj) use ($key) {
							return dol_print_date($obj['array_options']['options_' . $key]);
						};
						break;
					case 'datetime':
						$callback = function (&$obj) use ($key) {
							return dol_print_date($obj['array_options']['options_' . $key]);
						};
						break;
					case 'boolean':
						$callback = function (&$obj) use ($key, $langs) {
							return !empty($obj['array_options']['options_' . $key]) ? $langs->trans('yes') : $langs->trans('no');
						};
						break;
					case 'mail':case 'url':case 'phone':case 'price':
						$callback = function (&$obj) use ($key) {
							return $obj['array_options']['options_' . $key];
						};
						break;
					case 'select':
						$callback = function (&$obj) use ($key) {
							return $obj['array_options']['options_' . $key];
						};
						break;
					case 'sellist':
						$callback = function (&$obj) use ($key) {
							return $obj['array_options']['options_' . $key];
						};
						break;
					case 'radio':
						$callback = function (&$obj) use ($key) {
							return $obj['array_options']['options_' . $key];
						};
						break;
					case 'checkbox':
						$callback = function (&$obj) use ($key, $extrafieldParam) {
							$TValue = explode(',', $obj['array_options']['options_' . $key]);
							$toprint = array();
							if (is_array($TValue))
							{
								foreach ($TValue as $keyval => $valueval) {
									$toprint[] = $extrafieldParam['options'][$valueval];
								}
							}
							return implode("\n", $toprint);
						};
						break;
					case 'chkbxlst':
						$infoFieldList = explode(':', array_keys($extrafieldParam['options'])[0]);
						// Several field into label (eq table:code|libelle:rowid)
						$field_labels = explode('|', $infoFieldList[1]);
						$callback = function (&$obj) use ($langs, $key, $infoFieldList, $field_labels, $db) {
							$toprint = array();
							$TValue = explode(',', $obj['array_options']['options_' . $key]);
							if (!is_array($TValue)) return '';
							$selectKey = $keyList = 'rowid';
							if (count($infoFieldList) >= 3) {
								$selectKey = $infoFieldList[2];
								$keyList = $infoFieldList[2] . ' as rowid';
							}
							if (is_array($field_labels)) {
								$keyList .= ', ' . implode(', ', $field_labels);
							}
							$sql = 'SELECT ' . $keyList . ' FROM ' . MAIN_DB_PREFIX . $infoFieldList[0];
							if (strpos($infoFieldList[4], 'extra') !== false) {
								$sql .= ' as main';
							}
							$resql = $db->query($sql);
							if (!$resql) {
								return 'error';
							}
							while ($obj = $db->fetch_object($resql)) {
								if (in_array($obj->rowid, $TValue)) {
									if (is_array($field_labels) && count($field_labels) > 1) {
										foreach ($field_labels as $field_toshow) {
											$translabel = '';
											if (!empty($obj->{$field_toshow})) {
												$translabel = $langs->trans($obj->{$field_toshow});
											}
											if ($translabel == $field_toshow) $toprint[] = $obj->{$field_toshow};
											else $toprint[] = $translabel;
										}
									} else {
										$translabel = '';
										if (!empty($obj->{$infoFieldList[1]})) {
											$translabel = $langs->trans($obj->{$infoFieldList[1]});
										}
										if ($translabel == $obj->{$infoFieldList[1]}) $toprint[] = $translabel;
										else $toprint[] = $obj->{$infoFieldList[1]};
									}
								}
							}
							return implode("\n", $toprint);
						};
						break;
					case 'link':
						LIST($classname, $classpath) = explode(':', array_keys($extrafieldParam['options'])[0]);
						if (!empty($classpath)) {
							dol_include_once($classpath);
							if ($classname && class_exists($classname)) {
								$object = new $classname($db);
							}
							$callback = function (&$obj) use ($key, $object) {
								if ($object->fetch($obj['array_options']['options_' . $key]) > 0) return strip_tags($object->getNomUrl(3));
								return 'error';
							};
						} else {
							$callback = function (&$obj) use ($key) {
								return 'Error bad setup of extrafield';
							};
						}
						break;
					case 'text':case 'longtext':
						$callback = function (&$obj) use ($key) {
							return html_entity_decode($obj['array_options']['options_' . $key]);
						};
						break;
					case 'varchar':
						$callback = function (&$obj) use ($key) {return $obj['array_options']['options_' . $key];};
						break;
					case 'password':
						$callback = function (&$obj) use ($key) {return $obj['array_options']['options_' . $key];};
						break;
					default:
						$callback = function (&$obj) use ($key, $extrafields) {
							return strip_tags($extrafields->showOutputField($key,  $obj['array_options']['options_' . $key]));
						};
						break;
				}
				$selectedColumns[$label] = $callback;
			}
		}

		$csvFileHandle = null;
		foreach($TColumn as $column) {
			$sql = getSQLForTasks(
				$db,
				$projectId,
				$column->code,
				$fk_user,
				$fk_soc,
				$soc_type,
				$TDateFilter,
				$search_array_options,
				$task,
				$extrafieldstask,
				$labelFilter,
				$countryFilter,
				$stateFilter);
			$csvFileHandle = _getCSV($sql, $selectedColumns, $column->label, '', $csvFileHandle, (! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)?$conf->global->EXPORT_CSV_SEPARATOR_TO_USE:';'));
		}

		$fileName = stream_get_meta_data($csvFileHandle)['uri'];
		$downloadFileName = 'Project_' . $projectId . '_tasks.csv';

		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename=' . $downloadFileName);
		header('Pragma: no-cache');
		readfile($fileName);
		exit;
	}

	llxHeader('', $langs->trans('Tasks') , '','',0,0, array('/scrumboard/script/scrum.js.php'), $TArrayOfCss, '', join(' ', $TArrayOfCssClasses));

	$ref = GETPOST('ref', 'aZ09');
	$id_projet = (int)GETPOST('id');
	$action = GETPOST('action');
	$storie_k_toEdit = GETPOST('storie_k', 'int');
	$confirm = GETPOST('confirm');
	$storie_date_start = dol_mktime(12, 0, 0, GETPOST('storie_date_startmonth'), GETPOST('storie_date_startday'), GETPOST('storie_date_startyear'));
	$storie_date_end = dol_mktime(12, 0, 0, GETPOST('storie_date_endmonth'), GETPOST('storie_date_endday'), GETPOST('storie_date_endyear'));
	$id_task =  GETPOST('id_task', 'int');
	$userid = GETPOST('userid');

	if($userid <= 0)
	{
		$userid = $user->id;
	}

	$story = new TStory;
	$PDOdb = new TPDOdb;
	// Init new session var if not exist
	if(empty($_SESSION['scrumboard']['showdesc'])) {
		$_SESSION['scrumboard']['showdesc'] = array();
	}

	if($action == 'show_desc') {
		$_SESSION['scrumboard']['showdesc'][$id_projet] = 1;
	}else if ($action == 'hide_desc') {
		unset($_SESSION['scrumboard']['showdesc'][$id_projet]);
	}
	else if($action == "confirm_delete") {
		echo $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k_toEdit, $langs->trans('ConfirmDeleteStorie'), $langs->trans('DeleteStorie'), 'delete_storie', '', 0, 1);
	}
	else if($action == "delete_storie" && $confirm == 'yes') {
		$story->loadStory($id_projet, $storie_k_toEdit);

		$story->delete($PDOdb);
	}
	else if($action == 'save') {
		if($storie_date_start > $storie_date_end) {
			setEventMessage('DateStartAfterDateEnd', 'errors');
		}
		else {
			$story->load($PDOdb, GETPOST('id_story'));
			$story->label = GETPOST('storieName');
			$story->date_start = $storie_date_start;
			$story->date_end = $storie_date_end;

			$story->save($PDOdb);
		}
	}

	$object = new Project($db);
	if ($id_projet > 0 || ! empty($ref))
	{
	    $ret = $object->fetch($id_projet,$ref);	// If we create project, ref may be defined into POST but record does not yet exists into database
	    if ($ret > 0) {
	        $id_projet=$object->id;
	    }
	}
	if (method_exists($object, 'fetch_thirdparty')) $resfetchthirdparty = $object->fetch_thirdparty();
	if($resfetchthirdparty > 0) {
		if (empty($object->societe) && !empty($object->thirdparty)) $object->societe = $object->thirdparty; // Rétrocompatibilité
		if ($object->societe->id > 0) $result = $object->societe->fetch($object->societe->id);
	} else {
		$object->societe = new Societe($db);
	}

	if (!empty($id_projet)) $object->fetch_optionals();

	if($id_projet>0) {

	    // Add new contact
	    if ($action == 'addcontact' && $user->rights->projet->creer)
	    {
	        $contactsofproject=$object->getListContactId('internal');
	        $idfortaskuser=GETPOST('userid','int');
	        $typeForTask = GETPOST('typeForTask','int');
	        $typeForProject = GETPOST('typeForProject','int');
            $id_story = GETPOST('id_story','int');
            $Ttask = array();
            $id_task = GETPOST('id_task','int');


            // si le contact n'est pas dejà affecté au projet, on l'affecte au project
            $result = false;
            if(!empty($userid) && !in_array($userid, $contactsofproject)){
                $result = $object->add_contact($userid, $typeForProject, 'internal');
            }
            elseif(!empty($userid)){
                $result = true;
            }

            if($result){

                if(empty($id_task)){
                    // récupération des taches liées à la story
                    $Ttask = getAllTaskInStory($object->id, $id_story);
                }
                else{
                    $Ttask = array($id_task);
                }


                if(!empty($Ttask)){

                    $taskAddCount = 0;
                    $taskErrorCount = 0;

                    foreach ($Ttask as $task_id)
                    {
                        $taskObject = new Task($db);
                        $result = $taskObject->fetch($task_id);
                        if($result>0){
                            $result = $taskObject->add_contact($userid, $typeForTask, 'internal');
                            if($result>0){
                                $taskAddCount++;
                            }
                            else{
                                $taskErrorCount ++;
                                setEventMessage($taskObject->error,'errors');
                            }
                        }
                        else{
                            $taskErrorCount++;
                            setEventMessage($langs->trans('TaskNotFound'),'errors');
                        }

                    }

                    if($taskAddCount>0){
                        setEventMessage($langs->trans('UsersAddedToTask',$taskAddCount));
                    }

                    if($taskErrorCount>0){
                        setEventMessage($langs->trans('UsersAddedToTaskError',$taskErrorCount),'errors');
                    }

                }

            }

	    }



		$head=project_prepare_head($object);
	}
	else{
		$head=array(0=>array(dol_buildpath('/scrumboard/scrum.php', 1), $langs->trans("Scrumboard"), 'scrumboard'));

		if ($action == 'addcontact' && $user->rights->projet->creer)
		{
			$idfortaskuser=GETPOST('userid','int');
			$typeForTask = GETPOST('typeForTask','int');
			$typeForProject = GETPOST('typeForProject','int');
			$id_story = GETPOST('id_story','int');
			$id_task = GETPOST('id_task','int');

			$taskObject = new Task($db);
			$resulttaskfetch = $taskObject->fetch($id_task);

			$proj = new Project($db);
			if ($taskObject->fk_project) $proj->fetch($taskObject->fk_project);

			$contactsofproject=$proj->getListContactId('internal');

			// si le contact n'est pas dejà affecté au projet, on l'affecte au project
			$result = false;
			if(!empty($userid) && !in_array($userid, $contactsofproject)){
				$result = $proj->add_contact($userid, $typeForProject, 'internal');
			}
			elseif(!empty($userid)){
				$result = true;
			}

			if ($result && !empty($id_task))
			{
				$taskAddCount = 0;
				$taskErrorCount = 0;

				if($resulttaskfetch>0){
					$result = $taskObject->add_contact($userid, $typeForTask, 'internal');
					if($result>0){
						$taskAddCount++;
					}
					else{
						$taskErrorCount ++;
						setEventMessage($taskObject->error,'errors');
					}
				}
				else{
					$taskErrorCount++;
					setEventMessage($langs->trans('TaskNotFound'),'errors');
				}


			}

			if($taskAddCount>0){
				setEventMessage($langs->trans('UsersAddedToTask',$taskAddCount));
			}

			if($taskErrorCount>0){
				setEventMessage($langs->trans('UsersAddedToTaskError',$taskErrorCount),'errors');
			}
		}
	}

	dol_fiche_head($head, 'scrumboard', $langs->trans("Scrumboard"),0,($object->public?'projectpub':'project'));

	$form = new Form($db);
	$formcompany   = new FormCompany($db);

	if($id_projet) {

	/*
		 *   Projet synthese pour rappel
		 */

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref='<div class="refidno">';
        // Title
        $morehtmlref.=$object->title;
        // Thirdparty
        $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ';
        if (isset($object->thirdparty->id) AND  $object->thirdparty->id > 0)
        {
            $morehtmlref .= $object->thirdparty->getNomUrl(1, 'project');
        }
        $morehtmlref.='</div>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		if ($object->public) print $langs->trans('SharedProject');
		else print $langs->trans('PrivateProject');
		print '</td></tr>';

		// Date start - end
		print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
		$start = dol_print_date($object->date_start,'dayhour');
		print ($start?$start:'?');
		$end = dol_print_date($object->date_end,'dayhour');
		print ' - ';
		print ($end?$end:'?');
		if ($object->hasDelay()) print img_warning("Late");
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("CurrentVelocity").'</td><td rel="currentVelocity"></td></tr>';

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border" width="100%">';

		// Description
		print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($object->description);
		print '</td></tr>';

		// Categories
		if($conf->categorie->enabled) {
			print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($object->id,'project',1);
			print "</td></tr>";
		}

		print "</table>";
		print '</div>';
		print '</div>';

		// ---------------------------------------------------------------------
		// ------------------------------ FILTRES ------------------------------
		// ---------------------------------------------------------------------

		print '<div class="fichehalfleft clearboth">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border" width="100%">';

		if(!empty($conf->global->SCRUM_SHOW_DESCRIPTION_IN_TASK)) {
			// Description mode if conf activ
			print '<tr><td>'.$langs->trans("showDescriptionInTask").'</td>';
			print '<td>';
			if(!empty($_SESSION['scrumboard']['showdesc'][$id_projet])) {
				print '<a href="'.dol_buildpath('scrumboard/scrum.php',1).'?id='.$id_projet.'&action=hide_desc">'.img_picto('test','switch_on.png').'</a>';
			}
			else {
				print '<a href="'.dol_buildpath('scrumboard/scrum.php',1).'?id='.$id_projet.'&action=show_desc">'.img_picto('test','switch_off.png').'</a>';
			}
			print '</td></tr>';
		}
		print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" id="scrum_filter_by_user">';
		echo '<input name="id" value="'.$id_projet.'" type="hidden" />';
		echo '<input type="hidden" name="token" value="' . newToken() . '">';

		print '<table class="border" width="100%">';

		_printUserFilter($id_projet, $form);
		_printDateFilter($form);
		_printLabelFilter();
		_printStateFilter($form);
		_printExtrafieldsFilter();

		print '</table>';

		$exportBtn = '<input type="submit" value="' . $langs->trans('ExportCSV') . '" class="ButAction" />';

		echo '<div class="tabsAction">'
			 . '<input type="submit" name="submitAction" value="' . $langs->trans('Filter') . '" class="butAction" />';
		if ($user->rights->scrumboard->export) {
			echo '<button type="submit" name="submitAction" value="getCSV" formtarget="_blank" title="' . $langs->trans('ExportCSVHelp') . '" class="butAction">'
			     . $langs->trans('ExportCSV')
			     . '</button>';
		}

		echo  '</div>';
		echo '</form>';

		print '</div>';
		print '</div>';
	}
	else{
		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" id="scrum_filter_by_user">';
		echo '<input name="id" value="'.$id_projet.'" type="hidden" />';

		print '<table class="border" width="100%">';
		if (empty($conf->global->SCRUMBOARD_HIDE_VELOCITY))
		{
			echo '<tr><td>';
			echo $langs->trans('CurrentVelocity');
			echo '</td><td rel="currentVelocity"></td></tr>';
		}

		_printUserFilter($id_projet, $form);
		_printSocieteFilter($form);
		_printDateFilter($form);
		_printLabelFilter();
		_printStateFilter($form);
		_printExtrafieldsFilter();

		print '</table>';

		echo '<div class="tabsAction"><input type="submit" value="'.$langs->trans('Filter').'" class="butAction" />';
		if ($user->rights->scrumboard->export) {
			echo '<button type="submit" name="submitAction" value="getCSV" formtarget="_blank" title="' . $langs->trans('ExportCSVHelp') . '" class="butAction">'
				 . $langs->trans('ExportCSV')
				 . '</button>';
		}
		echo '</div>';
		echo '</form>';
	}

	$TStorie = $story->getAllStoriesFromProject($id_projet);

	$scrumboardColumn = new ScrumboardColumn;
	$TColumn = $scrumboardColumn->getTColumnOrder();
	$nbColumns = count($TColumn);

?>
<link rel="stylesheet" type="text/css" title="default" href="<?php echo dol_buildpath('/scrumboard/css/scrum.css',1) ?>">

<style type="text/css">

td.projectDrag {
	<?php
	// On calcule la largeur de chaque colonne en fonction du nombre de colonne
	$calculatedWidth = 100 / $nbColumns;
	echo 'width: '.$calculatedWidth.'%';
	?>;
	min-width:100px;
}

</style>

<div class="content">
<?php

/**
 * @param string        $sql              SQL query that filters tasks
 * @param array         $selectedColumns  Assoc array: keys are the column names, values are either empty (in which
 *                                        case the key will be used to get the CSV value) or a function (in which case
 *                                        the value returned by the function, called on the $taskDetails object, will be
 *                                        used as the CSV value).
 * @param string        $scrumboardColumn
 * @param string        $filename
 * @param resource|null $csvFileHandle
 * @return resource|null Null on error, else the CSV file handle to which the function has written.
 */
function _getCSV($sql, $selectedColumns, $scrumboardColumn, $filename, $csvFileHandle=null, $delimiter=';', $enclosure='"', $escapeChar="\\") {
	global $conf, $user, $db, $langs;
	$langs->load('scrumboard@scrumboard');

	if ($csvFileHandle === null) {
		$tmpName = tempnam(sys_get_temp_dir(), 'data');
		$csvFileHandle = fopen($tmpName, 'w');
		$headerRow = array_keys($selectedColumns);
		foreach($headerRow as &$label) $label = $langs->transnoentitiesnoconv($label);
		fputcsv($csvFileHandle, $headerRow, $delimiter, $enclosure, $escapeChar);
	}

	$resql = $db->query($sql);
	if (!$resql) return $csvFileHandle;
	$num_rows = $db->num_rows($resql);
	if ($num_rows === 0) return $csvFileHandle;

	for ($i = 0; $i < $num_rows; $i++) {
		$obj = $db->fetch_object($resql);
		if (!$obj) continue;
		$taskDetails = array_merge(
			getTaskDetailsForScrumboardCard($db, $obj->rowid),
			array('story_k' => $obj->story_k, 'scrum_status' => $scrumboardColumn)
		);
		$values = array();
		foreach ($selectedColumns as $columnName => $accessor) {
			// $accessor is either a function or a property name (string)
			if (is_callable($accessor)) {
				$values[] = $accessor($taskDetails);
			} else {
				$values[] = $taskDetails[$accessor];
			}
		}

		fputcsv(
			$csvFileHandle,
			$values,
			$delimiter,
			$enclosure,
			$escapeChar
		);
	}
	return $csvFileHandle;
}

if($action == 'addressourcetotask' && !empty($id_task)) {

    $taskObject = new Task($db);
    $taskObject->fetch($id_task);
    if (!empty($taskObject->id)) $object->fetch($taskObject->fk_project);

    $ajaxCall = GETPOST('ajaxcall','int');

    print '<hr style="clear:both;" />';
    print '<div id="form-add-ressource-task-'.$id_task.'" style="clear:both; margin: 2em 0;" >';
    print '<form  action="'.dol_buildpath('scrumboard/scrum.php',2).'" method="POST">';
    print '<input type="hidden" name="token" value="'.$newToken.'">';
    print '<input type="hidden" name="id" value="'.$id_projet.'" />';
    print '<input type="hidden" name="action" value="addcontact" />';
    print '<input type="hidden" name="id_story" value="0" />';
    print '<input type="hidden" name="id_task" value="'.$taskObject->id.'" />';

    print '<h4>'.$langs->trans('AddRessource',$taskObject->label).' :</h4>';

    print $form->select_dolusers($userid,'userid',0, null, 0, '', '', '0', 0, 0, '', 0, '', '', 1);

    $contactsofproject=$object->getListContactId('internal');

    print ' &nbsp;&nbsp;&nbsp;'.$langs->trans('ContactProjectType');
    print $formcompany->selectTypeContact($object, '', 'typeForProject','internal','rowid');


    print ' &nbsp;&nbsp;&nbsp;'.$langs->trans('ContactTaskType');
    print $formcompany->selectTypeContact($taskObject, '', 'typeForTask','internal','rowid');

    if(!$ajaxCall) print '<br/>';

    print '<button class="butAction" type="submit" name="submit" value="1"  ><i class="fa fa-user-plus"></i> '.$langs->trans('Add').'</button>';


    if(!$ajaxCall){
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'">'.$langs->trans('Cancel').'</a>';
    }

    print '</form>';
    print '</div>';
    print '<hr style="clear:both;" />';
}

?>




	<table id="scrum" id_projet="<?php echo $id_projet ?>">
		<tr>
			<?php
			foreach($TColumn as $column) {
				echo '<td>'.$langs->trans($column->label);

				if($column->code == 'todo') echo '<span rel="velocityToDo"></span>';
				else if($column->code == 'inprogress') echo '<span rel="velocityInProgress"></span>';

				echo '</td>';
			}
			?>
		</tr>
		<?php
		$default_k = 1;
		$storie_k = 0;
		$currentProject = 0;
		foreach($TStorie as &$obj) {
			$storie_k = $obj->storie_order;

			if(empty($id_projet) && $currentProject != $obj->fk_projet)
			{
				$projet = new Project($db);
				$projet->fetch($obj->fk_projet);
				print '<tr style="display:none"><td colspan="' . $nbColumns . '" style="font-size:140%">'.$projet->getNomUrl(1).'</td></tr>';
				$currentProject = $projet->id;
				$default_k = 1;
			}
		?>
			<?php
    			if($action == 'addressourcetostorie' && $storie_k == $storie_k_toEdit) {
    			    $storyToEdit = new TStory;
    			    $storyToEdit->loadStory($id_projet, $storie_k);


    			    print '<tr>';

    			    print '<td  colspan="'.($nbColumns).'" >';


    			    print '<div id="form-add-ressource-story-'.$storyToEdit->id.'" >';
                    print '<input type="hidden" name="token" value="'.$newToken.'">';
                    print '<form  action="'.$_SERVER['PHP_SELF'].'" method="POST">';
    			    print '<input type="hidden" name="id" value="'.$id_projet.'" />';
    			    print '<input type="hidden" name="action" value="addcontact" />';
    			    print '<input type="hidden" name="id_story" value="'.$storie_k.'" />';

    			    print '<strong>'.$langs->trans('AddRessource',$storyToEdit->label).' :</strong>';

    			    print $form->select_dolusers($userid, 'userid', 0, null, 0, '', '', '0', 0, 0, '', 0, '', '', 1);



    			    $contactsofproject=$object->getListContactId('internal');

    			    print ' &nbsp;&nbsp;&nbsp;'.$langs->trans('ContactProjectType');
    			    print $formcompany->selectTypeContact($object, '', 'typeForProject','internal','rowid');


    			    print ' &nbsp;&nbsp;&nbsp;'.$langs->trans('ContactTaskType');
    			    $taskObject = new Task($db);
    			    print $formcompany->selectTypeContact($taskObject, '', 'typeForTask','internal','rowid');


    			    print '<button class="butAction" type="submit" name="submit" value="1"  ><i class="fa fa-user-plus"></i> '.$langs->trans('Add').'</button>';
    			    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'">'.$langs->trans('Cancel').'</a>';



    			    print '</form>';
    			    print '</td>';


    			    print '<td></td>';

    			    print '</tr>';
    			    print '</div>';
    			}
				elseif($action == 'edit' && $storie_k == $storie_k_toEdit) {
					$storyToEdit = new TStory;
					$storyToEdit->loadStory($id_projet, $storie_k);

					print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
                    print '<input type="hidden" name="token" value="'.$newToken.'">';
                    print '<input type="hidden" name="id" value="'.$id_projet.'" />';
					print '<input type="hidden" name="action" value="save" />';
					print '<input type="hidden" name="storie_k" value="'.$storie_k.'" />';
					print '<input type="hidden" name="id_story" value="'.$storyToEdit->id.'" />';

					print '<tr>';

					print '<td>';
					print '<input type="text" name="storieName" storie-k="'.$storie_k.'" value="'.$storyToEdit->label.'"/>';
					print '</td>';

					print '<td>';
					print $langs->trans('From').' : ';
					print $form->select_date((empty($storyToEdit->date_start) ? -1 : $storyToEdit->date_start), 'storie_date_start');
					print '&nbsp;';
					print $langs->trans('to').' : ';
					print $form->select_date((empty($storyToEdit->date_end) ? -1 : $storyToEdit->date_end), 'storie_date_end');
					print '</td>';

					if($nbColumns > 3) print '<td colspan="'.($nbColumns-3).'"></td>';

					print '<td align="right">';
					print '<input type="submit" name="submit" value="'.$langs->trans('Save').'" class="button" />';
					print '</td>';

					print '</tr>';
					print '</form>';
				}
				else {
			?>
		<tr<?php echo (empty($id_projet) ? ' style="display:none"' : ''); ?>>
			<td class="liste_titre">
				<?php print $obj->label; ?>
			</td>
			<td class="liste_titre">
				<?php
				if(! empty($obj->date_start)) {
					print $langs->trans('From').' : '.date('d/m/Y', $obj->date_start);
					print '&nbsp;'.$langs->trans('to').' : '.date('d/m/Y', $obj->date_end);
				}
				?>
			</td>
			<?php
                    if($nbColumns > 3) print '<td colspan="'.($nbColumns-3).'"></td>';
					print '<td align="right">';

					if($id_projet > 0)
					{
						if(!empty($conf->global->SCRUM_SHOW_LINKED_CONTACT)){
					   		print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k.'&action=addressourcetostorie#form-add-ressource-story-'.$storie_k.'"><i class="fa fa-user-plus"></i></a>';
						}
						print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k.'&action=edit">'.img_picto($langs->trans('Modify'), 'edit.png').'</a>';

						print '&nbsp;';
						if($storie_k != 1) {
							print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k.'&action=confirm_delete">'.img_picto($langs->trans('Delete'), 'delete.png').'</a>';
						}
					}
					print !empty($id_projet)?'<a href="javascript:toggle_visibility('.$id_projet.', '.$storie_k.')">':'<a href="javascript:toggle_visibility('.$currentProject.', '.$storie_k.')">';

					if($obj->visible) {
						$iconClass = 'fa fa-eye-slash fa-lg';
						$iconTitle = $langs->trans('Hide');
					}
					else {
						$iconClass = 'fa fa-eye fa-lg';
						$iconTitle = $langs->trans('Show');
					}
					print !empty($id_projet)?'<i class="'.$iconClass.'" title="'.$iconTitle.'" data-story-k="'.$storie_k.'" data-project-id="'.$id_projet.'"></i>':'<i class="'.$iconClass.'" title="'.$iconTitle.'" data-story-k="'.$storie_k.'" data-project-id="'.$currentProject.'"></i>';

					print '</a>';
					print '</td>';
      ?></tr>
          <?php } ?>
		<tr class="hiddable" project-id="<?php echo ($id_projet > 0 ? $id_projet : $currentProject); ?>" story-k="<?php echo $storie_k; ?>" default-k="<?php echo $default_k; ?>" style="<?php if(! $obj->visible || empty($id_projet)) echo 'display: none;';?>">
			<?php
			foreach($TColumn as $column) {
				echo '<td class="projectDrag droppable" data-code="'.$column->code.'" rel="'.$column->code.'">';

				echo '<ul class="task-list" data-code="'.$column->code.'" data-project-id="'.($id_projet > 0 ? $id_projet : $currentProject).'" data-story-k="'.$storie_k.'" rel="'.$column->code.'" story-k="'.$storie_k.'">';
				echo '</ul>';

				echo '</td>';
			}
			?>
		</tr>

		<?php
		$default_k = 0;
		}
		?>

	</table>
<?php
	/*
	 * Actions
	*/

	if($id_projet > 0)
	{
		print '<div class="tabsAction">';

		if ($user->rights->projet->all->creer || $user->rights->projet->creer)
		{
			if ($object->public || $object->restrictedProjectArea($user,'write') > 0)
			{
				print '<a class="butAction" href="javascript:add_storie_task('.$object->id.');">'.$langs->trans('AddStorieTask').'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddStorieTask').'</a>';
			}
		}

		if( (float)DOL_VERSION > 3.4 ) {
			if ($user->rights->projet->all->creer || $user->rights->projet->creer)
			{
				if ($object->public || $object->restrictedProjectArea($user,'write') > 0)
				{
					print '<a class="butAction" href="javascript:reset_date_task('.$object->id.');">'.$langs->trans('ResetDateTask').'</a>';
				}
			}
		}

		if (($user->rights->projet->all->creer || $user->rights->projet->creer))
		{
			if ($object->public || $object->restrictedProjectArea($user,'write') > 0)
			{
				print '<a class="butAction" href="javascript:create_task('.$object->id.');">'.$langs->trans('AddTask').'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
			}
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NoPermission").'">'.$langs->trans('AddTask').'</a>';
		}

		print '</div>';
	}
?>

<div>
	<span style="background-color:red;">&nbsp;&nbsp;&nbsp;&nbsp;</span> <?php echo $langs->trans('TaskWontfinishInTime'); ?><br />
	<span style="background-color:orange;">&nbsp;&nbsp;&nbsp;&nbsp;</span> <?php echo $langs->trans('TaskMightNotfinishInTime'); ?><br />
	<span style="background-color:#CCCCCC;">&nbsp;&nbsp;&nbsp;&nbsp;</span> <?php echo $langs->trans('BarProgressionHelp'); ?>

</div>


		</div>

		<div style="display:none">

			<ul>
				<li id="task-blank">
					<div class="task-dates"></div>
					<div class="task-card-container">
						<div class="task-content width68p float">
							<div class="task-ref"><?php echo img_picto('', 'object_scrumboard@scrumboard') ?> [<a href="#" rel="ref"> </a>]</div>
							<div class="task-title"><span></span></div>
							<div class="task-desc"><span></span></div>
							<div class="task-extrafields"></div>
						</div>
						<div class="task-actions width32p float">
							<div class="task-times">
								<div class="task-real-time"><?php echo img_picto($langs->trans('SB_realtimealt'), 'object_realtime@scrumboard') ?><span></span></div>
								<div class="task-allowed-time"><?php echo img_picto($langs->trans('SB_allowedtimealt'), 'object_allowedtime@scrumboard') ?><span></span></div>
							</div>
							<div class="task-progress"><?php echo img_picto('', 'object_progress@scrumboard') ?>
								<span>
									<select class="nodisplaybutinprogress">
										<?php
										for($i=5; $i<=95;$i+=5) {
											?><option value="<?php echo $i ?>"><?php echo $i ?>%</option><?php
										}
										?>
									</select>
								</span>
							</div>
							<?php
							// Méthodes sur les commentaires ajoutées en standard depuis la 7.0
							if(!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK) && method_exists('Task', 'fetchComments')) {
							?>
							<div class="task-comment"><?php echo img_picto('', 'object_comment@scrumboard') ?><span></span></div>
							<?php
								}
							?>
							<div class="task-origin"><a title="<?php echo $langs->trans('OriginFile'); ?>"><i style="color: black;" class="fa fa-link fa-lg"></i></a></div>

							<?php
							if(!empty($conf->global->SCRUM_SHOW_LINKED_CONTACT)){
							   print '<div class="task-add-contact" ><a ><i style="color: black;" class="fa fa-user-plus"></i> '.$langs->trans('LinkContact').'</a></div>';
							}
							?>
						</div>
						<div class="clearboth"></div>
						<div class="task-users-affected"></div>
						<div class="progressbaruser"></div>
						<div class="progressbar"></div>
					</div>
				</li>


			<!-- <li id="task-blank">
				<div class="progressbaruser"></div>
				<div class="progressbar"></div>
				<div class="actions">
					<select rel="progress" class="nodisplaybutinprogress">
						<?php
						for($i=5; $i<=95;$i+=5) {
							?><option value="<?php echo $i ?>"><?php echo $i ?>%</option><?php
						}
						?>
					</select>
					<span rel="time"></span>
				</div>
				<?php echo img_picto('', 'object_scrumboard@scrumboard') ?><span rel="project"></span> [<a href="#" rel="ref"> </a>] <span rel="label" class="classfortooltip" title="">label</span>
				<br /><span class="font-small" rel="list_of_user_affected"></span>
			</li> -->
			</ul>

		</div>


		<div id="saisie" style="display:none;"></div>
		<div id="reset-date" title="<?php echo $langs->trans('ResetDate'); ?>" style="display:none;">

			<p><?php echo $langs->trans('ResetDateWithThisVelocity'); ?> : </p>

			<input type="text" name="velocity" size="5" id="current-velocity" value="<?php echo $conf->global->SCRUM_DEFAULT_VELOCITY*3600; ?>" /> <?php echo $langs->trans('HoursPerDay') ?>

		</div>
		<div id="add-storie" title="<?php echo $langs->trans('AddStorie'); ?>" style="display:none;">

			<span><?php echo $langs->trans('AddStorieName'); ?> : </span>
			<input type="hidden" name="add_storie_k" id="add_storie_k" value="<?php $storie_k++; echo $storie_k; ?>" />
			<input type="text" name="storieName" size="20" id="newStorieName" value="<?php echo 'Sprint '.$storie_k; ?>" required="required"/>
			<br />

			<?php

			print '<span>'.$langs->trans('From').' : </span>';
			print $form->select_date(-1, 'add_storie_date_start');

			print '<span>'.$langs->trans('to').' : </span>';
			print $form->select_date(-1, 'add_storie_date_end');

			?>

		</div>

		<script type="text/javascript">
			$(document).ready(function() {
				project_loadTasks(<?php echo $id_projet ?>);
				project_init_change_type(<?php echo $id_projet ?>);
				project_velocity(<?php echo $id_projet ?>);
			});
		</script>

<?php

	llxFooter();



function _printUserFilter($id_projet, $form)
{
	global $conf, $langs, $user;

	if (!empty($conf->global->SCRUM_FILTER_BY_USER_ENABLE))
	{
		echo '<tr><td>';
		echo $langs->trans('UserFilter');
		echo '</td><td>';
		$fk_user = GETPOST('fk_user');
		if (empty($id_projet) && empty($fk_user)) $fk_user = $user->id; // Si on selectionne vide dans le champ on aura -1

		if (empty($id_projet) && empty($user->rights->projet->all->lire) && $conf->global->GLOBAL_SB_PREFILTERED_ON_USER_RIGHTS) // filtrage du scrumboard global sur le user courant si pas le droit de tout voir
		{
			echo "<input type='hidden' name='fk_user' value='".$user->id."'>";
			echo $user->getNomUrl(1);
		}
		else
		{
			echo $form->select_dolusers($fk_user, 'fk_user',  1);
		}

		echo '</td></tr>';
	}
}

/**
 * @param Form $form
 */
function _printDateFilter($form)
{
    global $langs;
    echo  '<tr>'
         .   '<td>' . $langs->trans('FilterDateStartAfter') . '</td>'
         .   '<td>'
         .      $form->select_date(dol_mktime(0, 0, 0, GETPOST('start_date_aftermonth'), GETPOST('start_date_afterday'), GETPOST('start_date_afteryear')), 'start_date_after', 0, 0, 1, '', 1, 1, 1, 0)
         .      '<br/>'
         .      $langs->trans('FilterDateStartBefore')
         .      $form->select_date(dol_mktime(23, 59, 59, GETPOST('start_date_beforemonth'), GETPOST('start_date_beforeday'), GETPOST('start_date_beforeyear')), 'start_date_before', 0, 0, 1, '', 1, 1, 1, 0)
         .   '</td>'
         .'</tr>';
    echo  '<tr>'
        .   '<td>' . $langs->trans('FilterDateEndAfter') . '</td>'
        .   '<td>'
        .      $form->select_date(dol_mktime(0, 0, 0, GETPOST('end_date_aftermonth'), GETPOST('end_date_afterday'), GETPOST('end_date_afteryear')), 'end_date_after', 0, 0, 1, '', 1, 1, 1, 0)
        .      '<br/>'
        .      $langs->trans('FilterDateEndBefore')
        .      $form->select_date(dol_mktime(23, 59, 59, GETPOST('end_date_beforemonth'), GETPOST('end_date_beforeday'), GETPOST('end_date_beforeyear')), 'end_date_before', 0, 0, 1, '', 1, 1, 1, 0)
        .   '</td>'
        .'</tr>';
}

/**
 */
function _printLabelFilter()
{
	global $langs;
	$labelValue = dol_escape_htmltag(GETPOST('label'));
	$labelFilterLabel = '<label for="filter">' . $langs->trans('Label') . '</label>';
	$labelFilterInput = '<input type="text" name="label" id="label_filter" value="'. $labelValue .'" />';
	echo '<tr><td>' . $labelFilterLabel . '</td><td>' . $labelFilterInput . '</td></tr>';
}

/**
 * @param Form $form
 */
function _printStateFilter($form)
{
    global $langs, $conf;

    $formcompany = new FormCompany($form->db);
    if (!empty($conf->global->SOCIETE_DISABLE_STATE)) return;
    dol_include_once('/core/lib/company.lib.php');

    $state_id = dol_escape_htmltag(GETPOST('state_id'));
    $country_id = GETPOST('country_id');
    $country_code = $country_id ? getCountry($country_id, 2, $form->db, $langs) : '';
    $countryFilterInput = $form->select_country($country_id,'country_id', 'onchange="state_filter_on_change()"');
    $stateFilterLabel = '<label for="state">' . $langs->trans('State') . '</label>';
    $countryFilterLabel = '<label for="country">' . $langs->trans('Country') . '</label>';
    $stateFilterInput = $formcompany->select_state($state_id, $country_code, 'state_id');
    echo '<tr><td>' . $countryFilterLabel . '</td><td>' . $countryFilterInput . '</td></tr>';
    echo '<tr><td>' . $stateFilterLabel . '</td><td>' . $stateFilterInput . '</td></tr>';
}

/**
 * @param Form $form
 */
function _printSocieteFilter($form)
{
	global $langs;

	echo '<tr><td>';
	echo $langs->trans('Company');
	echo '</td><td>';
	echo $form->select_company(GETPOST('fk_soc'), 'fk_soc', '', 1);
	echo '&nbsp;&nbsp;&nbsp;';

	$soc_type = GETPOST('soc_type');

	echo '<label for="soc_type_onlycompany">'.$langs->trans('soc_type_onlycompany').'</label>&nbsp;<input type="radio" name="soc_type" value="onlycompany" id="soc_type_onlycompany" '.((empty($soc_type) || $soc_type === 'onlycompany') ? 'checked' : '').'>';
	echo '&nbsp;&nbsp;<label for="soc_type_onlycompany">'.$langs->trans('soc_type_onlychildren').'</label>&nbsp;<input type="radio" name="soc_type" value="onlychildren" id="soc_type_onlychildren" '.(($soc_type === 'onlychildren') ? 'checked' : '').'>';
	echo '&nbsp;&nbsp;<label for="soc_type_onlycompany">'.$langs->trans('soc_type_both').'</label>&nbsp;<input type="radio" name="soc_type" value="both" id="soc_type_both" '.(($soc_type === 'both') ? 'checked' : '').'>';
//	echo $form->select_dolusers($fk_user, 'fk_user',  1);
	echo '</td></tr>';
}

/**
 * @param ExtraFields $extrafieldstask
 */
function _printExtrafieldsFilter()
{
	global $db;

	$task = new Task($db);
	$extrafieldstask = new ExtraFields($db);
	$extrafieldstask->fetch_name_optionals_label($task->table_element);

	$search_array_options = $extrafieldstask->getOptionalsFromPost($task->table_element, '', 'search_');

	if(!empty($extrafieldstask->attributes[$task->table_element]['list'])) {
		foreach ($extrafieldstask->attributes[$task->table_element]['list'] as $key => $list) {
			if ($list > 0) {
				echo '<tr><td>';
				echo $extrafieldstask->attributes[$task->table_element]['label'][$key];
				echo '</td><td>';
				echo $extrafieldstask->showInputField($key, $search_array_options['search_options_' . $key], '', '', 'search_', '', '', $task->table_element);
				echo '</td></tr>';
			}
		}
	}
}
