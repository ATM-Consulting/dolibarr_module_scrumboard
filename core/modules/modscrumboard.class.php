<?php
/* Scrumboard for project's tasks
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup	scrumboard	scrumboard module
 * 	\file		core/modules/modscrumboard.class.php
 * 	\ingroup	scrumboard
 * 	\brief		Description and activation file for module scrumboard
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module scrumboard
 */
class modscrumboard extends DolibarrModules
{

	/**
	 * 	Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * 	@param	DoliDB		$db	Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->editor_name = 'ATM Consulting';
		$this->editor_url = 'https://www.atm-consulting.fr';
		// Id for module (must be unique). 104000 to 104999 for ATM CONSULTING
		$this->numero = 104210;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'scrumboard';

		// Family used to group modules in module setup page
		$this->family = "projects";
		// Module label (no space allowed), used if translation 'ModuleXXXName' not found
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation 'ModuleXXXDesc' not found
		$this->description = "Module pour gérer les tâches projet sur une vue kanban";
		// Possible values for version are: 'development', 'experimental' or version
		$this->version = '2.8.3';
		// Url to the file with the last version number of this module
		require_once __DIR__ . '/../../class/techatm.class.php';
		$this->url_last_version = \scrumboard\TechATM::getLastModuleVersionUrl($this);

		// Key used in llx_const table to save module status enabled/disabled
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Where to store the module in setup page (0=common)
		$this->special = 0;
		$this->picto = 'module.svg@scrumboard';

		// Module parts (triggers, hooks, ...)
		$this->module_parts = array(
			'triggers' => 1,
			'hooks' => array('projecttaskcard', 'projecttasktime'),
		);

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config page stored into scrumboard/admin directory
		$this->config_page_url = 'scrumboard_setup.php@scrumboard';

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(7, 0);
		$this->need_dolibarr_version = array(16, 0);
		$this->langfiles = array("scrumboard@scrumboard");

		// Constants added when module is enabled
		$this->const = array();

		// New tabs added by module
		$this->tabs = array(
			'project:+scrumboard:ScrumBoard:scrumboard@scrumboard::/scrumboard/scrum.php?id=__ID__'
		);

		// Dictionnaries. Stub $conf->scrumboard to avoid warnings when module is not enabled yet.
		if (!isModEnabled('scrumboard')) {
			$conf->scrumboard = new stdClass();
			$conf->scrumboard->enabled = 0;
		}
		$this->dictionnaries = array(
			'langs' => 'scrumboard@scrumboard',
			'tabname' => array(
				$db->prefix() . 'c_scrum_columns'
			),
			'tablib' => array(
				'ScrumManageColumns'
			),
			'tabsql' => array(
				'SELECT sc.rowid, sc.label, sc.rang, sc.active, sc.code, sc.entity FROM ' . $db->prefix() . 'c_scrum_columns as sc'
			),
			'tabsqlsort' => array(
				'rang ASC'
			),
			'tabfield' => array(
				'label,code,rang'
			),
			'tabfieldvalue' => array(
				'label,code,rang'
			),
			'tabfieldinsert' => array(
				'label,code,rang'
			),
			'tabrowid' => array(
				'rowid'
			),
			'tabcond' => array(
				"isModEnabled('scrumboard')" // TODO ?? -> && getDolGlobalInt('SCRUM_ADD_BACKLOG_REVIEW_COLUMN')
			)
		);

		// Boxes
		$this->boxes = array();
		$r = 0;
		$this->boxes[$r][1] = "scrumboard_box@scrumboard";
		$r++;

		// Permissions
		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'scrumboard_export';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'export';			// In php code, permission checked with $user->hasRight("permkey", "level1", "level2")
		$this->rights[$r][5] = '';
		$r++;

		// Main menu entries
		$r = 0;
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=project',
			'type' => 'left',
			'titre' => 'Scrumboard',
			'mainmenu' => 'project',
			'leftmenu' => 'Scrumboard',
			'url' => '/scrumboard/scrum.php',
			'langs' => 'scrumboard@scrumboard',
			'position' => 100,
			'perms' => '1',
			'target' => '',
			'user' => 2, // 0=internal users, 1=external users, 2=both
			'enabled' => 'isModEnabled("scrumboard") && getDolGlobalString("SCRUM_USE_SHARED_BOARD")'
		);
		$r++;
	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function init($options = '')
	{

		global $db, $conf;
		$sql = array();
		if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', true);

		// Migrate legacy constant SCRUM_USE_GLOBAL_BOARD to SCRUM_USE_SHARED_BOARD.
		// The legacy name contains the substring "_GLOBAL", rejected by dol_eval() as a
		// forbidden superglobal token, which corrupted the left menu 'enabled' condition.
		if (!isset($conf->global->SCRUM_USE_SHARED_BOARD) && isset($conf->global->SCRUM_USE_GLOBAL_BOARD)) {
			if (dolibarr_set_const($this->db, 'SCRUM_USE_SHARED_BOARD', getDolGlobalString('SCRUM_USE_GLOBAL_BOARD'), 'chaine', 0, '', $conf->entity) > 0) {
				dolibarr_del_const($this->db, 'SCRUM_USE_GLOBAL_BOARD', $conf->entity);
			}
		}

		dol_include_once('/scrumboard/config.php');
		dol_include_once('/scrumboard/script/create-maj-base.php');

		$this->loadTables();

		dolibarr_set_const($this->db, 'SCRUM_DEFAULT_VELOCITY', 7, 'chaine', 1, 'Vélocité par défaut d\'un projet', 0);

		$this->db->query('ALTER TABLE '.$db->prefix().'projet_task ADD story_k integer NOT NULL DEFAULT \'0\'');
		$this->db->query('ALTER TABLE '.$db->prefix().'projet_task ADD scrum_status varchar(255) NOT NULL DEFAULT \'\'');

		$this->db->query('ALTER TABLE '.$db->prefix().'c_scrum_columns ADD CONSTRAINT unique_code UNIQUE(code)');
		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /scrumboard/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/scrumboard/sql/');
	}
}
