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

/**
 * Class ScrumboardColumn
 */
class ScrumboardColumn
{
	/** @var stdClass[] $TColumn Loaded columns, ordered by rank */
	public $TColumn = array();

	/**
	 * Load the columns matching the conditions, ordered by rank.
	 *
	 * @param  DoliDB $db          Database handler
	 * @param  array  $TConditions Column name => value (only code, active and entity are accepted)
	 * @return stdClass[]          Columns
	 */
	public function LoadAllBy($db, $TConditions = array()) // phpcs:ignore PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	{
		$sql = 'SELECT rowid, code, label, rang, active, entity FROM '.$db->prefix().'c_scrum_columns WHERE 1 = 1';
		foreach (array('entity', 'active') as $field) {
			if (isset($TConditions[$field])) {
				$sql .= ' AND '.$field.' = '.((int) $TConditions[$field]);
			}
		}
		if (isset($TConditions['code'])) {
			$sql .= " AND code = '".$db->escape($TConditions['code'])."'";
		}
		$sql .= ' ORDER BY rang ASC, rowid ASC';

		$this->TColumn = array();
		$resql = $db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' '.$db->lasterror(), LOG_ERR);
			return $this->TColumn;
		}
		while ($obj = $db->fetch_object($resql)) {
			$this->TColumn[] = $obj;
		}
		$db->free($resql);

		return $this->TColumn;
	}

	/**
	 * Order of the columns of the board (see the SCRUM_ADD_BACKLOG_REVIEW_COLUMN setting).
	 *
	 * @param  bool $force_load Force reload from the database
	 * @return array            Columns
	 */
	public function getTColumnOrder(bool $force_load = false): array
	{
		global $conf, $db;

		if (getDolGlobalInt('SCRUM_ADD_BACKLOG_REVIEW_COLUMN')) {
			if (empty($this->TColumn) || $force_load) {
				$this->LoadAllBy($db, array('active' => 1, 'entity' => (int) $conf->entity));
				if (empty($this->TColumn) && (int) $conf->entity !== 1) {
					dol_syslog(__METHOD__ . ' Fallback on master entity columns (entity=1), no active column found for entity=' . ((int) $conf->entity), LOG_INFO);
					$this->LoadAllBy($db, array('active' => 1, 'entity' => 1));
				}
			}

			return $this->TColumn;
		}

		$Tab = array();
		foreach (array('todo' => 'toDo', 'inprogress' => 'inProgress', 'finish' => 'finish') as $code => $label) {
			$obj = new stdClass;
			$obj->label = $label;
			$obj->code = $code;
			$Tab[] = $obj;
		}

		return $Tab;
	}

	/**
	 * Code of the column where the tasks without column are put.
	 *
	 * @return string Column code
	 */
	public function getDefaultColumn()
	{
		if (getDolGlobalInt('SCRUM_ADD_BACKLOG_REVIEW_COLUMN')) {
			return !empty($this->TColumn[0]) ? $this->TColumn[0]->code : '';
		}

		return 'todo';
	}
}

/**
 * Class TStory
 */
class TStory
{
	/** @var string $tablename */
	public static $tablename = 'projet_storie';

	/** @var DoliDB $db */
	private $db;

	/** @var int $id */
	public $id = 0;
	/** @var int $fk_projet */
	public $fk_projet = 0;
	/** @var int $storie_order */
	public $storie_order = 0;
	/** @var string $label */
	public $label = '';
	/** @var int $visible */
	public $visible = 1;
	/** @var int|null $date_start Timestamp */
	public $date_start = null;
	/** @var int|null $date_end Timestamp */
	public $date_end = null;
	/** @var string $error */
	public $error = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * @param  stdClass $obj Database row
	 * @return void
	 */
	private function setFromRow($obj)
	{
		$this->id = (int) $obj->rowid;
		$this->fk_projet = (int) $obj->fk_projet;
		$this->storie_order = (int) $obj->storie_order;
		$this->label = (string) $obj->label;
		$this->visible = (int) $obj->visible;
		$this->date_start = $obj->date_start ? $this->db->jdate($obj->date_start) : null;
		$this->date_end = $obj->date_end ? $this->db->jdate($obj->date_end) : null;
	}

