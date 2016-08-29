<?php

namespace Pets;

use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\Server;

/**
 * The pet manager
 */
class PetsManager implements Listener {

	/**
	 * The constructor, registers entities
	 *
	 * @param LBPets\Main $plugin The main plugin
	 */
	public function __construct($plugin) {
		$server = Server::getInstance();

		Entity::registerEntity(ChickenPet::class);
		Entity::registerEntity(WolfPet::class);
		Entity::registerEntity(PigPet::class);
	}

	/**
	 * Creates the pet
	 *
	 * @param  string   $type   The type of the entity
	 * @param  Position $source The position
	 * @param  array   $args    Extra arguments
	 * @return Entity           The entity
	 */
	public static function create($type, Position $source, ...$args) {
		$chunk = $source->getLevel()->getChunk($source->x >> 4, $source->z >> 4, true);

		$nbt = new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $source->x),
				new DoubleTag("", $source->y),
				new DoubleTag("", $source->z)
					]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
					]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", $source instanceof Location ? $source->yaw : 0),
				new FloatTag("", $source instanceof Location ? $source->pitch : 0)
					]),
		]);
		return Entity::createEntity($type, $chunk, $nbt, ...$args);
	}

	/**
	 * Triggers the pet creation
	 *
	 * @param  Player $player   The player to spawn the pet to
	 * @param  string $type     The type of the pet
	 * @param  string $holdType The holding type
	 * @return Pets             The pet
	 */
	public static function createPet($player, $type = "", $holdType = "") {
		$len = rand(8, 12);
		$x = (-sin(deg2rad($player->yaw))) * $len  + $player->getX();
		$z = cos(deg2rad($player->yaw)) * $len  + $player->getZ();
		$y = $player->getLevel()->getHighestBlockAt($x, $z);

		$source = new Position($x , $y + 2, $z, $player->getLevel());
		if (empty($type)) {
			$pets = array("ChickenPet", "PigPet", "WolfPet");
			$type = $pets[rand(0, 2)];
		}
		if (!empty($holdType)) {
			$pets = array("ChickenPet", "PigPet", "WolfPet");
			foreach ($pets as $key => $petType) {
				if($petType == $holdType) {
					unset($pets[$key]);
					break;
				}
			}
			$type = $pets[array_rand($pets)];
		}
		$pet = self::create($type, $source);
		$pet->setOwner($player);
		$pet->spawnToAll();

		return $pet;
	}
}
