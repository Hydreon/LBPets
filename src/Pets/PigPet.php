<?php

namespace Pets;

/**
 * The pig pet class
 */
class PigPet extends Pets {

	/**
	 * The ID of the pet
	 */
	const NETWORK_ID = 12;

	/**
	 * Pet width
	 *
	 * @type float
	 */
    public $width = 1.45;

	/**
	 * Pet height
	 *
	 * @type float
	 */
    public $height = 1.12;

	/**
	 * The name of the pet
	 *
	 * @return string The pet name
	 */
	public function getName() {
		return "PigPet";
	}

	/**
	 * The speed of the pet
	 *
	 * @return integer The speed of the pet
	 */
	public function getSpeed() {
		return 1.1;
	}

}
