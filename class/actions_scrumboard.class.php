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

require_once __DIR__ . '/../backport/v19/core/class/commonhookactions.class.php';

/**
 * Class ActionsScrumboard
 *
 * Hook actions for scrumboard module
 */
class ActionsScrumboard extends scrumboard\RetroCompatCommonHookActions
{
	/**
	 * Overloading the formObjectOptions function
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow to call another hook
	 * @return  int                             0 on success, 1 on error, 2 to replace standard method
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs,$db;

		if (in_array('ordercard', explode(':', $parameters['context']))) {
		}

		return 0;
	}
	/**
	 * Overloading the formEditProductOptions function
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow to call another hook
	 * @return  int                             0 on success, 1 on error, 2 to replace standard method
	 */
	public function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
	{

		if (in_array('invoicecard', explode(':', $parameters['context']))) {
		}

		return 0;
	}
	/**
	 * Overloading the formAddObjectLine function
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow to call another hook
	 * @return  int                             0 on success, 1 on error, 2 to replace standard method
	 */
	public function formAddObjectLine($parameters, &$object, &$action, $hookmanager)
	{

		global $db;

		if (in_array('ordercard', explode(':', $parameters['context'])) || in_array('invoicecard', explode(':', $parameters['context']))) {
		}

		return 0;
	}
	/**
	 * Overloading the printObjectLine function
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow to call another hook
	 * @return  int                             0 on success, 1 on error, 2 to replace standard method
	 */
	public function printObjectLine($parameters, &$object, &$action, $hookmanager)
	{

		global $db;

		if (in_array('ordercard', explode(':', $parameters['context'])) || in_array('invoicecard', explode(':', $parameters['context']))) {
		}

		return 0;
	}
	/**
	 * Overloading the doActions function
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow to call another hook
	 * @return  int                             0 on success, 1 on error, 2 to replace standard method
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{

		global $db, $conf, $user, $langs;

		$TContext = explode(':', $parameters['context']);

		if (in_array('projecttasktime', $TContext) ) {
			if ($action == 'addtimespent' && $user->hasRight('projet', 'lire') && getDolGlobalInt('SCRUM_ADD_TIMESPENT_ON_PROJECT_DRAFT')) {
				$action = 'addtimespent_scrumboard';
				$error=0;
				$timespent_durationhour = (double) GETPOST('timespent_durationhour', 'int');
				$timespent_durationmin = (double) GETPOST('timespent_durationmin', 'int');
				if (empty($timespent_durationhour) && empty($timespent_durationmin)) {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
					$error++;
				}
				if (empty($_POST["userid"])) {
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorUserNotAssignedToTask'), null, 'errors');
					$error++;
				}
				if (! $error) {
					$object = new Task($db);
					$id = GETPOSTISSET('taskid') ? GETPOST('taskid', 'int') : GETPOST('id', 'int');
					$object->fetch($id, GETPOST('ref', 'alpha'));
					if ($object->id > 0) {
						$object->fetch_projet();

						$object->timespent_note = GETPOST('timespent_note');
						$object->progress = GETPOST('progress', 'int');
						$object->timespent_duration = $timespent_durationhour*60*60;	// We store duration in seconds
						$object->timespent_duration+= $timespent_durationmin*60;		// We store duration in seconds
						if (GETPOST("timehour") != '' && GETPOST("timehour") >= 0) {	// If hour was entered
							$object->timespent_date = dol_mktime(GETPOST("timehour"), GETPOST("timemin"), 0, GETPOST("timemonth"), GETPOST("timeday"), GETPOST("timeyear"));
							$object->timespent_withhour = 1;
						} else {
							$object->timespent_date = dol_mktime(12, 0, 0, GETPOST("timemonth"), GETPOST("timeday"), GETPOST("timeyear"));
						}
						$object->timespent_fk_user = GETPOST('userid');
						$result=$object->addTimeSpent($user);
						if ($result >= 0) {
							setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
						} else {
							setEventMessages($langs->trans($object->error), null, 'errors');
							$error++;
						}
					}
				} else {
					$action='';
				}
			}
		}

		return 0;
	}
}
