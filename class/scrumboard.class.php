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

if (!class_exists('TObjetStd')) {
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

/**
 * Class ScrumboardColumn
 */
class ScrumboardColumn extends TObjetStd
{
	/** @var array $TColumn */
	public $TColumn = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $conf;

		$this->set_table(MAIN_DB_PREFIX.'c_scrum_columns');

		$this->add_champs('code', array('type' => 'string', 'length' => 50, 'index' => true));
		$this->add_champs('label', array('type' => 'string', 'length' => 100));
		$this->add_champs('rang,active', array('type' => 'integer'));
		$this->add_champs('entity', array('type' => 'integer', 'index' => true, 'default' => 1));

		$this->_init_vars();
		$this->start();

		$this->entity = $conf->entity;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load all objects
	 *
	 * @param   TPDOdb  $db             Database handler
	 * @param   array   $TConditions    Conditions
	 * @param   bool    $annexe         Load linked objects
	 * @return  array                   Array of objects
	 */
	public function LoadAllBy(&$db, $TConditions = array(), $annexe = true)
	{
		$this->TColumn = parent::LoadAllBy($db, $TConditions, $annexe);
		usort($this->TColumn, array($this, 'orderByRang'));

		return $this->TColumn;
	}
	// phpcs:enable

	/**
	 * Sort by rank
	 *
	 * @param   ScrumboardColumn $a  Object A
	 * @param   ScrumboardColumn $b  Object B
	 * @return  int                  -1, 0, 1
	 */
	private function orderByRang($a, $b)
	{
		if ($a->rang < $b->rang) return -1;
		elseif ($a->rang > $b->rang) return 1;

		return 0;
	}

	/**
	 * Renvoi un array contenant l'ordre des colonnes (check la conf SCRUM_ADD_BACKLOG_REVIEW_COLUMN)
	 *
	 * @param   bool    $force_load     Force reload from DB
	 * @return  array                   Array of columns
	 */
	public function getTColumnOrder(bool $force_load = false): array
	{
		global $conf;

		if (getDolGlobalInt('SCRUM_ADD_BACKLOG_REVIEW_COLUMN')) {
			$PDOdb = new TPDOdb;
			$this->TColumn = array();
			$this->LoadAllBy($PDOdb, array('active' => 1, 'entity' => (int) $conf->entity));
			if (empty($this->TColumn) && (int) $conf->entity !== 1) {
				$this->LoadAllBy($PDOdb, array('active' => 1, 'entity' => 1));
			}

			return $this->TColumn;
		} else {
			$Tab = array();
			foreach (array('todo' => 'toDo', 'inprogress' => 'inProgress', 'finish' => 'finish') as $code => $label) {
				$obj = new stdClass;
				$obj->label = $label;
				$obj->code = $code;
				$Tab[] = $obj;
			}

			return $Tab;
		}
	}

	/**
	 * Renvoi le code de la colonne où sont mise les taches non rattachées
	 *
	 * @return string   Column code
	 */
	public function getDefaultColumn()
	{
		global $conf;

		if (getDolGlobalInt('SCRUM_ADD_BACKLOG_REVIEW_COLUMN')) {
			return !empty($this->TColumn[0]) ? $this->TColumn[0]->code : '';
		} else {
			return 'todo';
		}
	}
}

/**
 * Class TStory
 */
class TStory extends TObjetStd
{
	/** @var string $tablename */
	public static $tablename = 'projet_storie';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->set_table(MAIN_DB_PREFIX.self::$tablename);

		$this->add_champs('fk_projet', array('type' => 'integer', 'index' => true));
		$this->add_champs('storie_order', array('type' => 'integer', 'index' => true));
		$this->add_champs('label', array('type' => 'string', 'length' => 100));
		$this->add_champs('visible', array('type' => 'integer'));
		$this->add_champs('date_start,date_end', array('type' => 'date'));

		$this->_init_vars();
		$this->start();

		$this->visible = 1;
		$this->date_start = $this->date_end = null;
	}

	/**
	 * Load a story
	 *
	 * @param   int     $fk_project     Project ID
	 * @param   int     $storie_order   Story order
	 * @return  int|null                ID or null
	 */
	public function loadStory($fk_project, $storie_order)
	{
		if (empty($fk_project) || empty($storie_order)) return null;

		$PDOdb = new TPDOdb;

		$sql = 'SELECT rowid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.self::$tablename;
		$sql .= " WHERE fk_projet=$fk_project";
		$sql .= " AND storie_order=$storie_order";

		$resql = $PDOdb->Execute($sql);

		if ($obj = $PDOdb->Get_line()) {
			return parent::load($PDOdb, $obj->rowid);
		}
	}

	/**
	 * Get all stories from project
	 *
	 * @param   int     $fk_project     Project ID
	 * @return  array                   Array of stories
	 */
	public function getAllStoriesFromProject($fk_project)
	{
		$PDOdb = new TPDOdb;

		$TConditions = array();

		if (! empty($fk_project)) {
			$TConditions = array('fk_projet' => $fk_project);
		}

		$TRes = parent::LoadAllBy($PDOdb, $TConditions);
		usort($TRes, array($this, 'orderByStoryOrder'));
		return $TRes;
	}

	/**
	 * Sort by Story order
	 *
	 * @param   TStory $a  Story A
	 * @param   TStory $b  Story B
	 * @return  int        -1, 0, 1
	 */
	private function orderByStoryOrder($a, $b)
	{
		if ($a->fk_projet < $b->fk_projet) return -1;
		if ($a->fk_projet > $b->fk_projet) return 1;

		if ($a->storie_order < $b->storie_order) return -1;
		if ($a->storie_order > $b->storie_order) return 1;

		return 0;
	}

	/**
	 * Toggle visibility
	 *
	 * @return  int     Result of save
	 */
	public function toggleVisibility()
	{
		$PDOdb = new TPDOdb;

		$this->visible = ! $this->visible;
		return parent::save($PDOdb);
	}
}
