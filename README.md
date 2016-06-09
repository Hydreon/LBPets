LBPets - A simple plugin for adding pets to your server
======

## This plugin allows a user to add pets to their server. The commands are simple:

| Command | Sub Command | User | Params | Description |
|:-------:|:-----------:|:----:|:------:|:-----------:|
| `pets`  |   `give`   | `<name>` |  `ChickenPet, OcelotPet, PigPet, WolfPet`  | Spawns a pet |
| `pets`  |   `remove`  | `<name>` |    | Removes a player's pet |
| `pets`  |    `find`   | `<name>` |    | Finds a player's pet |

## The plugin can also be accessed by its API

```
$LBPets = Server::getInstance()->getPluginManager()->getPlugin('LBPets');

// Give a pet
$LBPets->givePet($user, $pet);

// Remove a pet
$LBPets->removePet($user);

// Find a pet
$LBPets->findPet($user);
```