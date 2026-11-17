# CLAUDE.md — module scrumboard

Module Dolibarr custom : vue kanban (Scrumboard) des tâches de projet. Ajoute un
board par projet (onglet `ScrumBoard` sur la fiche projet) et un board global
optionnel accessible depuis le menu Projets.

## Stack

- PHP / Dolibarr (module custom sous `htdocs/custom/scrumboard/`).
- Compatibilité déclarée : Dolibarr >= 16 (`need_dolibarr_version`), PHP >= 7.0.
- Descripteur : `core/modules/modscrumboard.class.php` (numéro module `104210`).
- Tests PHPUnit : `test/phpunit/ScrumboardTest.php` (sprints, colonnes, casts des filtres, champs modifiables d'une tâche).
- Plus de dépendance à Abricot depuis la 2.8.6 : classes `TStory` et `ScrumboardColumn` en SQL direct.

## Structure

- `scrum.php` — page principale du board (par projet et vue globale).
- `admin/scrumboard_setup.php` — page de configuration (constantes du module).
- `core/modules/modscrumboard.class.php` — descripteur (menus, onglets, droits,
  dictionnaire des colonnes, migration de constantes dans `init()`).
- `core/triggers/` — triggers ; `class/actions_scrumboard.class.php` — hooks
  (`projecttaskcard`, `projecttasktime`).
- `class/scrumboard.class.php` — classe métier. `class/techatm.class.php` — vérif
  de version ATM (`TechATM::getLastModuleVersionUrl`).
- `lib/scrumboard.lib.php` — helpers (dont `scrumboardAdminPrepareHead`).
- `sql/` — tables (dont `c_scrum_columns`, dictionnaire des colonnes kanban).
- `sql/*.sql` — tables des sprints (`projet_storie`) et des colonnes ; les `.key.sql` passent la clé en AUTO_INCREMENT sur les anciennes installations. `script/migration_stories.php` — reprise de l'ancien champ « stories ».
- `script/interface.php` — service AJAX du board : module + droit projet + jeton + accès au projet de chaque tâche ou sprint ; seuls label, progress, status, story_k et scrum_status d'une tâche sont modifiables.
- `langs/` — fr_FR, en_US, es_ES, it_IT, el_GR. `doc/` — rapports de compat.

## Conventions & pièges

- **Nom des constantes utilisées dans une condition de menu/onglet** (`enabled`,
  `perms`, `tabcond`) : interdiction des sous-chaînes `_GLOBAL`, `_GET`, `_POST`,
  `_ENV`, `_SESSION`, `_COOKIE`, `_REQUEST`. Elles sont évaluées par `verifCond()`
  → `dol_eval()` qui blackliste ces tokens (anti-superglobales) et corrompt
  silencieusement la condition. Ex. corrigé : `SCRUM_USE_GLOBAL_BOARD` →
  `SCRUM_USE_SHARED_BOARD`.
- Le board global n'apparaît que si la constante `SCRUM_USE_SHARED_BOARD` est
  activée (menu Projets → Scrumboard). La ligne `llx_menu.enabled` n'est
  régénérée qu'à la (ré)activation du module ; `init()` porte la migration des
  constantes legacy.
- Toute nouvelle clé `$langs->trans()` : compléter au minimum `fr_FR` + `en_US`.
- Versioning : chiffre du milieu pour une feature, dernier pour un fix ;
  ChangeLog.md à jour à chaque PR.

## Dette connue

- `script/scrum.js.php` est un fichier JavaScript généré par PHP (règle : JS statique + données en `data-*`) ; à convertir.

## Commandes

- Pas de build ni de tests automatisés. Vérification manuelle : lint PHP
  (`php -l <fichier>`) et activation du module dans Dolibarr.
