<?php

namespace Pair;

abstract class Controller {
	
	/**
	 * Application object.
	 * @var	Application
	 */
	protected $app;

	/**
	 * Router object.
	 * @var	Router
	 */
	protected $router;
	
	/**
	 * Model for this MVC stack.
	 * @var multitype
	 */
	protected $model;
	
	/**
	 * View’s file name, without file extension.
	 * @var string
	 */
	protected $view;
	
	/**
	 * Translator object.
	 * @var Translator
	 */
	protected $translator;
	
	/**
	 * Controller’s name, without “Controller” suffix.
	 * @var string
	 */
	private $name;
	
	/**
	 * Path to the module for this controller.
	 * @var string
	 */
	private $modulePath;
	
	final public function __construct() {
		
		// useful singleton objects
		$this->app = Application::getInstance();
		$this->router = Router::getInstance();
		$this->translator = Translator::getInstance();
		
		// set controller’s name
		$class = get_called_class();
		$this->name = substr($class, 0, strpos($class, 'Controller'));

		// path to the module folder
		$ref = new \ReflectionClass($this);
		$this->modulePath = dirname($ref->getFileName());
		
		// new instance to the default model
		include ($this->modulePath .'/model.php');
		$modelName = $this->name . 'Model';
		$this->model = new $modelName();
		
		// sets language subfolder’s name
		$this->translator->setModuleName($this->name);
		
		// sets same view as the controller action
		$this->view = $this->router->action ? $this->router->action : 'default';

		$this->init();
		
		// look for extended classes
		if (is_dir($this->modulePath . '/classes')) {
			
			// get all folder files
			$filenames = Utilities::getDirectoryFilenames($this->modulePath . '/classes');

			// include each class file
			foreach ($filenames as $filename) {
				include_once $this->modulePath . '/classes/' . $filename;
			}
			
		}
		
	}
	
	/**
	 * Start function, being executed before each method. Optional.
	 */
	protected function init() {}
	
	/**
	 * Empty function, could be overloaded.
	 */
	public function defaultAction() {}
	
	public function __get($name) {
	
		if ('route' == $name) {
			$this->logWarning('$this->route is deprecated');
			return $this->router;
		}
	
		try {
			if (!isset($this->$name)) {
				throw new \Exception('Property “'. $name .'” doesn’t exist for this object '. get_called_class());
			}
			return $this->$name;
		} catch(\Exception $e) {
			return NULL;
		}
	
	}
	
	public function __set($name, $value) {
		
		try {
			$this->$name = $value;
		} catch(\Exception $e) {
			print $e->getMessage();
		}
		
	}

	/**
	 * Notices developer about a call to unexistent method.
	 *
	 * @param	string	Method name.
	 * @param	array	Method arguments.
	 */
	public function __call($name, $arguments) {
		
		// do nothing
		
	}
	
	/**
	 * Proxy to set a variable within global scope.
	 * 
	 * @param	string	Variable name.
	 * 
	 * @return	mixed	Any variable type.
	 */
	final public function setState($name, $value) {
		
		$this->app->setState($name, $value);
		
	}

	/**
	 * Proxy to unset a state variable.
	 * 
	 * @param	string	Variable name.
	 */
	final public function unsetState($name) {
		
		$this->app->unsetState($name);
		
	}
	
	/**
	 * Proxy to get a variable within global scope.
	 * 
	 * @param	string	Variable name.
	 */
	final public function getState($name) {
	
		return $this->app->getState($name);
	
	}
	
	/**
	 * Proxy to append a text message to queue.
	 *
	 * @param	string	Message’s text.
	 * @param	string	Optional title.
	 * @param	string	Message’s type (info, error).
	 */
	final public function enqueueMessage($text, $title='', $type=NULL) {
		
		$this->app->enqueueMessage($text, $title, $type);
		
	}
	
	/**
	 * Proxy to queue an error message.
	 *
	 * @param	string	Message’s text.
	 * @param	string	Optional title.
	 */
	final public function enqueueError($text, $title='') {
		
		$this->app->enqueueError($text, $title);
	
	}
	
