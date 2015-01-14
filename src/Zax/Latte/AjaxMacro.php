<?php

namespace Zax\Latte;

use Zax;
use Latte;
use Nette;

class AjaxMacro extends Latte\Macros\MacroSet {

	public static function install(Latte\Compiler $compiler) {
		$self = new static($compiler);
		$self->addMacro('ajax', NULL, NULL, [$self, 'macroAjax']);
	}

	public function macroAjax(Latte\MacroNode $node, Latte\PhpWriter $writer) {
		return $writer->write('echo Latte\Runtime\Filters::htmlAttributes(["data-zax-ajax" => $control->isAjaxEnabled()])');
	}

}
