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
 *	\file		admin/scrumboard_setup.php
 *	\ingroup	scrumboard
 *	\brief		Setup page for module scrumboard
 */
require '../config.php';
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
		if (empty($TtasksEF)){
			$TtasksEF = array();
		}
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

/**
 * Print an on/off setup row for a boolean constant toggled with ajax_constantonoff().
 *
 * @param	string	$labelKey	Translation key used for the row label
 * @param	string	$const		Constant name to toggle
 * @param	bool	$var		Alternating row-color flag (passed by reference and toggled)
 * @return	void
 */
function scrumboardPrintOnOffRow($labelKey, $const, &$var)
{
	global $langs, $bc;

	$var = !$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans($labelKey).'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print ajax_constantonoff($const);
	print '</td></tr>';
}

/**
 * Display the module setup parameters table.
 *
 * @return	void
 */
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

	// Default velocity (free text value)
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

	// Boolean options
	scrumboardPrintOnOffRow("AllowCompleteModeBacklog", 'SCRUM_ADD_BACKLOG_REVIEW_COLUMN', $var);
	scrumboardPrintOnOffRow("EnableFilterOnGlobalView", 'SCRUM_FILTER_BY_USER_ENABLE', $var);
	scrumboardPrintOnOffRow("showLinkedContactToTask", 'SCRUM_SHOW_LINKED_CONTACT', $var);
	scrumboardPrintOnOffRow("showDescriptionInTask", 'SCRUM_SHOW_DESCRIPTION_IN_TASK', $var);
	scrumboardPrintOnOffRow("showDateInDescription", 'SCRUM_SHOW_DATES_IN_DESCRIPTION', $var);
	scrumboardPrintOnOffRow("SCRUM_ADD_TIMESPENT_ON_PROJECT_DRAFT", 'SCRUM_ADD_TIMESPENT_ON_PROJECT_DRAFT', $var);
	scrumboardPrintOnOffRow("SCRUM_USE_SHARED_BOARD", 'SCRUM_USE_SHARED_BOARD', $var);
	scrumboardPrintOnOffRow("SCRUM_SHOW_DATES", 'SCRUM_SHOW_DATES', $var);
	scrumboardPrintOnOffRow("GLOBAL_SB_PREFILTERED_ON_USER_RIGHTS", 'GLOBAL_SB_PREFILTERED_ON_USER_RIGHTS', $var);

	// Task extrafields to display (multiselect)
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
	print $html->multiselectarray('SCRUM_DISPLAY_TASKS_EXTRAFIELDS', $labels, getDolGlobalString('SCRUM_DISPLAY_TASKS_EXTRAFIELDS') ? explode(',', getDolGlobalString('SCRUM_DISPLAY_TASKS_EXTRAFIELDS')) : array(), '', '', '', '', '300');
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
