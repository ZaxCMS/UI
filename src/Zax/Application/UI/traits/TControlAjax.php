<?php

namespace Zax\Application\UI;

use Nette;
use Zax\Latte\AjaxMacro;

trait TControlAjax {

	use TControlForward;

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
	 * Automatic snippet invalidation
	 * Do not call manually
	 */
	public function attached($presenter) {
		parent::attached($presenter);

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
	 * @param string $name
	 * @return Nette\ComponentModel\IComponent
	 */
	protected function createComponent($name) {
		$control = parent::createComponent($name);
		if($this->ajaxEnabled && !in_array($name, $this->ajaxDisabledFor) && ($control instanceof IAjaxAware)) {
			$control->enableAjax();
		}
		return $control;
	}

	/**
	 * If AJAX, then forward, else redirect.
	 *
	 * @param string $destination
	 * @param array $args
	 * @param array $snippets Not needed if we only have one unnamed snippet
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

}
