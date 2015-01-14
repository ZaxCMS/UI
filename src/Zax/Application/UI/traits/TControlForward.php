<?php

namespace Zax\Application\UI;

use Nette;

trait TControlForward {

	/**
	 * Forward using $presenter->forward() (doesn't work well with redrawControl)
	 *
	 * @param string $destination
	 * @param array $args
	 */
	public function presenterForward($destination, $args = []) {

		// Get full path
		$name = $this->getUniqueId();
		if($destination !== 'this') {
			$destination = "$name-$destination";
		}

		// Prepend full path to param keys
		$params = [];
		foreach($args as $key => $val) {
			$params["$name-$key"] = $val;
		}

		$this->getPresenter()->forward($destination, $params);
	}

	/**
	 * This method gets called during forward().
	 * You can override it if you want to eg. send url and/or anchor in payload in AJAX requests.
	 *
	 * @param string $url
	 * @param string|NULL $anchor
	 */
	protected function processDestination($url, $anchor = NULL) {

	}

	/**
	 * Custom "hacky" forward (works well with redrawControl)
	 *
	 * @param string $destination
	 * @param array $args
	 */
	public function forward($destination, $args = []) {

		// Remove '!' from destination
		$destination = str_replace('!', '', $destination);

		// Remove anchor from destination
		$anchor = strpos($destination, '#');
		if(is_int($anchor)) {
			list($destination, $anchor) = explode('#', $destination);
		} else {
			$anchor = NULL;
		}
		$this->processDestination($this->link($destination, $args), $anchor);

		// Process arguments
		$params = [];
		foreach($args as $key => $param) {
			$control = $this;
			$property = $key;

			// Get subcomponent from name
			if(strpos($key, self::NAME_SEPARATOR) > 0) {
				$names = explode(self::NAME_SEPARATOR, $key);
				$property = array_pop($names);
				$control = $this->getComponent(implode(self::NAME_SEPARATOR, $names));
			}

			if($control->hasPersistentProperty($property)) {
				$control->$property = $param;
			} else {
				$params[$key] = $param;
			}
		}
		$this->params = $params;

		if($destination === 'this')
			return; // Definitely not a signal

		$this->signalReceived($destination);
	}

	/**
	 * Does this control have a persistent property $property?
	 *
	 * @param $property
	 * @return bool
	 */
	protected function hasPersistentProperty($property) {
		$ref = $this->getReflection();
		if($ref->hasProperty($property)) {
			$refp = $ref->getProperty($property);
			return $refp->isPublic() && $refp->hasAnnotation('persistent');
		}
		return FALSE;
	}

}
