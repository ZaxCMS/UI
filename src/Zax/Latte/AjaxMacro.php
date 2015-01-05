<?php

namespace Zax\Latte;

use Zax;
use Latte;
use Nette;

class AjaxMacro extends Nette\Object {

	public function install(Latte\Engine $latte) {
		$set = new Latte\Macros\MacroSet($latte->getCompiler());
		$set->addMacro('ajax', NULL, NULL, [$this, 'macroAjax']);
	}

	public function macroAjax(Latte\MacroNode $node, Latte\PhpWriter $writer) {
		return $writer->write('echo Latte\Runtime\Filters::htmlAttributes(["data-zax-ajax" => $control->isAjaxEnabled()])');
	}

}
