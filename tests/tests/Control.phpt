<?php

namespace Zax\Tests;

use Nette;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

class ControlTest extends Tester\TestCase {

	/** @var Nette\DI\Container */
	protected $container;

	/** @var Nette\Application\UI\Presenter */
	protected $presenter;

	/** @var TestControl */
	protected $control;

	public function __construct(Nette\DI\Container $container) {
		$this->container = $container;
		$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');
		$this->presenter = $presenterFactory->createPresenter('Test');
		$this->presenter->run(new Nette\Application\Request('Test', 'get', []));
	}

	public function setUp() {
		if($this->control !== NULL ){
			$this->presenter->removeComponent($this->control);
		}
		$this->control = $this->presenter->getComponent('testControl');
	}

	public function testViewNotExist() {
		$this->control->view = 'abc';
		Assert::exception(function() {$this->control->render();}, 'Zax\Application\UI\InvalidViewException');
	}

	public function testInvalidViewName() {
		$this->control->view = 'abc.';
		Assert::exception(function() {$this->control->render();}, 'Zax\Application\UI\InvalidViewNameException');
	}

	public function testBeforeRenderNotExist() {
		$this->control->view = 'foo';
		Assert::exception(function() {$this->control->renderBad();}, 'Zax\Application\UI\InvalidRenderException');
	}

	public function testTemplateRender() {
		$this->control->view = 'Default';

		ob_start();
		$this->control->render();
		Assert::same('default', ob_get_clean());

		ob_start();
		$this->control->renderBar();
		Assert::same('default bar', ob_get_clean());

		$this->control->view = 'Foo';

		ob_start();
		$this->control->render();
		Assert::same('foo', ob_get_clean());

		ob_start();
		$this->control->renderBar();
		Assert::same('foo bar', ob_get_clean());
	}

	public function testAjaxRecursive() {
		$this->control->enableAjax();
		$control = $this->control;
		for($i=0;$i<100;$i++) {
			Assert::true($control->isAjaxEnabled());
			$control = $control->getComponent('testControl');
		}
	}

	public function testAjaxMultiplier() {
		$this->control->enableAjax();
		for($i=0;$i<100;$i++) {
			Assert::true($this->control->getComponent('multipliedTestControl-' . $i)->isAjaxEnabled());
			Assert::true($this->control->getComponent('multipliedTestControl-' . $i . '-testControl')->isAjaxEnabled());
		}
	}

	public function testDisableAjaxFor() {
		$this->control->enableAjax();
		$this->control[str_repeat('testControl-', 49) . 'testControl']
			->disableAjaxFor(['testControl']);
		$control = $this->control;
		for($i=0;$i<100;$i++) {
			if($i>50) {
				Assert::false($control->isAjaxEnabled());
			} else {
				Assert::true($control->isAjaxEnabled());
			}
			$control = $control->getComponent('testControl');
		}
	}

	public function testLatteAjaxMacro() {
		$this->control->view = 'Link';

		ob_start();
		$this->control->render();
		Assert::same('<a href="#">link</a>', ob_get_clean());

		$this->control->enableAjax();
		ob_start();
		$this->control->render();
		Assert::same('<a href="#" data-zax-ajax>link</a>', ob_get_clean());
	}

	public function testForward() {

		$this->control->forward('this', ['view' => 'Foo']);
		ob_start();
		$this->control->render();
		Assert::same('foo', ob_get_clean());
		Assert::null($this->control->testPersistentParam);
		Assert::false($this->control->testSignalReceived);

		$params = ['testPersistentParam' => 42, 'testControl-testPersistentParam' => 43];
		$this->control->forward('testSignal!', $params);

		Assert::true($this->control->testSignalReceived);
		Assert::equal(42, $this->control->testPersistentParam);
		Assert::equal(43, $this->control->getComponent('testControl')->testPersistentParam);
	}

	private function tp($name) {
		return dirname($this->control->reflection->fileName) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $name . '.latte';
	}

}

$test = new ControlTest($container);
$test->run();
