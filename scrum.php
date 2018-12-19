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
	
	$TArrayOfCss = array();

	if((float) DOL_VERSION == 6.0) {
		$TArrayOfCss[] = '/theme/common/fontawesome/css/font-awesome.css';
	}

	llxHeader('', $langs->trans('Tasks') , '','',0,0, array('/scrumboard/script/scrum.js.php'), $TArrayOfCss);
	
	$ref = GETPOST('ref', 'aZ09');
	$id_projet = (int)GETPOST('id');
	$action = GETPOST('action');
	$storie_k_toEdit = GETPOST('storie_k', 'int');
	$confirm = GETPOST('confirm');
	$storie_date_start = dol_mktime(12, 0, 0, GETPOST('storie_date_startmonth'), GETPOST('storie_date_startday'), GETPOST('storie_date_startyear'));
	$storie_date_end = dol_mktime(12, 0, 0, GETPOST('storie_date_endmonth'), GETPOST('storie_date_endday'), GETPOST('storie_date_endyear'));
	$id_task =  GETPOST('id_task', 'int');
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
	if (method_exists($object, 'fetch_thirdparty')) $object->fetch_thirdparty();
	if (empty($object->societe) && !empty($object->thirdparty)) $object->societe = $object->thirdparty; // Rétrocompatibilité
	if ($object->societe->id > 0)  $result=$object->societe->fetch($object->societe->id);

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
            if(!empty($idfortaskuser) && !in_array($idfortaskuser, $contactsofproject)){
                $result = $object->add_contact($idfortaskuser, $typeForProject, 'internal');
            }
            elseif(!empty($idfortaskuser)){
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
                            $result = $taskObject->add_contact($idfortaskuser, $typeForTask, 'internal');
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
		$head=array(0=>array('#', $langs->trans("Scrumboard"), 'scrumboard'));
	}
	
	dol_fiche_head($head, 'scrumboard', $langs->trans("Scrumboard"),0,($object->public?'projectpub':'project'));

	$form = new Form($db);
	$formcompany   = new FormCompany($db);
	
	if($id_projet) {
		
	/*
		 *   Projet synthese pour rappel
		 */

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

		$morehtmlref='<div class="refidno">';
        // Title
        $morehtmlref.=$object->title;
        // Thirdparty
        $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ';
        if ($object->thirdparty->id > 0)
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
		print '<table class="border" width="100%">';

		if (!empty($conf->global->SCRUM_FILTER_BY_USER_ENABLE))
		{
			echo '<tr><td>';
			echo $langs->trans('UserFilter');
			echo '</td><td>';
			$fk_user = GETPOST('fk_user');
			if ($id_projet == 0 && empty($fk_user)) $fk_user = $user->id; // Si on selectionne vide dans le champ on aura -1

			echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" id="scrum_filter_by_user">';
			echo '<input name="id" value="'.$id_projet.'" type="hidden" />';
			echo $form->select_dolusers($fk_user, 'fk_user',  1);
			echo '<input type="submit" value="'.$langs->trans('Filter').'" class="butAction" />';
			echo '</form>';
			echo '</td></tr>';
		}

		print '</table>';
		print '</div>';
		print '</div>';
	
	}
	else{
		print $langs->trans("CurrentVelocity").' <span rel="currentVelocity"></span>';	
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

if($action == 'addressourcetotask' && !empty($id_task)) {
    
    $taskObject = new Task($db);
    $taskObject->fetch($id_task);
    $ajaxCall = GETPOST('ajaxcall','int');
    
    print '<hr style="clear:both;" />';
    print '<div id="form-add-ressource-task-'.$id_task.'" style="clear:both; margin: 2em 0;" >';
    print '<form  action="'.dol_buildpath('scrumboard/scrum.php',2).'" method="POST">';
    print '<input type="hidden" name="id" value="'.$id_projet.'" />';
    print '<input type="hidden" name="action" value="addcontact" />';
    print '<input type="hidden" name="id_story" value="0" />';
    print '<input type="hidden" name="id_task" value="'.$taskObject->id.'" />';
    
    print '<h4>'.$langs->trans('AddRessource',$taskObject->label).' :</h4>';
    
    print $form->select_dolusers($selected,'userid',0);
    
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
		foreach($TStorie as &$obj) {
			$storie_k = $obj->storie_order;

		?>
			<?php
    			if($action == 'addressourcetostorie' && $storie_k == $storie_k_toEdit) {
    			    $storyToEdit = new TStory;
    			    $storyToEdit->loadStory($id_projet, $storie_k);
    			    
    			    
    			    print '<tr>';
    			    
    			    print '<td  colspan="'.($nbColumns).'" >';
    			    
    			    
    			    print '<div id="form-add-ressource-story-'.$storyToEdit->id.'" >';
    			    print '<form  action="'.$_SERVER['PHP_SELF'].'" method="POST">';
    			    print '<input type="hidden" name="id" value="'.$id_projet.'" />';
    			    print '<input type="hidden" name="action" value="addcontact" />';
    			    print '<input type="hidden" name="id_story" value="'.$storie_k.'" />';
    			    
    			    print '<strong>'.$langs->trans('AddRessource',$storyToEdit->label).' :</strong>';
    			   
    			    print $form->select_dolusers($selected,'userid',0);
    			    
    			    
    			    
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
		<tr>
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
					
					if(!empty($conf->global->SCRUM_SHOW_LINKED_CONTACT)){
					   print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k.'&action=addressourcetostorie#form-add-ressource-story-'.$storie_k.'"><i class="fa fa-user-plus"></i></a>';
					}
					
					print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k.'&action=edit">'.img_picto($langs->trans('Modify'), 'edit.png').'</a>';

					print '&nbsp;';
					if($storie_k != 1) {
						print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k.'&action=confirm_delete">'.img_picto($langs->trans('Delete'), 'delete.png').'</a>';
					}

					print '<a href="javascript:toggle_visibility('.$id_projet.', '.$storie_k.')">';

					if($obj->visible) {
						$iconClass = 'fa fa-eye-slash fa-lg';
						$iconTitle = $langs->trans('Hide');
					}
					else {
						$iconClass = 'fa fa-eye fa-lg';
						$iconTitle = $langs->trans('Show');
					}
					print '<i class="'.$iconClass.'" title="'.$iconTitle.'" data-story-k="'.$storie_k.'"></i>';

					print '</a>';
					print '</td>';
      ?></tr>
          <?php } ?>
		<tr class="hiddable" story-k="<?php echo $storie_k; ?>" default-k="<?php echo $default_k; ?>" style="<?php if(! $obj->visible) echo 'display: none;';?>">
			<?php
			foreach($TColumn as $column) {
				echo '<td class="projectDrag droppable" data-code="'.$column->code.'" rel="'.$column->code.'">';

				echo '<ul class="task-list" data-code="'.$column->code.'" data-story-k="'.$storie_k.'" rel="'.$column->code.'" story-k="'.$storie_k.'">';
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

	if (($user->rights->projet->all->creer || $user->rights->projet->creer) && $id_projet)
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
	elseif( $id_projet)
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NoPermission").'">'.$langs->trans('AddTask').'</a>';
	}

	print '</div>';
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
					<div class="task-content width68p float">
						<div class="task-ref"><?php echo img_picto('', 'object_scrumboard@scrumboard') ?> [<a href="#" rel="ref"> </a>]</div>
						<div class="task-title"><span></span></div>
						<div class="task-desc"><span></span></div>
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
