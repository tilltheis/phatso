<?php
/**
 * set DEBUG to true to display php errors
 */
define('DEBUG', true);

/**
 * set up the urls
 */
$URLS = array(
	'/' => 'index',
	'/hello/' => 'hello'
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
	$app->set('title', 'Phatos Sample Application');
	// index view is auto rendered
}

/**
 * function to be executed on /hello/ request
 */
function exec_hello($app, $params) {
	$app->set('text', 'Hello World!');
	// hello view is auto rendered
}
?>