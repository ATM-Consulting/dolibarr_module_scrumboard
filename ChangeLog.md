

## NOT RELEASED
- FIX : Quality pass. The board service (script/interface.php) now needs the module and project rights, checks that the user can read or modify the project of each task or sprint, and requires the CSRF token (the JavaScript adds it to its calls). A task can only be changed through its label, progress, status, column and sprint (before, any field of the request was copied on the task). The filters of the board (user, thirdparty, country, state, project) are cast before they reach the SQL query, and the sprint actions of the board page need the right to modify the project. The Abricot framework dependency is removed: the sprint and column tables are created by SQL files, config.php and create-maj-base.php are deleted. On upgrade: reactivate the module. - 2.8.6
- FIX : Deprecated Form::select_date replaced by Form::selectDate on the scrum page (story dates and story filters) - 2.8.5
- FIX : PHP warning "Undefined array key" on the board extrafields filters when a filter has no submitted value - 2.8.4
- FIX : Compat V24 - time spent from a scrumboard tile rejected with "Task is mandatory" (the core timespent form no longer emits the hidden `id` field since v23, the AJAX payload now sends `taskid`) - 2.8.3
- FIX : Shared scrumboard left menu hidden - rename constant SCRUM_USE_GLOBAL_BOARD to SCRUM_USE_SHARED_BOARD ("_GLOBAL" substring was rejected by dol_eval as a forbidden superglobal token, breaking the menu 'enabled' condition on Dolibarr 22+) - 2.8.2

## Release 2.8
- FIX : Compat V24 - add CSRF token on add-resource-to-task link (MAIN_SECURITY_CSRF_WITH_TOKEN=3) - *03/06/2026* - 2.8.1
- NEW : Release V23 - *08/04/2026* - 2.8

## Release 2.7
- FIX : Keep ScrumBoard columns visible when dictionary mode is enabled and no active columns are available on current entity - *17/02/2026* - 2.7.5
- FIX : COMPAT V23 - *08/08/2025* - 2.7.4
- FIX [SP151] : Sentry - Changing run_trigger method to avoid warnings - *17/10/2025* - 2.7.3
- FIX : fatal wrong type on implode method - *08/08/2025* - 2.7.2
- FIX : Compat v21 - *16/12/2024* - 2.7.1
- FIX : Compat v20
  Changed Dolibarr compatibility range to 16 min - 20 max - *25/07/2024* - 2.7.0

## Release 2.6

- FIX :  Ajout d'un condition pour le changement de l'action addtimespent en addtimespent_scrumboard dans le hook doAction pour éviter bug dans le standard- *17/08/2024* - 2.6.2
- FIX :  Si la conf pour le backlog est active, on devrait voir les tâches dans la colonne backlog et pas TODO - *05/06/2024* - 2.6.1
- NEW :  Changed Dolibarr compatibility range to 15 min - 19 max  	- *29/11/2023* - 2.6.0
  	 Changed PHP compatibility range to 7.0 min - 8.2 max

## Release 2.5

- FIX : PHP warning  - 26/07/2023* - 2.5.4
- FIX : Compat v18 - *23/06/2023* - 2.5.3
- FIX : DA023325 - CSRF error on general scrumboard - *27/04/2023* - 2.5.2
- FIX : Compatibilité => ShowInputField() a besoin du paramètre $extrafieldsobjectkey - *28/02/2023* - 2.5.1
- FIX : Compatibilité v16 qui n'avait pas été corrigé  - *05/01/2023* - 2.5.0
    et ajout correctif suite à changement du nom du hook "projecttasktime" pour la configuration qui permet d'ajouter des temps même si le projet est brouillon

## Release 2.4

- FIX : Compatibility PHP8 *05/08/2022* - 2.4.4
- FIX : Compatibility V16 - fix warnings *22/07/2022* - 2.4.4
- FIX : Compatibility V16 - replace Dictionnaries tabname *13/07/2022* - 2.4.3
- FIX : Compatibility V16 - Token - *12/07/2022* - 2.4.2
- FIX : Module icon *13/07/2022* 2.4.1
- NEW : Ajout de la class TechATM pour l'affichage de la page "A propos" *11/05/2022* 2.4.0

## Release 2.3

- FIX : add missing WHERE filter in SQL for unknown task status + missing NOCSRFCHECK - *09/12/2021* - 2.3.6
- FIX : Compatibility V13 - newToken() replaces $_SESSION['newtoken'] and add token renewal - *19/05/2021* - 2.3.5

## Release 1.0

 Initial version
