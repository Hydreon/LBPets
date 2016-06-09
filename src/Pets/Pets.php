<?php

namespace Pets;

use pocketmine\Player;
use pocketmine\math\Math;
use pocketmine\block\Air;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\block\Liquid;
use pocketmine\entity\Creature;
use pocketmine\network\protocol\AddEntityPacket;

/**
 * The main class for a pet
 */
abstract class Pets extends Creature {

	/**
	 * The owner of the plugin
	 *
	 * @type null
	 */
	protected $owner = null;

	/**
	 * The distance to the owner
	 *
	 * @type integer
	 */
	protected $distanceToOwner = 0;

	/**
	 * The target to the pet
	 *
	 * @type null
	 */
	protected $closeTarget = null;

	/**
	 * Sets the owner of the plugin
	 *
	 * @param Player $player The player to set the player for
	 */
	public function setOwner(Player $player) {
		$this->owner = $player;
	}

	/**
	 * Player to spawn the pet to
	 *
	 * @param  Player $player The player to spawn to
	 * @return null
	 */
	public function spawnTo(Player $player) {
		if(!$this->closed && $player->spawned && $player->dead !== true) {
			if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
				$pk = new AddEntityPacket();
				$pk->eid = $this->getID();
				$pk->type = static::NETWORK_ID;
				$pk->x = $this->x;
				$pk->y = $this->y;
				$pk->z = $this->z;
				$pk->speedX = 0;
				$pk->speedY = 0;
				$pk->speedZ = 0;
				$pk->yaw = $this->yaw;
				$pk->pitch = $this->pitch;
				$pk->metadata = $this->dataProperties;
				$player->dataPacket($pk);

				$this->hasSpawned[$player->getId()] = $player;
			}
		}
	}

	/**
	 * Update the movement of the pet
	 *
	 * @return null
	 */
	public function updateMovement() {
		if ($this->lastX !== $this->x || $this->lastY !== $this->y || $this->lastZ !== $this->z || $this->lastYaw !== $this->yaw || $this->lastPitch !== $this->pitch) {
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;
			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;
		}

		$this->level->addEntityMovement($this->getViewers(), $this->getId(), $this->x, $this->y, $this->z, $this->yaw, $this->pitch);
	}

	/**
	 * Moves the pet
	 *
	 * @param  integer $dx The x value
	 * @param  integer $dy The y value
	 * @param  integer $dz The z value
	 * @return boolean Whether the move was successful
	 */
	public function move($dx, $dy, $dz) {
		$this->boundingBox->offset($dx, 0, 0);
		$this->boundingBox->offset(0, 0, $dz);
		$this->boundingBox->offset(0, $dy, 0);
		$this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);

		return true;
	}

	/**
	 * The speed of the pet
	 *
	 * @return integer The speed of the pet
	 */
	public function getSpeed() {
		return 1;
	}

	/**
	 * Updates the movement of the pet
	 *
	 * @return null
	 */
	public function updateMove() {
		if(is_null($this->closeTarget)) {
			$x = $this->owner->x - $this->x;
			$z = $this->owner->z - $this->z;
		} else {
			$x = $this->closeTarget->x - $this->x;
			$z = $this->closeTarget->z - $this->z;
		}
		if ($x ** 2 + $z ** 2 < 4) {
			$this->motionX = 0;
			$this->motionZ = 0;
			$this->motionY = 0;
			if(!is_null($this->closeTarget)) {
				$this->close();
			}
			return;
		} else {
			$diff = abs($x) + abs($z);
			$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
			$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
		}
		$this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
		if(is_null($this->closeTarget)) {
			$y = $this->owner->y - $this->y;
		} else {
			$y = $this->closeTarget->y - $this->y;
		}
		$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		$dx = $this->motionX;
		$dz = $this->motionZ;
		$newX = Math::floorFloat($this->x + $dx);
		$newZ = Math::floorFloat($this->z + $dz);
		$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y), $newZ));
		if (!($block instanceof Air) && !($block instanceof Liquid)) {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y + 1), $newZ));
			if (!($block instanceof Air) && !($block instanceof Liquid)) {
				$this->motionY = 0;
				if(is_null($this->closeTarget)) {
					$this->returnToOwner();
					return;
				}
			} else {
				if (!$block->canBeFlowedInto) {
					$this->motionY = 1.1;
				} else {
					$this->motionY = 0;
				}
			}
		} else {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y - 1), $newZ));
			if (!($block instanceof Air) && !($block instanceof Liquid)) {
				$blockY = Math::floorFloat($this->y);
				if ($this->y - $this->gravity * 4 > $blockY) {
					$this->motionY = -$this->gravity * 4;
				} else {
					$this->motionY = ($this->y - $blockY) > 0 ? ($this->y - $blockY) : 0;
				}
			} else {
				$this->motionY -= $this->gravity * 4;
			}
		}
		$dy = $this->motionY;
		$this->move($dx, $dy, $dz);
		$this->updateMovement();
	}

	/**
	 * Updates the pet
	 *
	 * @param  integer $currentTick The current tick
	 * @return boolean              Whether or not the update was true
	 */
	public function onUpdate($currentTick) {
		if(!($this->owner instanceof Player) || $this->owner->closed) {
			$this->fastClose();
			return false;
		}
		if($this->closed){
			return false;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		$this->lastUpdate = $currentTick;
		if (is_null($this->closeTarget) && $this->distance($this->owner) > 40) {
			$this->returnToOwner();
		}
		$this->entityBaseTick($tickDiff);
		$this->updateMove();
		$this->checkChunks();

		return true;
	}

	/**
	 * Returns the pet to the owner
	 *
	 * @return null
	 */
	public function returnToOwner() {
		$len = rand(2, 6);
		$x = (-sin(deg2rad( $this->owner->yaw))) * $len  +  $this->owner->getX();
		$z = cos(deg2rad( $this->owner->yaw)) * $len  +  $this->owner->getZ();
		$this->x = $x;
		$this->y = $this->owner->getY() + 1;
		$this->z = $z;
	}

	/**
	 * Close the pet
	 *
	 * @return null
	 */
	public function fastClose() {
		parent::close();
	}

	/**
	 * Close the pet
	 *
	 * @return null
	 */
	public function close(){
		if(!($this->owner instanceof Player) || $this->owner->closed) {
			$this->fastClose();
			return;
		}
		if(is_null($this->closeTarget)) {
			$len = rand(12, 15);
			$x = (-sin(deg2rad( $this->owner->yaw + 20))) * $len  +  $this->owner->getX();
			$z = cos(deg2rad( $this->owner->yaw + 20)) * $len  +  $this->owner->getZ();
			$this->closeTarget = new Vector3($x, $this->owner->getY() + 1, $z);
		}
	}

	/**
	 * Get teh time interval
	 *
	 * @param  string $started The started time
	 * @return integer         The time value
	 */
	public static function getTimeInterval($started) {
		return round((strtotime(date('Y-m-d H:i:s')) - strtotime($started)) /60);
	}

}
