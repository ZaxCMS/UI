<?php

namespace Zax\Application\UI;

use Nette;

trait TControlMergeLinkParams {

	/** @var array */
	protected $defaultLinkParams = [];

	/**
	 * @param string $destination
	 * @param array $args
	 * @return string
	 */
	public function link($destination, $args = []) {
		return parent::link($destination, array_merge($this->defaultLinkParams, $args));
	}

}
