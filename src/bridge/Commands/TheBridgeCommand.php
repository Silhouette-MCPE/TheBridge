<?php

namespace bridge\Commands;

use bridge\Entity\EntityManager;
use bridge\Main;
use JsonException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class TheBridgeCommand extends Command
{

    public function __construct()
    {
        parent::__construct("tb", "TheBridge Main Command", "/tb help", ["thebridge"]);
    }

    public function execute(CommandSender|Player $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cPlease use this command In-Game!");
            return;
        }
        if (isset($args[0])) {
            switch (strtolower($args[0])) {
                case "help":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.help")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    $sender->sendMessage(">§aTheBridge Commands: \n" .
                        "§7/tb help : Displays list of TheBridge commands \n" .
                        "§7/tb <pos1|pos2> : Set the Goal Position <1|2> \n" .
                        "§7/tb <spawn1|spawn2> : Set the Spawn Position <1|2> \n" .
                        "§7/tb <respawn1|respawn2> : Set the Respawn position <1|2> \n" .
                        "§7/tb spawn : Set the Waiting Point \n" .
                        "§7/tb create: Create TheBridge arena \n" .
                        "§7/tb delete : Delete TheBridge arena \n" .
                        "§7/tb leaderboard : Spawn TheBridge Solos leaderboard \n" .
                        "§7/tb npc : Spawn a NPC to join arena \n" .
                        "§7/tb join : Connect player to the arena \n");
                    break;
                case "pos1":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.pos1")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    $x = $sender->getPosition()->getFloorX();
                    $y = $sender->getPosition()->getFloorY();
                    $z = $sender->getPosition()->getFloorZ();
                    Main::getInstance()->pos1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
                    $sender->sendMessage(" Goal Position 1 marked in §aX: $x §aY: $y §aZ: $z");
                    break;
                case "pos2":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.pos2")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    $x = $sender->getPosition()->getFloorX();
                    $y = $sender->getPosition()->getFloorY();
                    $z = $sender->getPosition()->getFloorZ();
                    Main::getInstance()->pos2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
                    $sender->sendMessage(" Goal Position 2 marked in §aX: $x §aY: $y §aZ: $z");
                    break;
                case "spawn1":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.spawn1")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    $x = $sender->getPosition()->getFloorX();
                    $y = $sender->getPosition()->getFloorY();
                    $z = $sender->getPosition()->getFloorZ();
                    Main::getInstance()->spawn1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
                    $sender->sendMessage(" Spawn Position 1 marked in §aX: $x §aY: $y §aZ: $z");
                    break;
                case "spawn2":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.spawn2")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    $x = $sender->getPosition()->getFloorX();
                    $y = $sender->getPosition()->getFloorY();
                    $z = $sender->getPosition()->getFloorZ();
                    Main::getInstance()->spawn2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
                    $sender->sendMessage(" Spawn Position 2 marked in §aX: $x §aY: $y §aZ: $z");
                    break;
                case "respawn1":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.respawn1")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    $x = $sender->getPosition()->getFloorX();
                    $y = $sender->getPosition()->getFloorY();
                    $z = $sender->getPosition()->getFloorZ();
                    Main::getInstance()->respawn1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
                    $sender->sendMessage(" Respawn Position 1 marked in §aX: $x §aY: $y §aZ: $z");
                    break;
                case "respawn2":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.respawn2")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    $x = $sender->getPosition()->getFloorX();
                    $y = $sender->getPosition()->getFloorY();
                    $z = $sender->getPosition()->getFloorZ();
                    Main::getInstance()->respawn2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
                    $sender->sendMessage(" Respawn Position 2 marked in §aX: $x §aY: $y §aZ: $z");
                    break;
                case "spawn":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.spawn")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    $x = $sender->getPosition()->getFloorX();
                    $y = $sender->getPosition()->getFloorY();
                    $z = $sender->getPosition()->getFloorZ();
                    Main::getInstance()->pos[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z, "level" => $sender->getWorld()->getFolderName()];
                    $sender->sendMessage(" Waiting point marked in §aX: $x §aY: $y §aZ: $z");
                    break;
                case "create":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.create")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    if (isset($args[1])) {
                        $name = $sender->getName();
                        if (!isset($this->pos1[$name])) {
                            $sender->sendMessage("Goal Position 1 not found!");
                            return;
                        }
                        if (!isset($this->pos2[$name])) {
                            $sender->sendMessage("Goal Position 2 not found!");
                            return;
                        }
                        if (!isset($this->spawn1[$name])) {
                            $sender->sendMessage("Spawn Position 1 not found!");
                            return;
                        }
                        if (!isset($this->spawn2[$name])) {
                            $sender->sendMessage("Spawn Position 2 not found!");
                            return;
                        }
                        if (!isset($this->respawn1[$name])) {
                            $sender->sendMessage("Respawn Position 1 not found!");
                            return;
                        }
                        if (!isset($this->respawn2[$name])) {
                            $sender->sendMessage("Respawn Position 2 not found!");
                            return;
                        }
                        if (!isset($this->pos[$name])) {
                            $sender->sendMessage("Waiting Point not found!");
                            return;
                        }
                        if (strlen($args[1]) > 15) {
                            $sender->sendMessage("Arena name must not be more than 15 characters");
                            return;
                        }
                        $mode = "solos";
                        if (isset($args[2])) {
                            switch (strtolower($args[2])) {
                                case "solos":
                                case "duos":
                                case "squads":
                                    $mode = strtolower($args[2]);
                                    break;
                                default:
                                    $sender->sendMessage(" §cThat Mode Doesn't Exist! Available Mode: §fSolos, §6Duos, §aSquads §conly!");
                                    return;
                            }
                        }
                        if (Main::getInstance()->createBridge($args[1], $sender, Main::getInstance()->pos1[$name], Main::getInstance()->pos2[$name], Main::getInstance()->spawn1[$name], Main::getInstance()->spawn2[$name], Main::getInstance()->respawn1[$name], Main::getInstance()->respawn2[$name], Main::getInstance()->pos[$name], $mode)) {
                            $sender->sendMessage(" §aArena §b" . $args[1] . " §asuccessfully created, with §e" . $args[2] . " §amode!");
                        }
                    } else {
                        $sender->sendMessage(" §bUsage: §c/tb create {world} {mode}");
                        return;
                    }
                    break;
                case "npc":
                    if ($sender->hasPermission("tb.npc")) {
                        $npc = new EntityManager();
                        $npc->setMainEntity($sender);
                    } else {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                    }
                    break;
                case "delete":
                    if (!$sender->hasPermission("tb.cmd") && !$sender->hasPermission("tb.delete")) {
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                        return;
                    }
                    if (isset($args[1])) {
                        if (Main::getInstance()->deleteBridge($args[1])) {
                            $sender->sendMessage(" §bArena: §c" . $args[1] . " §bSuccessfully Deleted!");
                        } else {
                            $sender->sendMessage(" §cThere is no arena with that name!");
                        }
                    }
                    break;
                case "leaderboard":
                    if (!$sender->hasPermission("tb.leaderboard") && !$sender->hasPermission("tb.cmd")) {
                        $sender->sendMessage("§cYou have not permissions to use this command!");
                        return;
                    }
                    $config = new Config(Main::getInstance()->getDataFolder() . "leaderboard.yml", Config::YAML);
                    $config->set("positions", [round($sender->getPosition()->getX()), round($sender->getPosition()->getY()), round($sender->getPosition()->getZ())]);
                    try {
                        $config->save();
                    } catch (JsonException $e) {
                        Main::getInstance()->getLogger()->error($e);
                    }
                    $sender->sendMessage("§a> Leaderboard set to X:" . round($sender->getPosition()->getX()) . " Y:" . round($sender->getPosition()->getY()) . " Z:" . round($sender->getPosition()->getZ()) . " Please restart your server!");
                    break;
                case "join":
                    $mode = "solos";
                    if (isset($args[1])) {
                        switch (strtolower($args[1])) {
                            case "solos":
                            case "duos":
                            case "squads":
                                $mode = strtolower($args[1]);
                                break;
                            default:
                                $sender->sendMessage(" §cThat Mode Doesn't Exist! Available Mode: §fSolos, §6Duos, §aSquads §conly!");
                                return;
                        }
                    }
                    if(!Main::getInstance()->join($sender, $mode)) $sender->sendMessage("There is no arena available!");
                    break;
                default:
                    $sender->sendMessage("§cUsage: /tb help");
                    break;
            }
        }
    }
}