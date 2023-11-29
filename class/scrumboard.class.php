<?php

if (!class_exists('TObjetStd'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

class ScrumboardColumn extends TObjetStd
{
	public $TColumn = array();
	
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
	
	function LoadAllBy(&$db, $TConditions = array(), $annexe = true)
	{
		$this->TColumn = parent::LoadAllBy($db, $TConditions, $annexe);
		usort($this->TColumn, array($this, 'orderByRang'));
		
		return $this->TColumn;
	}
	
	private function orderByRang($a, $b)
	{
		if ($a->rang < $b->rang) return -1;
		elseif ($a->rang > $b->rang) return 1;
		
		return 0;
	}
	
	/**
	 * Renvoi un array contenant l'ordre des colonnes (check la conf SCRUM_ADD_BACKLOG_REVIEW_COLUMN)
	 */
	function getTColumnOrder($force_load=false)
	{
		global $conf;
		
		if (getDolGlobalInt('SCRUM_ADD_BACKLOG_REVIEW_COLUMN'))
		{
			if (empty($this->TColumn) || $force_load)
			{
				$PDOdb = new TPDOdb;
				$this->LoadAllBy($PDOdb, array('active' => 1, 'entity' => $conf->entity));	
			}
			
			return $this->TColumn;
		}
		else
		{
			$Tab = array();
			foreach (array('todo' => 'toDo', 'inprogress' => 'inProgress', 'finish' => 'finish') as $code => $label)
			{
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
	 * @global type $conf
	 * @return string
	 */
	function getDefaultColumn()
	{
		global $conf;
		
		if (getDolGlobalInt('SCRUM_ADD_BACKLOG_REVIEW_COLUMN'))
		{
			return !empty($this->TColumn[0]) ? $this->TColumn[0]->code : '';
		}
		else
		{
			return 'todo';
		}
	}
}

class TStory extends TObjetStd {
	public static $tablename = 'projet_storie';

	public function __construct() {
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
	
	function loadStory($fk_project, $storie_order) {
		if(empty($fk_project) || empty($storie_order)) return null;

		$PDOdb = new TPDOdb;

		$sql = 'SELECT rowid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.self::$tablename;
		$sql .= " WHERE fk_projet=$fk_project";
		$sql .= " AND storie_order=$storie_order";

		$resql = $PDOdb->Execute($sql);

		if($obj = $PDOdb->Get_line()) {
			return parent::load($PDOdb, $obj->rowid);
		}
	}
	
	function getAllStoriesFromProject($fk_project) {
		$PDOdb = new TPDOdb;

		$TConditions = array();

		if(! empty($fk_project))
		{
			$TConditions = array('fk_projet' => $fk_project);
		}

		$TRes = parent::LoadAllBy($PDOdb, $TConditions);
		usort($TRes, array($this, 'orderByStoryOrder'));
		return $TRes;
	}

	private function orderByStoryOrder($a, $b)
	{
		if ($a->fk_projet < $b->fk_projet) return -1;
		if ($a->fk_projet > $b->fk_projet) return 1;

		if ($a->storie_order < $b->storie_order) return -1;
		if ($a->storie_order > $b->storie_order) return 1;

		return 0;
	}

	function toggleVisibility() {
		$PDOdb = new TPDOdb;

		$this->visible = ! $this->visible;
		parent::save($PDOdb);
	}
}
