<?php
include(BASEPATH.'includes/Database.php');
include(BASEPATH.'includes/User.php');
include(BASEPATH.'includes/REST.php');

Plugins::load();
base_paths();

$db = new Database();
$db->connect($config['db_name'], $config['db_type']);
$db->install();	// uncomment to install database tables from schema.php
$REST = new REST();
$User = new User();

include(BASEPATH.'includes/API.php');

?>