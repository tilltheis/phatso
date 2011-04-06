<?php
/**
 * set DEBUG to true to display php errors
 */
define('DEBUG', true);

/**
 * require the phatso file
 */
require_once('../phatso.php');

/**
 * set up the urls
 */
$URLS = array(
	'/' => 'index',
	'/hello/(.*)' => 'hello'
);

/**
 * create the sampleApp class extending the Phatso framework
 */ 
class SampleApp extends Phatso {
	/**
	 * funtion to be executed on / request
	 */
	function indexAction() {
		$this->set('title', 'Phatos Sample Application');
		// index view is auto rendered
	}

	/**
	 * function to be executed on /hello/(.+) request
	 */
	function helloAction($name = 'World') {
		$this->set('text', sprintf('Hello %s!', $name));
		// hello view is auto rendered
	}
	
	/**
	 * method to be executed when a 404 error happens
	 */
	function status404($msg) {
		$this->set('msg', '404 - File Not Found');
		$this->render('status404.php');
	}
}

/**
 * create the object and run the $URLS
 */
$app = new SampleApp();
$app->run($URLS);

?>