<?php

/***
 * Model.php
 * @author Alexander Forselius <alex@artistconnector.com>
 * @license MIT
 **/
class Model {
	public static $_table = NULL;
	public static $_idfield = 'id';
	public $id;
	public static $_dataSource;
	public $data = array();
	public function __construct($data, $table = NULL, $dataSource = NULL) {
		$className = get_class($this);
		if (!$dataSource) {
			$className::$_dataSource = new MySQLDataSource();
		}
		if ((!$table) && (!$className::$_table)) {
			$className::$_table = strtolower($className);
		}
		$this->data = $data;
		$keys = array_keys($data);
		if (in_array($className::$_idfield, $data)) {
			$this->id = $data['id'];
		}
	}
	
	/**
	 * Get an item
	 **/
	public static function get($id) {
		$className = get_called_class();

		$rows = $className::$_dataSource->select(array('*'), $className::$_table, array($className::$_idfield => $id));
		if (count($rows) > 0) {
			$row = $rows[0];
			$item = new $className($row, $className::$_table);
			return $item;
		} else {
			return FALSE;
		}
	}

	public static function query($conditions, $fields = array('*')) {
		$className = get_called_class();

		$rows = $className::$_dataSource->select($fields, $className::$_table, $conditions);
		if (count($rows) > 0) {
			$items = array();
			foreach($rows as $row) {
				$item = new $className($row, $this->table);
				$items[] = $item;
			}
			return $items;
		} else {
			return FALSE;
		}
	}

	public function delete() {
		$className = get_class($this);
		$className::$_dataSource->delete($className::$_table, array($className::$_idfield => $this->data[$className::$_idfield]));
	}

	public function save($data = NULL) {
		$className = get_class($this);
		if (isset($this->data[$className::$_idfield]) && $this->data[$className::$_idfield]) {

			$id = $className::$_dataSource->update($className::$_table, $this->data, array($className::$_idfield => $this->data[$className::$_idfield]));
			
			return $this->data[$className::$_idfield];
		} else {
			if (!$data) {
				$data = $this->data; 
			}
			$id = $className::$_dataSource->insert($className::$_table, $data);
			$this->data[$className::$_idfield] = $id;

			return $id;
		}
	}
}

/**
 * DataSource
 **/  
abstract class DataSource {
	/**
	 * Inserts to the database and returns the ID
	 **/
	public abstract function insert($table, $data);
	/**
	 * Deletes from the database
	 **/
	public abstract function delete($table, $conditions);

	/**
	 * Updates to the database
	 **/
	public abstract function update($table, $fields, $conditions);

	/***
	 * Selects from the database
	 **/
	public abstract function select($fields, $table, $conditions); 
}

class MySQLDataSource extends DataSource {

	/**
	 * Generate set pair
	 **/
	private function set($data) {
		$keys = array_keys($data);
		$vals = array_values($data);
		$query = array();
		foreach($data as $k => $v) {
			if (is_numeric($k)) {
				continue;
			}
			$query[] = ' ' . $k . " = '" . mysql_real_escape_string($v) . "'"; 
		}
		$sql = implode(', ', $query);
		return $sql;
	}

	/**
	 * Generate set pair
	 **/
	private function conditions($data) {
		$keys = array_keys($data);
		$vals = array_values($data);
		$query = array();
		foreach($data as $k => $v) {
			if (is_numeric($k)) {
				continue;
			}
			$query[] = ' ' . $k . " = '" . mysql_real_escape_string($v) . "'"; 
		}
		$sql = implode('AND ', $query);
		return $sql;
	}

	/**
	 * Inserts to the database and returns the ID
	 **/
	public function insert($table, $data) {
		$fields = array_keys($data);
		$vals = array_values($data);
		$values = array();
		foreach($vals as $val) {
			$values[] = "'" . mysql_real_escape_string($val) . "'";
		}
		$sql = "INSERT INTO " . $table . "(" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";
		$q = mysql_query($sql) or die($sql);
		if (!$q) {
			throw new Exception(mysql_error());
		}
		return mysql_insert_id();

	}

	public function delete($table, $conditions) {
		$conditions = $this->conditions($conditions);

		$sql = "DELETE FROM " . $table . " WHERE " . $conditions;
		mysql_query($sql) or die(mysql_error());
		return TRUE;
	}

	public function update($table, $fields, $conditions) {
		$conditions = $this->conditions($conditions);
		$sets = $this->set($fields);
		$sql = "UPDATE " . $table . " SET " . $sets . " WHERE " . $conditions;
		
		$q = mysql_query($sql) or die($sql);
		return TRUE;
	}
	public function select( $fields, $table, $conditions) {
		$conditions = $this->conditions($conditions);
		$sql = "SELECT " . implode(', ', $fields) . " FROM " . $table . " WHERE " . $conditions;
		$q = mysql_query($sql) or die($sql);
		$data = array();
		while($row = mysql_fetch_array($q)) {
			$data[] = $row;
		}
		return $data;
	}
}