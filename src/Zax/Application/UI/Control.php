<?php

namespace Zax\Application\UI;

use Nette;

abstract class Control extends Nette\Application\UI\Control implements IAjaxAware, IHasControlLifeCycle {

	use TControlLifeCycle;
	use TControlMergeLinkParams;
	use TControlAjax;

	/**
	 * @param string $view
	 * @param string $render
	 * @return string
	 */
	static protected function formatTemplatePath($view, $render = '') {
		return 'templates' . DIRECTORY_SEPARATOR . $view . (strlen($render) > 0 ? '.' . $render : '') . '.latte';
	}

	/**
	 * @param string $view
	 * @param string $render
	 * @return string
	 * @throws Nette\InvalidStateException
	 */
	protected function getTemplatePath($view, $render = '') {
		$class = $this->reflection;

		do { // Template inheritance.. kinda..
			$path = dirname($class->fileName) . DIRECTORY_SEPARATOR . self::formatTemplatePath($view, $render);
			if(is_file($path)) {
				return $path;
			}
			$class = $class->getParentClass();
		} while ($class->getName() !== 'Zax\Application\UI\Control');

		$currentClass = get_class($this);
		throw new Nette\InvalidStateException("Template for view '$view' in control '$currentClass' was not found.");
	}

	/**
	 * Life cycle
	 *
	 * @param string $render
	 * @param array $renderParams
	 * @throws Nette\Application\UI\BadSignalException
	 */
	final public function run($render = '', $renderParams = []) {
		$template = $this->getTemplate();
		$template->setFile($this->getTemplatePath($this->view, $render));
		$template->render();
	}

}
