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

	$TInclude = array('/scrumboard/js/scrum.js.php');
	if(!defined('DISABLE_JQUERY_TIPTIP') && !is_file(DOL_URL_ROOT.'/includes/jquery/plugins/tiptip/tipTip.css')) {
		$TInclude[] = '/ordo/js/jquery.tipTip.minified.js';
	}
	llxHeader('', $langs->trans('Tasks') , '','',0,0, $TInclude);

	$id_projet = (int) GETPOST('id');

	$object = new Project($db);
	$object->fetch($id_projet);
	// Debug en cas de id 0 et non admin
	if(empty($object->id)) $object->id=0;
	$object->fetch_thirdparty();

	if($id_projet>0) {
		$head=project_prepare_head($object);
	}
	else{
		$head=array(0=>array('#', $langs->trans("Scrumboard"), 'scrumboard'));
	}

	dol_fiche_head($head, 'scrumboard', $langs->trans("Scrumboard"),0,($object->public?'projectpub':'project'));

	$form = new Form($db);
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
		if ($object->thirdparty->id > 0) print $object->thirdparty->getNomUrl(1);
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

		print "</table>";

	}
	else{
		print $langs->trans("CurrentVelocity").' <span rel="currentVelocity"></span>';
	}

?>
<link rel="stylesheet" type="text/css" title="default" href="<?php echo dol_buildpath('/scrumboard/css/scrum.css',1) ?>">

		<div class="content">

			<table id="scrum" id_projet="<?php echo $id_projet ?>">
				<tr>
					<!-- <td><?php echo $langs->trans('Ideas'); ?></td></td> -->
					<td><?php echo $langs->trans('toDo'); ?><span rel="velocityToDo"></span></td></td>
					<td><?php echo $langs->trans('inProgress'); ?><span rel="velocityInProgress"></span></td></td>
					<td><?php echo $langs->trans('finish'); ?></td></td>
				</tr>
				<tr>
					<!-- <td class="projectDrag droppable" id="task-idea" rel="idea">
						<ul id="list-task-idea" class="task-list" rel="idea">

						</ul>
					</td> -->
					<td class="projectDrag droppable" id="task-todo" rel="todo">
						<ul id="list-task-todo" class="task-list" rel="todo">

						</ul>
					</td>
					<td class="projectDrag droppable" id="task-inprogress" rel="inprogress">
						<ul id="list-task-inprogress" class="task-list" rel="inprogress">

						</ul>
					</td>
					<td class="projectDrag droppable" id="task-finish" rel="finish">
						<ul id="list-task-finish" class="task-list" rel="finish">

						</ul>
					</td>
				</tr>
			</table>
<?php
	/*
	 * Actions
	*/
	print '<div class="tabsAction">';

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
			</li>
			</ul>

		</div>


		<div id="saisie" style="display:none;"></div>
		<div id="reset-date" title="<?php echo $langs->trans('ResetDate'); ?>" style="display:none;">

			<p><?php echo $langs->trans('ResetDateWithThisVelocity'); ?> : </p>

			<input type="text" name="velocity" size="5" id="current-velocity" value"<?php echo $conf->global->SCRUM_DEFAULT_VELOCITY*3600; ?>" /> <?php echo $langs->trans('HoursPerDay') ?>

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
