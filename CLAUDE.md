# CLAUDE.md — module scrumboard

Module Dolibarr custom : vue kanban (Scrumboard) des tâches de projet. Ajoute un
board par projet (onglet `ScrumBoard` sur la fiche projet) et un board global
optionnel accessible depuis le menu Projets.

## Stack

- PHP / Dolibarr (module custom sous `htdocs/custom/scrumboard/`).
- Compatibilité déclarée : Dolibarr >= 16 (`need_dolibarr_version`), PHP >= 7.0.
- Descripteur : `core/modules/modscrumboard.class.php` (numéro module `104210`).
- Aucune suite de tests (pas de `composer.json`/`phpunit.xml` ; `.travis.yml` est du
  boilerplate obsolète non fonctionnel).

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
- `script/create-maj-base.php` — création/màj du schéma, appelé par `init()`.
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

## Commandes

- Pas de build ni de tests automatisés. Vérification manuelle : lint PHP
  (`php -l <fichier>`) et activation du module dans Dolibarr.
