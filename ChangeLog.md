

## NOT RELEASED



## Release 2.7
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


