<?php

namespace Zax\Tests;

use Nette\Application\UI\Presenter;

class TestPresenter extends Presenter {

	protected $testControlFactory;

	public function __construct(ITestControlFactory $testControlFactory) {
		$this->testControlFactory = $testControlFactory;
	}

	protected function createComponentTestControl() {
	    return $this->testControlFactory->create();
	}

}
