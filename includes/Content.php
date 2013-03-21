<?php
/**
* Content
* Usage:
* 	$Content = new Content();
*/
class Content extends Database
{
	public $dbname = 'content.db';

	function __construct()
	{
		$this->dbinit(BASEPATH.$this->dbname);
	}
}

?>