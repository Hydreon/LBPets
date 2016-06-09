<?php

namespace Pets;

/**
 * The ocelot pet class
 */
class OcelotPet extends Pets {

	/**
	 * The ID of the pet
	 */
	const NETWORK_ID = 22;

	/**
	 * Pet width
	 *
	 * @type float
	 */
    public $width = 0.72;

	/**
	 * Pet height
	 *
	 * @type float
	 */
    public $height = 0.9;

	/**
	 * The name of the pet
	 *
	 * @return string The pet name
	 */
	public function getName() {
		return "OcelotPet";
	}

	/**
	 * The speed of the pet
	 *
	 * @return integer The speed of the pet
	 */
	public function getSpeed() {
		return 1.4;
	}

}
