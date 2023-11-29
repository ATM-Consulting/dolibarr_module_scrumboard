<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2014 ATM Consulting <contact@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *		\version    $Id: skeleton_page.php,v 1.19 2011/07/31 22:21:57 eldy Exp $
 *		\author		Put author name here
 *		\remarks	Put here some comments
 */
// Change this following line to use the correct relative path (../, ../../, etc)
require '../config.php';
// Change this following line to use the correct relative path from htdocs (do not remove DOL_DOCUMENT_ROOT)
dol_include_once('/core/lib/admin.lib.php');
dol_include_once('/core/class/extrafields.class.php');

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if ($code == 'SCRUM_DISPLAY_TASKS_EXTRAFIELDS')
	{
		$TtasksEF = GETPOST('SCRUM_DISPLAY_TASKS_EXTRAFIELDS');
		if (dolibarr_set_const($db, $code, implode(',', $TtasksEF), 'chaine', 0, '', $conf->entity) > 0)
		{
			setEventMessage( $langs->trans('RegisterSuccess') );
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
	}
	else if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{

        setEventMessage( $langs->trans('RegisterSuccess') );
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		setEventMessage( $langs->trans('RegisterSuccess') );
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

llxHeader('','Gestion de scrumboard, à propos','');

// Configuration header
$head = scrumboardAdminPrepareHead();
print dol_get_fiche_head(
	$head,
	'settings',
	$langs->trans("Module104210Name"),
	0,
	"module.svg@scrumboard"
);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre('Scrumboard',$linkback,'setup');

showParameters();

function showParameters() {
	global $db,$conf,$langs,$bc;

	$html=new Form($db);

	$var=false;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="100">'.$langs->trans("Value").'</td>'."\n";

	$newToken = function_exists('newToken')?newToken():$_SESSION['newtoken'];

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("NumberOfWorkingHourInDay").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$newToken.'">';
	print '<input type="hidden" name="action" value="set_SCRUM_DEFAULT_VELOCITY">';
	print '<input type="text" name="SCRUM_DEFAULT_VELOCITY" value="' . getDolGlobalString('SCRUM_DEFAULT_VELOCITY').'" size="3" />&nbsp;';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("AllowCompleteModeBacklog").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('SCRUM_ADD_BACKLOG_REVIEW_COLUMN');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';

	print '<td>'.$langs->trans("EnableFilterOnGlobalView").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('SCRUM_FILTER_BY_USER_ENABLE');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';

	print '<td>'.$langs->trans("showLinkedContactToTask").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('SCRUM_SHOW_LINKED_CONTACT');

	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';

	print '<td>'.$langs->trans("showDescriptionInTask").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('SCRUM_SHOW_DESCRIPTION_IN_TASK');

	$var=!$var;
	print '<tr '.$bc[$var].'>';

	print '<td>'.$langs->trans("showDateInDescription").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('SCRUM_SHOW_DATES_IN_DESCRIPTION');

	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';

	print '<td>'.$langs->trans("SCRUM_ADD_TIMESPENT_ON_PROJECT_DRAFT").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('SCRUM_ADD_TIMESPENT_ON_PROJECT_DRAFT');

	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SCRUM_USE_GLOBAL_BOARD").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('SCRUM_USE_GLOBAL_BOARD');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SCRUM_SHOW_DATES").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('SCRUM_SHOW_DATES');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("GLOBAL_SB_PREFILTERED_ON_USER_RIGHTS").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff('GLOBAL_SB_PREFILTERED_ON_USER_RIGHTS');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SCRUM_DISPLAY_TASKS_EXTRAFIELDS").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	$ef = new ExtraFields($db);
	$labels = $ef->fetch_name_optionals_label('projet_task');
	print '<form method="POST" name="display_EF">';
    print '<input type="hidden" name="token" value="'.$newToken.'">';
    print '<input type="hidden" name="action" value="set_SCRUM_DISPLAY_TASKS_EXTRAFIELDS">';
	print $html->multiselectarray('SCRUM_DISPLAY_TASKS_EXTRAFIELDS', $labels, getDolGlobalString('SCRUM_DISPLAY_TASKS_EXTRAFIELDS') ? explode(',', $conf->global->SCRUM_DISPLAY_TASKS_EXTRAFIELDS):array(), '', '', '', '', '300');
	print '<input class="button" type="submit" value="'.$langs->trans('Save').'">';
	print '</form>';
	print '</td></tr>';



	print '</table>';

}
?>
<br /><br />
<table width="100%" class="noborder">
	<tr class="liste_titre">
		<td>A propos</td>
		<td align="center">&nbsp;</td>
		</tr>
		<tr class="impair">
			<td valign="top">Module développé par </td>
			<td align="center">
				<a href="http://www.atm-consulting.fr/" target="_blank">ATM Consulting</a>
			</td>
		</td>
	</tr>
</table>
<?php

$db->close();
llxFooter();
