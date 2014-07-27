<?php
/**
 * kate: replace-tabs off; indent-mode Normal; keep-extra-spaces: off; tab-indents: on;
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCA\TAL;


/**
 * This class provides TAL templates for owncloud.
 */
class Template extends \OCP\Template {
	/**
	*/
	protected $_engine = null;
	protected $scripts = array();
	protected $styles = array();
	protected $_headers = array();
	protected $renderas;
	protected $app = '';

	public function __construct($app, $name, $renderas = '') {

		list($version,) = \OCP\Util::getVersion();

		$this->renderas = $renderas;
		$this->i18n = new L10N($app);
		$this->setEngine(new \PHPTAL());
		if($version < 6) {
			parent::__construct($app, $name, $renderas);
		} else {
			// Read the selected theme from the config file
			$theme = \OC_Util::getTheme();
			// Read the detected formfactor and use the right file name.
			$fext = self::getFormFactorExtension();
			$this->getTemplate($theme, $app, $name, $fext);
		}
		$this->app = $app;
		$this->assign('application', $this->app);
		$this->assign('i18n', $this->i18n);
		$this->assign('user', \OCP\User::getUser());
		$user_displayname = \OCP\User::getDisplayName();
		$this->assign('user_displayname', $user_displayname);
		$this->assign('appinfo', \OCP\App::getAppInfo($app));
		$this->assign('appajaxpath', \OC_App::getAppPath($app).'/ajax');
		$this->assign('appjspath', \OC_App::getAppPath($app).'/js');
		$this->assign('apptemplatepath', \OC_App::getAppPath($app).'/templates');
		$this->assign('requesttoken', \OCP\Util::callRegister());
		$request = isset($_REQUEST)?$_REQUEST:array();
		$request['post'] = isset($_POST)?$_POST:array();
		$request['get'] = isset($_GET)?$_GET:array();
		$this->assign('request', $request);
		$this->assign('server', $_SERVER);
		$this->assign('DEBUG', (defined('DEBUG') && DEBUG) ? true : false);
		$this->assign('webroot', \OC::$WEBROOT);
		$this->assign('theme', \OCP\Config::getSystemValue('theme'));
	}

	/**
	* Plug in PHPTAL object into View
	*
	* @name setEngine
	* @access public
	* @param \PHPTAL $engine
	*/
	public function setEngine(\PHPTAL $engine) {
		$view = new \OC\Files\View('/' . \OCP\User::getUser());
		if(!$view->file_exists('phptal')) {
			$view->mkdir('phptal');
		}
		$this->_engine = $engine;
		$this->_engine->setPhpCodeDestination($view->getLocalFile('/phptal/'));
		$this->_engine->setTemplateRepository($_SERVER['DOCUMENT_ROOT'] . \OCP\Util::linkTo($this->app, 'templates'));
		$this->_engine->set('this', $this);
		$this->_engine->setOutputMode(\PHPTAL::HTML5);
		$this->_engine->setTranslator($this->i18n);
		return $this;
	}

	/**
	 * Forces reparsing of all templates all the time. It should be used only for testing and debugging.
	 * It's useful if you're testing pre filters or changing code of PHPTAL itself.
	 * WARNING: This slows down PHPTAL very much. Never enable this on production servers!
	 */
	public function setForceReparse() {
		\OCP\Util::writeLog('tal','ForceReparse is enabled!', \OCP\Util::WARN);
		$this->_engine->setForceReparse();
	}

	/**
	* Get PHPTAL object from View
	*
	* @name getEngine
	* @access public
	* @return \PHPTAL
	*/
	public function getEngine() {
		return $this->_engine;
	}

	/**
	* Clone PHPTAL object
	*
	* @access public
	*/
	public function __clone() {
		$this->_engine = clone $this->_engine;
	}

	/**
	* Display template
	*
	* @access protected
	*/
	protected function _run() {
		$this->_engine->setTemplate(func_get_arg(0));
		try {
			echo $this->_engine->execute();
		} catch (\Exception $e) {
			\OCP\Util::writeLog('tal', __METHOD__ . ', Exception: ' . $e->getMessage(), \OCP\Util::DEBUG);
			throw $e;
		}
	}

	/**
	 * Find the template with the given name
	 *
	 * Will select the template file for the selected theme and formfactor.
	 * Checking all the possible locations.
	 * @param string $name of the template file (without suffix)
	 */
	protected function getTemplate($theme, $app, $name, $fext) {
		// Check if it is a app template or not.
		if( $app !== '' ) {
			$dirs = $this->getAppTemplateDirs($theme, $app, \OC::$SERVERROOT, \OC_App::getAppPath($app));
		} else {
			$dirs = $this->getCoreTemplateDirs($theme, \OC::$SERVERROOT);
		}

		foreach($dirs as $dir) {
			$file = $dir.$name.$fext.'.pt';
			if (is_file($file)) {
				$this->path = $dir;
				$this->template = $file;
				break;
			}
			$file = $dir.$name.'.pt';

			if (is_file($file)) {
				$this->path = $dir;
				$this->template = $file;
				break;
			}
		}

		if(!$this->template) {
			throw new \Exception('template file not found: template:' . $template . ' formfactor:' . $fext);
		}

		$this->_engine->template = $this->template;
		$this->_engine->setTemplate($this->template);

	}

	/**
	 * @brief check Path For Template with and without $fext
	 * @param $path to check
	 * @param $name of the template file (without suffix)
	 * @param $fext formfactor extension
	 * @return bool true when found
	 *
	 * Will set $this->template and $this->path if there is a template at
	 * the specified $path
	 */
	protected function checkPathForTemplate($path, $name, $fext) {
		if ($name =='') {
			return false;
		}
		$template = null;
		if( is_file( $path.$name.$fext.'.pt' )) {
			$template = $path.$name.$fext.'.pt';
		} elseif( is_file( $path.$name.'.pt' )) {
			$template = $path.$name.'.pt';
		}

		if ($template) {
			$this->template = $template;
			$this->path = $path;
			$this->_engine->template = $this->template;
			$this->_engine->setTemplate($this->template);
			return true;
		}
		return false;
	}

