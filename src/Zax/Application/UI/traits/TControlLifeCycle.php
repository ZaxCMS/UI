<?php

namespace Zax\Application\UI;

use Nette;

trait TControlLifeCycle {

	/** @persistent */
	public $view = 'Default';

	/**
	 * @param string $view
	 * @return string
	 */
	static protected function formatViewMethod($view) {
		return 'view' . Nette\Utils\Strings::firstUpper($view);
	}

	/**
	 * @param string $render
	 * @return string
	 */
	static protected function formatBeforeRenderMethod($render) {
		return 'beforeRender' . Nette\Utils\Strings::firstUpper($render);
	}

	/**
	 * @param string $render
	 * @param array $renderParams
	 * @throws InvalidRenderException
	 * @throws InvalidViewException
	 */
	protected function callViewRender($render = '', $renderParams = []) {

		// call view*()
		if(!$this->tryCall(self::formatViewMethod($this->view), $this->params)) {
			$class = get_class($this);
			throw new InvalidViewException("There is no handler for view '$this->view' in class $class.");
		}

		// call beforeRender*()
		if(!$this->tryCall(self::formatBeforeRenderMethod($render), $renderParams)) {
			$class = get_class($this);
			throw new InvalidRenderException("There is no 'beforeRender$render' method in class $class.");
		}

	}

	/**
	 * Throws exception if view name contains anything else than alphanumeric characters.
	 *
	 * @throws InvalidViewNameException
	 */
	protected function checkView() {
		if(!preg_match('/^([a-zA-Z0-9]+)$/', $this->view)) {
			throw new InvalidViewNameException("Signal or view name must be alphanumeric.");
		}
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

			// Capitalize and validate view syntax
			$this->view = Nette\Utils\Strings::firstUpper($this->view);
			$this->checkView();

			// Call view and render methods
			$render = Nette\Utils\Strings::substring($func, 6);
			$this->callViewRender($render, $tmp);

			// Life cycle
			if($this instanceof IHasControlLifeCycle) {
				return $this->run($render, $tmp);
			}
		}
		return parent::__call($func, $args);
	}

}
