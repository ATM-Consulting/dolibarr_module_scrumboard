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
$res = @include '../../main.inc.php'; // From htdocs directory
if (!$res) {
	$res = @include '../../../main.inc.php'; // From "custom" directory
}
if (!$res) {
	die('Include of main fails');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
require_once __DIR__.'/../lib/scrumboard.lib.php';

$langs->loadLangs(array('admin', 'scrumboard@scrumboard'));

if (!isModEnabled('scrumboard') || !$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

$formSetup = new FormSetup($db);

$item = $formSetup->newItem('SCRUM_DEFAULT_VELOCITY');
$item->nameText = $langs->trans('NumberOfWorkingHourInDay');
$item->setValueFromPostCallBack = static function () use ($item) {
	$item->fieldValue = (string) max(0, GETPOST('SCRUM_DEFAULT_VELOCITY', 'int'));

	return 1;
};

$yesNo = array(
	'SCRUM_ADD_BACKLOG_REVIEW_COLUMN' => 'AllowCompleteModeBacklog',
	'SCRUM_FILTER_BY_USER_ENABLE' => 'EnableFilterOnGlobalView',
	'SCRUM_SHOW_LINKED_CONTACT' => 'showLinkedContactToTask',
	'SCRUM_SHOW_DESCRIPTION_IN_TASK' => 'showDescriptionInTask',
	'SCRUM_SHOW_DATES_IN_DESCRIPTION' => 'showDateInDescription',
	'SCRUM_ADD_TIMESPENT_ON_PROJECT_DRAFT' => 'SCRUM_ADD_TIMESPENT_ON_PROJECT_DRAFT',
	'SCRUM_USE_SHARED_BOARD' => 'SCRUM_USE_SHARED_BOARD',
	'SCRUM_SHOW_DATES' => 'SCRUM_SHOW_DATES',
	'GLOBAL_SB_PREFILTERED_ON_USER_RIGHTS' => 'GLOBAL_SB_PREFILTERED_ON_USER_RIGHTS',
);
foreach ($yesNo as $code => $label) {
	$item = $formSetup->newItem($code)->setAsYesNo();
	$item->nameText = $langs->trans($label);
	$item->setValueFromPostCallBack = static function () use ($item, $code) {
		$item->fieldValue = getDolGlobalString($code);

		return 1;
	};
}

$extrafields = new ExtraFields($db);
$taskExtrafields = $extrafields->fetch_name_optionals_label('projet_task');
$item = $formSetup->newItem('SCRUM_DISPLAY_TASKS_EXTRAFIELDS');
$item->nameText = $langs->trans('SCRUM_DISPLAY_TASKS_EXTRAFIELDS');
$item->setAsMultiSelect(is_array($taskExtrafields) ? $taskExtrafields : array());

/*
 * Actions
 */
if ($action === 'update') {
	$formSetup->saveConfFromPost();
}

/*
 * View
 */
llxHeader('', $langs->trans('Module104210Name'));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>';
print load_fiche_titre($langs->trans('Module104210Name'), $linkback, 'title_setup');

print dol_get_fiche_head(scrumboardAdminPrepareHead(), 'settings', $langs->trans('Module104210Name'), -1, 'module.svg@scrumboard');

if ($action === 'edit') {
	print $formSetup->generateOutput(true);
} else {
	print $formSetup->generateOutput();
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'">'.$langs->trans('Modify').'</a>';
	print '</div>';
}

print dol_get_fiche_end(-1);

llxFooter();
$db->close();
