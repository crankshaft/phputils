<?php
define("E_DB_CONNECT", 1);

class error {
	var $errno;

	function __construct()
	{
		$this->errno = 0;
	}

	function ret($err=0)
	{
		$this->errno = $err;
		return ($this->errno ? false : true);
	}

	function ok()
	{
		if ($this->errno == 0) return true;
		return false;
	}

	function errno()
	{
		return $this->errno;
	}

	function error()
	{
		return "";
	}
}
?>