	/**
	 * @brief Assign variables
	 * @param $key key
	 * @param $value value
	 * @param $sanitizeHTML Ignored, as values are always sanitized unless explicitly specified not to.
	 * @returns true
	 *
	 * This function assigns a variable. It can be accessed via TALES expressions or ${$key} in
	 * the template.
	 *
	 * If the key existed before, it will be overwritten
	 */
	public function assign($key, $value, $sanitizeHTML = false) {
		$this->_engine->set($key, $value);
		return true;
	}

	/**
	 * @brief Add a custom element to the header
	 * @param string tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 */
	public function addHeader($tag, $attributes, $text='') {
		$this->_headers[] = array(
			'tag' => $tag,
			'attributes' => $attributes,
			'text' => $text
		);
	}

	/**
	 * Prints the proceeded template
	 *
	 * This method proceeds the template and prints its output.
	 *
	 * @returns bool
	 */
	public function printPage() {
		// Some headers to enhance security
		header('X-Frame-Options: Sameorigin');
		header('X-XSS-Protection: 1; mode=block');
		header('X-Content-Type-Options: nosniff');
		// Content Security Policy
		// If you change the standard policy, please also change it in config.sample.php
		$policy = \OCP\Config::getSystemValue('custom_csp_policy',  'default-src \'self\'; script-src \'self\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; frame-src *; img-src *; font-src \'self\' data:');
		header('Content-Security-Policy:'.$policy); // Standard
		header('X-WebKit-CSP:'.$policy); // Older webkit browsers
		echo $this->fetchPage();
	}

	/**
	 * @brief Proceeds the template
	 * @returns content
	 *
	 * This function proceeds the template. If $this->renderas is set, it
	 * will produce a full page.
	 */
	public function fetchPage() {
		$data = $this->_engine->execute();

		if($this->renderas) {
			$page = new \OC_TemplateLayout($this->renderas);
			// Add custom headers
			$page->assign('headers', $this->_headers, false);
			foreach(\OC_Util::$headers as $header) {
				$page->append('headers', $header);
			}

			$page->assign('content', $data, false);
			return $page->fetchPage();
		}
		return $data;
	}

	static function linkTo($src) {
		$parts = is_array($src)?$src:explode('/', rtrim($src));
		if($parts[0] === '') {
			array_shift($parts);
			return \OCP\Util::linkTo('', implode('/', $parts));
		} elseif(count($parts) === 1) {
			return \OCP\Util::linkTo('', implode('/', $parts));
		} elseif(trim($parts[0]) === 'core') {
			array_shift($parts);
			return \OCP\Util::linkTo('', implode('/', $parts));
		} else { // This should be an app.
			return \OCP\Util::linkTo(array_shift($parts), implode('/', $parts));
		}
	}

	static function linkToAbsolute($src) {
		$parts = is_array($src)?$src:explode('/', rtrim($src));
		if($parts[0] === '') {
			array_shift($parts);
			return \OCP\Util::linkToAbsolute('', implode('/', $parts));
		} elseif(count($parts) === 1) {
			return \OCP\Util::linkToAbsolute('', implode('/', $parts));
		} elseif(trim($parts[0]) === 'core') {
			array_shift($parts);
			return \OCP\Util::linkToAbsolute('', implode('/', $parts));
		} else { // This should be an app.
			return \OCP\Util::linkToAbsolute(array_shift($parts), implode('/', $parts));
		}
	}

	static function imagePath($src) {
		$parts = is_array($src)?$src:explode('/', rtrim($src));
		if($parts[0] === '') {
			array_shift($parts);
			return \OCP\Util::imagePath('', implode('/', $parts));
		} elseif(count($parts) === 1) {
			return \OCP\Util::imagePath('', implode('/', $parts));
		} elseif(trim($parts[0]) === 'core') {
			array_shift($parts);
			return \OCP\Util::imagePath('', implode('/', $parts));
		} else { // This should be an app.
			return \OCP\Util::imagePath(array_shift($parts), implode('/', $parts));
		}
	}

	static function config($src) {
		$parts = is_array($src) ? $src : explode('/', rtrim($src));
		if(count($parts) < 2) {
			throw new \PHPTAL_Exception('Wrong argument count: config: takes no less than 2 arguments.');
		} else {
			switch ($parts[0]) {
			    case 'sys':
					return \OCP\Config::getSystemValue($parts[1]);
			        break;
			    case 'app':
					if(count($parts) === 2) {
						return \OCP\Config::getAppValue(self::app, $parts[1]);
					} elseif(count($parts) === 3) {
						return \OCP\Config::getAppValue($parts[1], $parts[2]);
					} else {
						throw new \PHPTAL_Exception('Wrong argument count: config:$app takes no more than 3 arguments.');
					}
			        break;
			    case 'user':
					if(count($parts) === 2) {
						return \OCP\Config::getUserValue(\OCP\User::getUser(), self::app, $parts[1]);
					} elseif(count($parts) === 3) {
						return \OCP\Config::getUserValue(\OCP\User::getUser(), $parts[1], $parts[2]);
					} elseif(count($parts) === 4) {
						return \OCP\Config::getUserValue($parts[1], $parts[2], $parts[3]);
					} else {
						throw new \PHPTAL_Exception('Wrong argument count: config: takes no more than 4 arguments.');
					}
			        break;
			}
		}
	}
}