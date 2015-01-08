<?php

namespace Zax\Application\UI;

use Nette;
use Zax\Latte\AjaxMacro;

abstract class Control extends Nette\Application\UI\Control implements IAjaxAware {

	/** @persistent */
	public $view = 'Default';

	/** @var array */
	protected $defaultLinkParams = [];

	/** @var bool */
	protected $ajaxEnabled = FALSE;

	/** @var bool */
	protected $autoAjax = FALSE;

	/** @var array */
	protected $ajaxDisabledFor = [];

	/**
	 * Enables AJAX for this component and all sub-components (if they implement IAjaxAware)
	 *
	 * @param bool $autoAjax Should call redrawControl() as soon as the component gets attached to presenter?
	 * @param array $exclude array of subcomponent names which should be excluded from AJAXification
	 * @return $this
	 */
	public function enableAjax($autoAjax = TRUE, array $exclude = NULL) {
		$this->ajaxEnabled = TRUE;
		$this->autoAjax = $autoAjax;
		if(is_array($exclude)) {
			$this->disableAjaxFor($exclude);
		}
		return $this;
	}

	/**
	 * Forces AJAX off on specified subcomponents (has higher priority than enableAjax())
	 *
	 * @param array $subcomponents array of subcomponent names which should be excluded from AJAXification
	 * @return $this
	 */
	public function disableAjaxFor(array $subcomponents = []) {
		$this->ajaxDisabledFor = $subcomponents;
		return $this;
	}

