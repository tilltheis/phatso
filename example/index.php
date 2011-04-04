<?php
/**
 * set DEBUG to true to display php errors
 */
define('DEBUG', true);

/**
 * set up the urls
 */
$URLS = array(
	'/' => 'index'
);

/**
 * create the Phatso object and run the $URLS
 */
require_once('../phatso.php');
$app = new Phatso();
$app->run($URLS);

/**
 * funtion to be executed on / request
 */
function exec_index($app, $params) {
	$app->set('text', 'Hello World!');
	$app->render('index.php');
}
?>