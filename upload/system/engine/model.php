<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2022, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Model class
*/
abstract class Model {
	protected $registry;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get($key) {
		if ($this->registry->has($key)) {
			return $this->registry->get($key);
		} else {
			throw new \Exception('Error: Could not call registry key ' . $key . '!');
		}
	}

	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
}