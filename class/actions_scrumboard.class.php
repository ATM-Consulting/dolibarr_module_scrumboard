<?php

require_once __DIR__ . '/../backport/v19/core/class/commonhookactions.class.php';

class ActionsScrumboard extends scrumboard\RetroCompatCommonHookActions
{
     /** Overloading the doActions function : replacing the parent's function with the one below
      *  @param      parameters  meta datas of the hook (context, etc...)
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
      *  @param      action             current action (if set). Generally create or edit or null
      *  @return       void
      */

    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
      	global $langs,$db;

		if (in_array('ordercard',explode(':',$parameters['context'])))
        {

		}

		return 0;
	}

    function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
    {

    	if (in_array('invoicecard',explode(':',$parameters['context'])))
        {

        }

        return 0;
    }

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {

		global $db;

		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])))
        {

        }

		return 0;
	}

	function printObjectLine ($parameters, &$object, &$action, $hookmanager){

		global $db;

		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])))
        {

        }

		return 0;
	}

	function doActions ($parameters, &$object, &$action, $hookmanager){

		global $db, $conf, $user, $langs;

		$TContext = explode(':',$parameters['context']);

		if (in_array('projecttasktime',$TContext) )
		{
			if ($action == 'addtimespent' && $user->hasRight('projet', 'lire') && getDolGlobalInt('SCRUM_ADD_TIMESPENT_ON_PROJECT_DRAFT'))
			{
				$action = 'addtimespent_scrumboard';
				$error=0;
				$timespent_durationhour = (double) GETPOST('timespent_durationhour','int');
				$timespent_durationmin = (double) GETPOST('timespent_durationmin','int');
				if (empty($timespent_durationhour) && empty($timespent_durationmin))
				{
					setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Duration")), null, 'errors');
					$error++;
				}
				if (empty($_POST["userid"]))
				{
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorUserNotAssignedToTask'), null, 'errors');
					$error++;
				}
				if (! $error)
				{
					$object = new Task($db);
					$id = GETPOSTISSET('taskid') ? GETPOST('taskid','int') : GETPOST('id','int');
					$object->fetch($id, GETPOST('ref','alpha'));
					if ($object->id > 0)
					{
						$object->fetch_projet();

						$object->timespent_note = GETPOST('timespent_note');
						$object->progress = GETPOST('progress', 'int');
						$object->timespent_duration = $timespent_durationhour*60*60;	// We store duration in seconds
						$object->timespent_duration+= $timespent_durationmin*60;		// We store duration in seconds
						if (GETPOST("timehour") != '' && GETPOST("timehour") >= 0)	// If hour was entered
						{
							$object->timespent_date = dol_mktime(GETPOST("timehour"),GETPOST("timemin"),0,GETPOST("timemonth"),GETPOST("timeday"),GETPOST("timeyear"));
							$object->timespent_withhour = 1;
						}
						else
						{
							$object->timespent_date = dol_mktime(12,0,0,GETPOST("timemonth"),GETPOST("timeday"),GETPOST("timeyear"));
						}
						$object->timespent_fk_user = GETPOST('userid');
						$result=$object->addTimeSpent($user);
						if ($result >= 0)
						{
							setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
						}
						else
						{
							setEventMessages($langs->trans($object->error), null, 'errors');
							$error++;
						}
					}
				}
				else
				{
					$action='';
				}


			}
		}

		return 0;
	}



}