	/**
	 * Load a story.
	 *
	 * @param  int $fk_project   Project ID
	 * @param  int $storie_order Story order
	 * @return int               ID of the story, 0 when not found or invalid
	 */
	public function loadStory($fk_project, $storie_order)
	{
		if (empty($fk_project) || empty($storie_order)) {
			return 0;
		}

		$sql = 'SELECT rowid, fk_projet, storie_order, label, visible, date_start, date_end FROM '.$this->db->prefix().self::$tablename;
		$sql .= ' WHERE fk_projet = '.((int) $fk_project).' AND storie_order = '.((int) $storie_order);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return 0;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return 0;
		}
		$this->setFromRow($obj);

		return $this->id;
	}

	/**
	 * Load a story by its id.
	 *
	 * @param  int $id Story id
	 * @return int     Story id, 0 when not found
	 */
	public function load($id)
	{
		$sql = 'SELECT rowid, fk_projet, storie_order, label, visible, date_start, date_end FROM '.$this->db->prefix().self::$tablename;
		$sql .= ' WHERE rowid = '.((int) $id);

		$resql = $this->db->query($sql);
		$obj = $resql ? $this->db->fetch_object($resql) : null;
		if (!$obj) {
			return 0;
		}
		$this->setFromRow($obj);

		return $this->id;
	}

	/**
	 * Stories of a project (or of every project), ordered by project then by order.
	 *
	 * @param  int $fk_project Project ID, 0 for every project
	 * @return TStory[]        Stories
	 */
	public function getAllStoriesFromProject($fk_project)
	{
		$sql = 'SELECT rowid, fk_projet, storie_order, label, visible, date_start, date_end FROM '.$this->db->prefix().self::$tablename;
		if (!empty($fk_project)) {
			$sql .= ' WHERE fk_projet = '.((int) $fk_project);
		}
		$sql .= ' ORDER BY fk_projet ASC, storie_order ASC';

		$TRes = array();
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
			return $TRes;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$story = new self($this->db);
			$story->setFromRow($obj);
			$TRes[] = $story;
		}
		$this->db->free($resql);

		return $TRes;
	}

	/**
	 * Create or update the story.
	 *
	 * @return int >0 if OK, <0 if KO
	 */
	public function save()
	{
		$date = function ($timestamp) {
			return empty($timestamp) ? 'NULL' : "'".$this->db->idate($timestamp)."'";
		};

		if ($this->id > 0) {
			$sql = 'UPDATE '.$this->db->prefix().self::$tablename.' SET';
			$sql .= " date_maj = '".$this->db->idate(dol_now())."'";
			$sql .= ', fk_projet = '.((int) $this->fk_projet).', storie_order = '.((int) $this->storie_order);
			$sql .= ", label = '".$this->db->escape($this->label)."', visible = ".((int) $this->visible);
			$sql .= ', date_start = '.$date($this->date_start).', date_end = '.$date($this->date_end);
			$sql .= ' WHERE rowid = '.((int) $this->id);
		} else {
			$sql = 'INSERT INTO '.$this->db->prefix().self::$tablename.' (date_cre, date_maj, fk_projet, storie_order, label, visible, date_start, date_end) VALUES (';
			$sql .= "'".$this->db->idate(dol_now())."', '".$this->db->idate(dol_now())."', ".((int) $this->fk_projet).', '.((int) $this->storie_order);
			$sql .= ", '".$this->db->escape($this->label)."', ".((int) $this->visible).', '.$date($this->date_start).', '.$date($this->date_end).')';
		}

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}
		if (empty($this->id)) {
			$this->id = (int) $this->db->last_insert_id($this->db->prefix().self::$tablename);
		}

		return 1;
	}

	/**
	 * Delete the story.
	 *
	 * @return int >0 if OK, <0 if KO
	 */
	public function delete()
	{
		if (empty($this->id)) {
			return 0;
		}
		if (!$this->db->query('DELETE FROM '.$this->db->prefix().self::$tablename.' WHERE rowid = '.((int) $this->id))) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	/**
	 * Show or hide the story.
	 *
	 * @return int Result of save()
	 */
	public function toggleVisibility()
	{
		$this->visible = $this->visible ? 0 : 1;

		return $this->save();
	}
}
