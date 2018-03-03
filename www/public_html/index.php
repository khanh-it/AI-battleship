<?php
/**
 * 
 */
// 
define('PUBLIC_DIR', dirname(__FILE__));
//
define('PROJECT_DIR', dirname(PUBLIC_DIR));
//
define('LIB_DIR', realpath(PROJECT_DIR . '/lib'));
//
define('DATA_DIR', realpath(PROJECT_DIR . '/data'));
//
set_include_path(implode(PATH_SEPARATOR, array(
	LIB_DIR,
	get_include_path()
)));

//
require_once('battleship/game_engine.php');

//
$GE = new GameEngine();
$GE->resolveRequest();
