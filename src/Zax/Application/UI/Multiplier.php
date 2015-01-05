<?php

namespace Zax\Application\UI;

use Nette;

class Multiplier extends Nette\Application\UI\Multiplier implements IAjaxAware {

	protected $ajaxEnabled = FALSE;

	public function enableAjax() {
		$this->ajaxEnabled = TRUE;
		return $this;
	}

	protected function createComponent($name) {
		$control = parent::createComponent($name);
		if($this->ajaxEnabled && $control instanceof IAjaxAware) {
			$control->enableAjax();
		}
		return $control;
	}

}
