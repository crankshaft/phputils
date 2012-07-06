<?php
include_once(dirname(__FILE__) . "/error.inc.php");

class mysql_db extends error {
	/* mysql db handler */
	var $db;
	/* mysql db host config array:
	 * 'm' -> array, master db host
	 * 's' -> array, slave db host
	 * 'dbn' -> string, database name
	 * 'tbp' -> string, prefix of tables
	 * 'tbc' -> int, tables count
	 ***/
	var $dbc;
	var $hash;
	var $table;

	/* affected rows */
	var $af_rows;
	var $my_errno;

	var $db_inited;

	function __construct($conf, $key, $hash=true, $master=true) {
		parent::__construct();
		$this->init();

		if (!$hash) $this->hash = $key;
		else $this->hash = md5($key);

		$this->dbc = $conf;
		$this->_get_db($master); //???
	}

	function __destruct()
	{
		if ($this->db)
			@mysql_close($this->db);
		unset($this->db);
	}

	function init()
	{
		$this->db = 0;
		$this->dbc = 0;
		$this->hash = "";
		$this->table = "";
		$this->af_rows = 0;
		$this->my_errno = 0;
		$this->db_inited = false;
	}

	// TODO: use closure to set hash bucket
	function _get_db($master=true)
	{
		if ($this->db_inited) return $this->ret();

		$count = count($this->dbc['m']);
		$idx = hexdec(substr($this->hash, -3, 1)) % $count;

		$host_conf = $master ? $this->dbc['m'] : $this->dbc['s'];
		$host = $host_conf[$idx];
		_debug_log("Host: {$idx} {$host}\n");

		$db = @mysql_connect($host, $this->dbc['u'], $this->dbc['p']);
		if (!$db) return $this->ret(E_DB_CONNECT);

		$ret = @mysql_select_db($this->dbc['n'], $db);
		if (!$ret) {
			@mysql_close($db);
			$this->my_errno = @mysql_errno($db);
			return $this->ret(E_DB_SELECTDB);
		} 
		$this->db = $db;
		$str = strtolower(substr($this->hash, -2, $this->dbc['tbc']));
		//$this->table = $this->dbc['tbp'] . $str; ///////DEBUGDEBUGDEBUG
		$this->table = $this->dbc['tbp'] . "0";
		_debug_log("Table: {$this->table}\n");
		//$this->table = $this->dbc['tbp'] . str_pad("", $this->dbc['tbc'], '0') ;
		$this->db_inited = true;

		return $this->ret();
	}

	function _compose_sql($elements)
	{
		$str =""; $f = 0;
		foreach ($elements as $key => $val) {
			if ($f++) {
				$str .= ", {$key}={$val}";
			} else {
				$str .= "{$key}={$val}";
			}
		}
		return $str;
	}

	function affected_rows()
	{
		return $this->af_rows;
	}

	function mysql_errno()
	{
		return $this->my_errno;
	}

	function insert($elements)
	{
		$sql = "INSERT INTO {$this->table} SET "
			. $this->_compose_sql($elements);
		_debug_log("insert: " . $sql . "\n");

		$ret = @mysql_query($sql, $this->db);
		if ($ret) return true;

		$this->my_errno = @mysql_errno($this->db);
		return false;
	}

	function update($elements, $cond=0)
	{
		$sql = "UPDATE {$this->table} SET "
			. $this->_compose_sql($elements) . " WHERE {$cond}";
		_debug_log("update: " . $sql . "\n");

		$ret = @mysql_query($sql, $this->db);
		if ($ret) {
			$this->af_rows = @mysql_affected_rows($this->db);
			return $this->ret();
		}

		$this->my_errno = @mysql_errno($this->db);
		return false;
	}

	function select($element, $cond=1)
	{
		$sql = "SELECT {$element} FROM {$this->table} WHERE {$cond}";
		_debug_log("select: " . $sql . "\n");

		$ret = @mysql_query($sql, $this->db);
		if (!$ret) {
			$this->my_errno = @mysql_errno($this->db);
			return false;
		}

		$row = @mysql_fetch_assoc($ret);
		return $row;
	}
}

/* example */
?>
