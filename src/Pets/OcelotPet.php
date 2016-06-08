<?php

namespace Pets;

class OcelotPet extends Pets {

	const NETWORK_ID = 22;

    public $width = 0.72;
    public $height = 0.9;

	public function getName() {
		return "OcelotPet";
	}

	public function getSpeed() {
		return 1.4;
	}

}
