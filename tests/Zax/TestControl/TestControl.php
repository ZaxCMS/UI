<?php

namespace Zax\Tests;

use Zax\Application\UI\Control;
use Zax\Application\UI\Multiplier;

class TestControl extends Control {

	protected $testControlFactory;

	public $testSignalReceived = FALSE;

	/** @persistent */
	public $testPersistentParam;

	public function __construct(ITestControlFactory $testControlFactory) {
		$this->testControlFactory = $testControlFactory;
	}

	protected function createComponentTestControl() {
		return $this->testControlFactory->create();
	}

	protected function createComponentMultipliedTestControl() {
		return new Multiplier(function($id) {
			return $this->testControlFactory->create();
		});
	}

	public function viewDefault() {}

	public function viewFoo() {}

	public function beforeRender() {}

	public function beforeRenderBar() {}

	public function viewLink() {}

	public function handleTestSignal() {
		$this->testSignalReceived = TRUE;
	}

}

interface ITestControlFactory {

	/** @return TestControl */
	public function create();

}
