<?php

namespace Pets;

/**
 * The chicken pet class
 */
class ChickenPet extends Pets {

	/**
	 * The ID of the pet
	 */
	const NETWORK_ID = 10;

	/**
	 * Pet width
	 *
	 * @type float
	 */
	public $width = 0.4;

	/**
	 * Pet height
	 *
	 * @type float
	 */
	public $height = 0.75;

	/**
	 * The name of the pet
	 *
	 * @return string The pet name
	 */
	public function getName() {
		return "ChickenPet";
	}

	/**
	 * The speed of the pet
	 *
	 * @return integer The speed of the pet
	 */
	public function getSpeed() {
		return 1;
	}

}
