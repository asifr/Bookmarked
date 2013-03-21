<?php
/**
* Database class, uses PDO
*/
class Database
{
	public $dbname = '';
	public $dbtype = '';
	public $conn = null;
	public $select_queries = array();

	public $postgres_datatype_transformations = array(
		'/^(TINY|SMALL)INT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'			=>	'SMALLINT',
		'/^(MEDIUM)?INT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'				=>	'INTEGER',
		'/^BIGINT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'						=>	'BIGINT',
		'/^(TINY|MEDIUM|LONG)?TEXT$/i'										=>	'TEXT',
		'/^DOUBLE( )?(\\([0-9,]+\\))?( )?(UNSIGNED)?$/i'					=>	'DOUBLE PRECISION',
		'/^FLOAT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'						=>	'REAL'
	);

	public $sqlite_datatype_transformations = array(
		'/^SERIAL$/'															=>	'INTEGER',
		'/^(TINY|SMALL|MEDIUM|BIG)?INT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'	=>	'INTEGER',
		'/^(TINY|MEDIUM|LONG)?TEXT$/i'											=>	'TEXT'
	);

	public $postgres_check_table_exists = 'SELECT 1 FROM information_schema.tables WHERE table_name=%s';

	public $sqlite_check_table_exists = 'SELECT 1 FROM sqlite_master WHERE name=%s AND type=\'table\'';

	// Establish connection to database
	public function connect($dbname, $dbtype)
	{
		$this->dbname = $dbname;
		$this->dbtype = $dbtype;
		try {
			switch ($this->dbtype) {
				case 'postgres':
				case 'psql':
					$this->conn = new PDO('pgsql:host=localhost;port=5432;dbname='.$this->dbname);
					break;
				case 'sqlite':
				case 'sqlite3':
					$this->conn = new PDO('sqlite:'.$this->dbname.'.db');
					break;
			}
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			error('Unable to open database. Reported: '.$e->getMessage());
		}
	}

	// Create tables if the don't already exist
	public function install()
	{
		include(BASEPATH.'includes/schema.php');
		switch ($this->dbtype) {
			case 'postgres':
			case 'psql':
				$check_table_exists = $this->postgres_check_table_exists;
				break;
			case 'sqlite':
			case 'sqlite3':
				$check_table_exists = $this->sqlite_check_table_exists;
				break;
		}
		foreach ($schema as $table_name => $table_schema) {
			if (count($this->conn->query(sprintf($check_table_exists,$this->quote($table_name)))->fetchAll()) == 0) {
				$this->conn->exec($this->create_table($table_name, $table_schema));
			}
		}
	}

	public function quote($str)
	{
		return $this->conn->quote($str);
	}

	// Returns the CREATE TABLE SQL string
	function create_table($table_name, $schema)
	{
		switch ($this->dbtype) {
			case 'postgres':
			case 'psql':
				$datatype_transformations = $this->postgres_datatype_transformations;
				break;
			case 'sqlite':
			case 'sqlite3':
				$datatype_transformations = $this->sqlite_datatype_transformations;
				break;
		}
		$query = "CREATE TABLE ".$table_name." (\n";
		// Go through every schema element and add it to the query
		foreach ($schema['FIELDS'] as $field_name => $field_data) {
			$field_data['datatype'] = preg_replace(array_keys($datatype_transformations), array_values($datatype_transformations), $field_data['datatype']);
			$query .= $field_name.' '.$field_data['datatype'];
			// The SERIAL datatype is a special case where we don't need to say not null
			if (!$field_data['allow_null'] && $field_data['datatype'] != 'SERIAL')
				$query .= ' NOT NULL';
			if (isset($field_data['default']))
				$query .= ' DEFAULT '.($field_data['datatype']=='INTEGER'?$field_data['default']:$this->quote($field_data['default']));
			$query .= ",\n";
		}
		// If we have a primary key, add it
		if (isset($schema['PRIMARY KEY']))
			$query .= 'PRIMARY KEY ('.implode(',', $schema['PRIMARY KEY']).'),'."\n";
		// Add unique keys
		if (isset($schema['UNIQUE KEYS'])) {
			foreach ($schema['UNIQUE KEYS'] as $key_name => $key_fields)
				$query .= 'UNIQUE ('.implode(',', $key_fields).'),'."\n";
		}
		// We remove the last two characters (a newline and a comma) and add on the ending
		$query = substr($query, 0, strlen($query) - 2)."\n".')';
		return $query;
	}

	public function meta($arr)
	{
		$meta = array();
		foreach ($arr as $value) {
			$meta[$value['meta_key']] = $value['meta_value'];
		}
		return $meta;
	}
}

?>