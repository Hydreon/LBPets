<?php

namespace LBPets;

use Pets\PetsManager;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerRespawnEvent;

/**
 * Main pets plugin class
 */
class Main extends PluginBase implements Listener {

    /**
     * An array of players mapped to the type of Pet
     *
     * @type array
     */
    public $players = [];

    /**
     * An array of pets mapped to a player
     *
     * @type array
     */
    public $pets = [];

    /**
     * Loads the plugin
     *
     * @return null
     */
    public function onLoad() {
        $this->getLogger()->info(TextFormat::WHITE . "Loaded");
    }

    /**
     * Enables the plugin
     *
     * @return null
     */
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->reloadConfig();

        /**
         * Disable the plugin if it's disabled in the plugin
         */
        if($this->getConfig()->get('pets') == false) {
            $this->setEnabled(false);
            return;
        }

        /**
         * Initalize the PetsManager
         * @type PetsManager
         */
        $this->manager = new PetsManager($this);

        /**
         * Load users that have a pet from the config
         */
        foreach($this->getConfig()->get('users') as $user => $pet) {
            $this->players[$user] = $pet;
        }

        $this->getLogger()->info(TextFormat::DARK_GREEN . "Enabled");
    }

    /**
     * Handles the commands sent to the plugin
     *
     * @param  CommandSender $sender  The person issuing the command
     * @param  Command       $command The command object
     * @param  string        $label   The command label
     * @param  array         $args    An array of arguments
     * @return boolean                True allows the command to go through, false sends an error
     */
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        $subcommand = strtolower(array_shift($args));
        switch ($subcommand) {
            case "give";
                if(count($args) < 1){
                    array_unshift($args, $sender->getDisplayName());
                }

                /**
                 * Check perms, then give pet
                 */
                if (!$this->getConfig()->get('onlyOp') || $sender->hasPermission("lbpets")) {
                    if($this->givePet(...$args)) {
                        $sender->sendMessage(TextFormat::BLUE . '[LBPets] ' . $args[0] . ' has a new pet!');
                    } else {
                        $sender->sendMessage(TextFormat::RED . '[LBPets] Unable to give ' . $args[0] . ' a new pet!');
                    }
                    return true;
                }

                $sender->sendMessage(TextFormat::RED . "[LBPets] You don't have permissions to do that...");
                return true;
            case "remove":
                if(count($args) < 1){
                    array_unshift($args, $sender->getDisplayName());
                }

                /**
                 * Check perms, then remove pet
                 */
                if (!$this->getConfig()->get('onlyOp') || $sender->hasPermission("lbpets")) {
                    $args[] = true;
                    if ($this->removePet(...$args)) {
                        $sender->sendMessage(TextFormat::BLUE . '[LBPets] ' . $args[0] . '\'s pet was removed!');
                    } else {
                        $sender->sendMessage(TextFormat::RED . '[LBPets] Unable to remove ' . $args[0] . '\'s pet!');
                    }
                    return true;
                }

                $sender->sendMessage(TextFormat::RED . "[LBPets] You don't have permissions to do that...");
                return true;
            case "find":
                if(count($args) < 1){
                    array_unshift($args, $sender->getDisplayName());
                }

                /**
                 * Check perms, then find pet
                 */
                if (!$this->getConfig()->get('onlyOp') || $sender->hasPermission("lbpets")) {
                    if ($this->findPet(...$args)) {
                        $sender->sendMessage(TextFormat::BLUE . '[LBPets] ' . $args[0] . '\'s pet was found!');
                    } else {
                        $sender->sendMessage(TextFormat::RED . '[LBPets] Unable to find ' . $args[0] . '\'s pet!');
                    }
                    return true;
                }

                $sender->sendMessage(TextFormat::RED . "[LBPets] You don't have permissions to do that...");
                return true;
            default:
                return false;
        }
    }

    /**
     * Give a player their pet if they are in the config
     *
     * @param PlayerLoginEvent $event The login event
     */
    public function PlayerLoginEvent(PlayerLoginEvent $event) {
        if (isset($this->players[$event->getPlayer()->getDisplayName()])) {
            $this->givePet($event->getPlayer()->getDisplayName(), $this->players[$event->getPlayer()->getDisplayName()]);
        }
    }

    /**
     * Remove the pet from a player when they leave
     *
     * @param PlayerQuitEvent $event The quit event
     */
    public function PlayerQuitEvent(PlayerQuitEvent $event) {
        if (isset($this->players[$event->getPlayer()->getDisplayName()])) {
            $this->removePet($event->getPlayer()->getDisplayName());
        }
    }

    /**
     * Give a player their pet when they respawn
     *
     * @param PlayerRespawnEvent $event The respawn event
     */
    public function PlayerRespawnEvent(PlayerRespawnEvent $event) {
        if (isset($this->players[$event->getPlayer()->getDisplayName()])) {
            $this->givePet($event->getPlayer()->getDisplayName(), $this->players[$event->getPlayer()->getDisplayName()]);
        }
    }

    /**
     * Give a user their pet
     *
     * @param  string $user     The username of the person to give Pet
     * @param  string $pet      The pet to give (The class name)
     * @return boolean          Whether or not giving the pet was successful
     */
    public function givePet($user = '', $pet = '') {
        if(($player = $this->getServer()->getPlayerExact($user)) instanceof Player) {
            if(!isset($this->pets[$player->getDisplayName()])) {
                $this->pets[$player->getDisplayName()] = PetsManager::createPet($player, $pet);
                $this->pets[$player->getDisplayName()]->returnToOwner();
                $this->players[$player->getDisplayName()] = $this->pets[$player->getDisplayName()]->getName();
                return true;
            }
        }
        return false;
    }

    /**
     * Remove the pet from the user
     *
     * @param  string $user  The username of the person to take the pet from
     * @param  boolean $unset Whether or not to unset the user from the config
     * @return boolean        Whether or not the removal was successful
     */
    public function removePet($user = '', $unset = false) {
        if(($player = $this->getServer()->getPlayerExact($user)) instanceof Player) {
            if(isset($this->pets[$player->getDisplayName()])) {
                $this->pets[$player->getDisplayName()]->fastClose();
                unset($this->pets[$player->getDisplayName()]);
                if($unset) {
                    unset($this->players[$player->getDisplayName()]);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Brings the pet back to the user
     *
     * @param  string $user The name of the user to find their pet
     * @return boolean      Whether the find was successful
     */
    public function findPet($user = '') {
        if(($player = $this->getServer()->getPlayerExact($user)) instanceof Player) {
            if(isset($this->pets[$player->getDisplayName()])) {
                $this->pets[$player->getDisplayName()]->returnToOwner();
                return true;
            }
        }
        return false;
    }

    /**
     * Disables the plguin
     *
     * @return null
     */
    public function onDisable() {
        $this->getConfig()->set('users', $this->players);
        $this->getConfig()->save();

        $this->getLogger()->info(TextFormat::DARK_RED . "Disabled");
    }
}
