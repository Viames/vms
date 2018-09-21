<?php

namespace Pair;

class Breadcrumb {

	/**
	 * List of all paths.
	 * @var array:stdClass
	 */
	protected $paths = array();

	/**
	 * Singleton object.
	 * @var Breadcrumb
	 */
	protected static $instance = NULL;

	/**
	 * Initializes breadcrumb with Home path.
	 */
	private function __construct() {
		
		$app = Application::getInstance();
		
		// add user-landing path if user is available
		if (is_a($app->currentUser, 'Pair\User')) {
			$landing = $app->currentUser->getLanding();
			$resource = $landing->module . '/' . $landing->action;
			$this->addPath('Home', $resource);
		}

	}
	
	/**
	 * Returns singleton object.
	 *
	 * @return	Breadcrumb
	 */
	public static function getInstance() {
	
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
	
		return self::$instance;
	
	}
	
	/**
	 * Adds a new sub-path to Breadcrumb. Chainable method.
	 * 
	 * @param	string	Title of the sub-path.
	 * @param	string	Destination URL (default NULL).
	 * 
	 * @return	Breadcrumb
	 */
	public function addPath($title, $url=NULL) {
		
		$path			= new \stdClass();
		$path->title	= $title;
		$path->url		= $url;
		$path->active	= TRUE;

		// just last active path will remains active
		foreach ($this->paths as $p) {
			$p->active = FALSE;
		}
		
		$this->paths[]	= $path;
		
		return $this;
	
	}
	
	/**
	 * Returns all paths as array.
	 * 
	 * @return array:stdClass
	 */
	public function getPaths() {
		
		return $this->paths;
		
	}
	
	/**
	 * Overwrite the standard Home path.
	 * 
	 * @param	string	Path title.
	 * @param	string	Optional URL.
	 */
	public function setHome($title, $url=NULL) {
		
		// TODO
		
	}
	
	/**
	 * Returns title of last item of breadcrumb.
	 * 
	 * @return	string
	 */
	public function getLastPathTitle() {
		
		$path = end($this->paths());
		return $path->title;
		
	}
	
}