	/**
	 * Disables AJAX for this component.
	 * Do not call in factory, it won't work, use disableAjaxFor in parent component instead
	 *
	 * @return $this
	 */
	public function disableAjax() {
		$this->ajaxEnabled = FALSE;
		$this->autoAjax = FALSE;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isAjaxEnabled() {
		return $this->ajaxEnabled;
	}

	/**
	 * Does this control have a persistent property $property?
	 *
	 * @param $property
	 * @return bool
	 */
	protected function hasPersistentProperty($property) {
		$ref = $this->getReflection();
		if($ref->hasProperty($property)) {
			$refp = $ref->getProperty($property);
			return $refp->isPublic() && $refp->hasAnnotation('persistent');
		}
		return FALSE;
	}

	/**
	 * Forward using $presenter->forward() (doesn't work well with redrawControl)
	 *
	 * @param string $destination
	 * @param array $args
	 */
	public function presenterForward($destination, $args = []) {

		// Get full path
		$name = $this->getUniqueId();
		if($destination !== 'this') {
			$destination = "$name-$destination";
		}

		// Prepend full path to param keys
		$params = [];
		foreach($args as $key => $val) {
			$params["$name-$key"] = $val;
		}

		$this->presenter->forward($destination, $params);
	}

	/**
	 * This method gets called during forward().
	 * You can override it if you want to eg. send url and/or anchor in payload in AJAX requests.
	 *
	 * @param string $url
	 * @param string|NULL $anchor
	 */
	protected function processDestination($url, $anchor = NULL) {

	}

	/**
	 * Custom "hacky" forward (works well with redrawControl)
	 *
	 * @param string $destination
	 * @param array $args
	 */
	public function forward($destination, $args = []) {

		// Remove '!' from destination
		$destination = str_replace('!', '', $destination);

		// Remove anchor from destination
		$anchor = strpos($destination, '#');
		if(is_int($anchor)) {
			list($destination, $anchor) = explode('#', $destination);
		} else {
			$anchor = NULL;
		}
		$this->processDestination($this->link($destination, $args), $anchor);

		// Process arguments
		$params = [];
		foreach($args as $key => $param) {
			$control = $this;
			$property = $key;

			// Get subcomponent from name
			if(strpos($key, self::NAME_SEPARATOR) > 0) {
				$names = explode(self::NAME_SEPARATOR, $key);
				$property = array_pop($names);
				$control = $this->getComponent(implode(self::NAME_SEPARATOR, $names));
			}

			if($property === 'view') {
				$control->setView($param);
			}else if($control->hasPersistentProperty($property)) {
				$control->$property = $param;
			} else {
				$params[$key] = $param;
			}
		}
		$this->params = $params;

		if($destination === 'this')
			return; // Definitely not a signal

		$this->signalReceived($destination);
	}

	/**
	 * If AJAX forward, else redirect
	 *
	 * @param string $destination
	 * @param array $args
	 * @param array $snippets
	 * @param bool $presenterForward Prefer $presenter->forward() over $this->forward()
	 */
	final public function go($destination, $args = [], $snippets = [], $presenterForward = FALSE) {
		if($this->ajaxEnabled && $this->presenter->isAjax()) {
			foreach($snippets as $snippet) {
				$this->redrawControl($snippet);
			}

			if($presenterForward) {
				$this->presenterForward($destination, $args);
			} else {
				$this->forward($destination, $args);
			}
		} else {
			$this->redirect($destination, $args);
		}
	}

	/**
	 * @param string $view
	 * @return string
	 */
	static public function formatViewMethod($view) {
		return 'view' . Nette\Utils\Strings::firstUpper($view);
	}

	/**
	 * @param string $render
	 * @return string
	 */
	static public function formatBeforeRenderMethod($render) {
		return 'beforeRender' . Nette\Utils\Strings::firstUpper($render);
	}

	/**
	 * @param string $view
	 */
	public function setView($view) {
		$this->view = Nette\Utils\Strings::firstUpper($view);
	}

	/**
	 * Throws exception if view name contains anything else than alphanumeric characters.
	 *
	 * @param string $view
	 * @throws Nette\Application\UI\BadSignalException
	 */
	protected function checkView($view) {
		if(!preg_match('/^([a-zA-Z0-9]+)$/', $view)) {
			throw new Nette\Application\UI\BadSignalException("Signal or view name must be alphanumeric.");
		}
	}

	/**
	 * Automatic snippet invalidation
	 * Do not call manually
	 */
	public function attached($presenter) {
		parent::attached($presenter);

		$this->startup();

		if($this->ajaxEnabled && $this->autoAjax && $presenter instanceof Nette\Application\UI\Presenter && $presenter->isAjax()) {
			$this->redrawControl();
		}
	}

	/**
	 * Useful for cases where you don't want automatic AJAX to kick in.
	 */
	protected function redrawNothing() {
		foreach($this->getPresenter()->getComponents(TRUE, 'Nette\Application\UI\IRenderable') as $component) {
			$component->redrawControl(NULL, FALSE);
		}
	}

	/**
	 * @param string $destination
	 * @param array $args
	 * @return string
	 */
	public function link($destination, $args = []) {
		return parent::link($destination, array_merge($this->defaultLinkParams, $args));
	}

	/**
	 * @param string $view
	 * @param string $render
	 * @return string
	 */
	static public function formatTemplatePath($view, $render = '') {
		return 'templates' . DIRECTORY_SEPARATOR . $view . (strlen($render) > 0 ? '.' . $render : '') . '.latte';
	}

	/**
	 * @param string $view
	 * @param string $render
	 * @return string
	 */
	public function getTemplatePath($view, $render = '') {
		$class = $this->reflection;
		do { // Template inheritance.. kinda..
			$path = dirname($class->fileName) . DIRECTORY_SEPARATOR . self::formatTemplatePath($view, $render);
			if(is_file($path)) {
				return $path;
			}
			$class = $class->getParentClass();
		} while ($class !== NULL);
	}

	/**
	 * Template factory
	 *
	 * @return Nette\Application\UI\ITemplate
	 */
	public function createTemplate() {
		$this->checkView($this->view);
		$template = parent::createTemplate();
		if(class_exists('Latte\Engine')) {
			(new AjaxMacro)->install($template->getLatte());
		}
		return $template;
	}

	/**
	 * @param string $name
	 * @param Nette\ComponentModel\IComponent $control
	 */
	protected function prepareComponent($name, Nette\ComponentModel\IComponent $control) {
		if($this->ajaxEnabled && !in_array($name, $this->ajaxDisabledFor) && ($control instanceof IAjaxAware)) {
			$control->enableAjax();
		}
	}

	/**
	 * @param string $name
	 * @return Nette\ComponentModel\IComponent
	 */
	protected function createComponent($name) {
		$control = parent::createComponent($name);
		$this->prepareComponent($name, $control);
		return $control;
	}

	public function startup() {}

	/**
	 * Life cycle
	 *
	 * @param string $render
	 * @param array $renderParams
	 * @throws \Nette\Application\UI\BadSignalException
	 */
	final public function run($render = '', $renderParams = []) {

		$template = $this->getTemplate();
		$template->setFile($this->getTemplatePath($this->view, $render));

		// view
		if(!$this->tryCall(self::formatViewMethod($this->view), $this->params)) {
			$class = get_class($this);
			throw new Nette\Application\UI\BadSignalException("There is no handler for view '$this->view' in class $class.");
		}

		// beforeRender
		if(!$this->tryCall(self::formatBeforeRenderMethod($render), $renderParams)) {
			$class = get_class($this);
			throw new Nette\Application\UI\BadSignalException("There is no 'beforeRender$render' method in class $class.");
		}

		$template->render();
	}

	/**
	 * Render method hook
	 *
	 * @param string $func
	 * @param array $args
	 * @return mixed|void
	 */
	public function __call($func, $args = []) {
		if (Nette\Utils\Strings::startsWith($func, 'render')) {

			// Fix array-in-array when passing parameters from template
			// See http://forum.nette.org/cs/21090-makro-control-obaluje-pojmenovane-parametry-polem (in czech)
			$tmp = @array_reduce($args, 'array_merge', []); // @ - intentionally
			if($tmp === NULL) {
				$tmp = $args;
			}

			return $this->run(Nette\Utils\Strings::substring($func, 6), $tmp);
		}
		return parent::__call($func, $args);
	}

}