	/**
	 * Proxy to add an event to framework’s logger, storing its chrono time.
	 * 
	 * @param	string	Event description.
	 * @param	string	Event type notice or error (default notice).
	 * @param	string	Optional additional text.
	 */
	final public function logEvent($description, $type='notice', $subtext=NULL) {
		
		$logger = Logger::getInstance();
		$logger->addEvent($description, $type, $subtext);
		
	}

	/**
	 * Proxy to add a warning event.
	 *
	 * @param	string	Event description.
	 */
	final public function logWarning($description) {
	
		$logger = Logger::getInstance();
		$logger->addWarning($description);
	
	}
	
	/**
	 * Proxy to add an error event.
	 *
	 * @param	string	Event description.
	 */
	final public function logError($description) {
	
		$logger = Logger::getInstance();
		$logger->addError($description);
	
	}
	
	/**
	 * Proxy to redirect HTTP on the URL param. Relative path as default.
	 *
	 * @param	string	Location URL.
	 * @param	bool	If TRUE, will avoids to add base url (default FALSE).
	 */
	public function redirect($url, $absoluteUrl=FALSE) {

		$this->app->redirect($url, $absoluteUrl);

	}
	
	/**
	 * Return View object related to this controller.
	 * 
	 * @return	multitype
	 * 
	 * @throws Exception
	 */
	public function getView() {
		
		try {
			
			if ($this->view) {
				
				$file = $this->modulePath .'/view'. ucfirst($this->view) .'.php';
				
				if (!file_exists($file)) {
					if ($this->app->currentUser->isPopulated()) {
						throw new \Exception('View file '. $file .' has not been found');
					} else {
						die('Access denied');
					}
				}

				include_once($file);

				$viewName = ucfirst($this->name) .'View'. ucfirst($this->view);
				
				return new $viewName();
				
			} else {
				
				throw new \Exception('No view file has been set');
				
			}
		
		} catch (\Exception $e) {
			
			// set the error in the log
			$this->app->logError($e->getMessage());
			
			// get referer and redirect
			$url = !isset($_SERVER['REFERER']) ? $_SERVER['REFERER'] : BASE_HREF;
			$this->redirect($url, TRUE);
			
		}
		
		return NULL;
		
	}
	
	/**
	 * Include the file for View formatting. Display an error message and
	 * redirect to default view as fallback in case of view not found for non-ajax requests.
	 */
	public function display() {
		
		$view = $this->getView();

		if (is_subclass_of($view, 'Pair\View')) {
			$view->display();
		} else {
			if (!$this->router->isRaw()) {
				$this->enqueueError($this->translator->get('RESOURCE_NOT_FOUND', $this->router->module . '/' . $this->router->action));
			}
			$this->redirect($this->router->module);
		}
		
	}

	/**
	 * Proxy function to translate a string, used for AJAX return messages.
	 * 
	 * @param	string	The language key.
	 * @param	string|array	Parameter or parameter’s list to bind on translation string (optional).
	 */
	public function lang($key, $vars=NULL) {
		
		return $this->translator->get($key, $vars);
		
	}
	
	/**
	 * Returns the object of inherited class when called with id as first parameter.
	 *
	 * @param	string	Expected object class type.
	 * @return	object|NULL
	 */
	protected function getObjectRequestedById($class) {
	
		// reads from url requested item id
		$itemId		= Router::get(0);
		
		if (!$itemId) {
			$this->enqueueError($this->lang('NO_ID_OF_ITEM_TO_EDIT', $class));
			return NULL;
		}
		
		$object = new $class($itemId);
	
		if ($object->isLoaded()) {
				
			return $object;
	
		} else {
	
			$this->enqueueError($this->lang('ID_OF_ITEM_TO_EDIT_IS_NOT_VALID', $class));
			$this->logError('Object ' . $class . ' id=' . $itemId . ' has not been loaded');
			return NULL;
	
		}
	
	}
	
}
