<?php

namespace Zax\DI;

use Nette\DI\CompilerExtension;

class AjaxExtension extends CompilerExtension {

	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();

		$def = $builder->hasDefinition('nette.latteFactory')
			? $builder->getDefinition('nette.latteFactory')
			: $builder->getDefinition('nette.latte');

		$def->addSetup('Zax\Latte\AjaxMacro::install(?->getCompiler())', ['@self']);
	}

}
