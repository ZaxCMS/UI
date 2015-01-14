<?php

namespace Zax\Application\UI;

interface IHasControlLifeCycle {

	public function run($render = '', $renderParams = []);

}
