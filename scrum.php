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
	
	llxHeader('', $langs->trans('Tasks') , '','',0,0, array('/scrumboard/script/scrum.js.php'));
	
	$id_projet = (int)GETPOST('id');
	$action = GETPOST('action');
	$storie_k_toEdit = GETPOST('storie_k', 'int');
	$storie_date_start = GETPOST('storie_date_start');
	$storie_date_end = GETPOST('storie_date_end');
//	var_dump($storie_date_start, $storie_date_end);
	$confirm = GETPOST('confirm');
	
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
		scrum_deleteStorie($id_projet, $storie_k_toEdit);
	}
	else if($action == 'save') {
		scrum_updateStorie($id_projet, $storie_k_toEdit, GETPOST('storieName'), $storie_date_start, $storie_date_end);
	}

	$object = new Project($db);
	$object->fetch($id_projet);
	if (method_exists($object, 'fetch_thirdparty')) $object->fetch_thirdparty();
	if (empty($object->societe) && !empty($object->thirdparty)) $object->societe = $object->thirdparty; // Rétrocompatibilité
	if ($object->societe->id > 0)  $result=$object->societe->fetch($object->societe->id);

	if (!empty($id_projet)) $object->fetch_optionals($id_projet);
	
	if($id_projet>0) {
		$head=project_prepare_head($object);
	}
	else{
		$head=array(0=>array('#', $langs->trans("Scrumboard"), 'scrumboard'));
	}
	
	dol_fiche_head($head, 'scrumboard', $langs->trans("Scrumboard"),0,($object->public?'projectpub':'project'));

	$form = new Form($db);
	
	if (!empty($conf->global->SCRUM_FILTER_BY_USER_ENABLE))
	{
		$fk_user = GETPOST('fk_user');
		if ($id_projet == 0 && empty($fk_user)) $fk_user = $user->id; // Si on selectionne vide dans le champ on aura -1
		
		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" id="scrum_filter_by_user">';
		echo '<input name="id" value="'.$id_projet.'" type="hidden" />';
		echo $form->select_dolusers($fk_user, 'fk_user',  1);
		echo '<input type="submit" value="'.$langs->trans('Filter').'" class="butAction" />';
		echo '</form><br /><br />';
		
	}
	
	if($id_projet) {
		
	/*
		 *   Projet synthese pour rappel
		 */
		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/liste.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
		// Define a complementary filter for search of next/prev ref.
        if (! $user->rights->projet->all->lire)
        {
            $objectsListId = $object->getProjectsAuthorizedForUser($user,$mine,0);
            $object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
        }
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

		// Customer
		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">';
		if ($object->societe->id > 0) print $object->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		if ($object->public) print $langs->trans('SharedProject');
		else print $langs->trans('PrivateProject');
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'<span rel="tobelate" date_end="'.$object->date_end.'"></span></td></tr>';
	
		// Statut
		print '<tr><td>'.$langs->trans("CurrentVelocity").'</td><td rel="currentVelocity"></td></tr>';
		
		// Stories
//		print '<tr><td>'.$langs->trans("ProjectStories").'</td><td>'.$object->array_options['options_stories'].'</td></tr>';
		
		if(!empty($conf->global->SCRUM_SHOW_DESCRIPTION_IN_TASK)) {
			// Description mode if conf activ
			print '<tr><td>'.$langs->trans("showDescriptionInTask").'</td>';
			print '<td>';
			if(!empty($_SESSION['scrumboard']['showdesc'][$id_projet])) {
				print '<a href="'.dol_buildpath('scrumboard/scrum.php?id='.$id_projet.'&action=hide_desc',1).'">'.img_picto('test','switch_on.png').'</a>';
			}else{
				print '<a href="'.dol_buildpath('scrumboard/scrum.php?id='.$id_projet.'&action=show_desc',1).'">'.img_picto('test','switch_off.png').'</a>';
			}
			print '</td></tr>';
		}
		
		print "</table>";
	
	}
	else{
		print $langs->trans("CurrentVelocity").' <span rel="currentVelocity"></span>';	
	}
		
//	$TStorie = !empty($object->array_options['options_stories']) ? explode(',', $object->array_options['options_stories']) : array(0=>$langs->trans('Tasks'));
	$TStorie = scrum_getAllStories($id_projet);
	
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
		foreach($TStorie as $k=>$obj) {
			$storie_k = $k;


		?>
			<?php
				if($action == 'edit' && $storie_k == $storie_k_toEdit) {
					print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
					print '<input type="hidden" name="id" value="'.$id_projet.'" />';
					print '<input type="hidden" name="action" value="save" />';
					print '<input type="hidden" name="storie_k" value="'.$storie_k.'" />';
					
					print '<tr>';
					
					print '<td>';
					print '<input type="text" name="storieName" storie-k="'.$storie_k.'" value="'.scrum_getStorie($id_projet, $storie_k).'"/>';
					print '</td>';
					
					print '<td>';
					print $langs->trans('From').' : ';
					print $form->select_date($storie_date_start, 'storie_date_start');
					print '&nbsp;';
					print $langs->trans('to').' : ';
					print $form->select_date($storie_date_end, 'storie_date_end');
					print '</td>';
					
					print '<td colspan="'.($nbColumns-3).'"></td>';
					
					print '<td align="right">';
					print '<input type="submit" name="submit" value="'.$langs->trans('Save').'" />';
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
					print $langs->trans('From').' : '.date('d/m/Y', strtotime($obj->date_start));
					print '&nbsp;'.$langs->trans('to').' : '.date('d/m/Y', strtotime($obj->date_end));
				}
				?>
			</td>
			<td colspan="<?php echo $nbColumns-3; ?>"></td>
			<?php
					print '<td align="right">';
					print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k.'&action=edit">'.img_picto($langs->trans('Modify'), 'edit.png').'</a>';

					print '&nbsp;';

					if($storie_k != 1) {
						print '<a href="javascript:toggle_visibility('.$id_projet.', '.$storie_k.')">';
						if(scrum_isStorieVisible($id_projet, $storie_k)) {
							print img_picto($langs->trans('Hide'), DOL_URL_ROOT.'/theme/md/img/switch_off_old.png', '', true);
						}
						else {
							print img_picto($langs->trans('Show'), DOL_URL_ROOT.'/theme/md/img/switch_on_old.png', '', true);
						}
						print '</a>';
						print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id_projet.'&storie_k='.$storie_k.'&action=confirm_delete">'.img_picto($langs->trans('Delete'), 'delete.png').'</a>';
					}
					print '</td>';
				}
			?>
		</tr>
		<tr story-k="<?php echo $storie_k; ?>" default-k="<?php echo $default_k; ?>" style="<?php if(! scrum_isStorieVisible($id_projet, $storie_k)) echo 'display: none;';?>">
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
						if(!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK)) {
						?>
						<div class="task-comment"><?php echo img_picto('', 'object_comment@scrumboard') ?><span></span></div>
						<?php
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
			
			<span><?php echo $langs->trans('From'); ?> : </span>
			<input type="date" name="add_storie_date_start" size="20" id="add_storie_date_start" />
			
			<span><?php echo $langs->trans('to'); ?> : </span>
			<input type="date" name="add_storie_date_end" size="20" id="add_storie_date_end" />

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
