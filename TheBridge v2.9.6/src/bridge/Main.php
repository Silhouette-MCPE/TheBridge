<?php

namespace bridge;

use bridge\task\BridgeTask;
use bridge\task\NPC;
use bridge\task\UpdateTask;
use bridge\Entity\{MainEntity, EntityManager};
use bridge\utils\arena\Arena;
use bridge\utils\arena\ArenaManager;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\utils\Config;
use pocketmine\Player;

use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as T;
use pocketmine\math\Vector3;

use Scoreboards\Scoreboards;

class Main extends PluginBase{
	
	public $arenas = [];
	public $eco;
	public $leaderboard;
	public $prefix = T::WHITE."[".T::YELLOW."TheBridge".T::WHITE."]";
	public $win;
	private static $data = ['inarena' => []];
	private $particles = [];
	private $pos1 = [];
	private $pos2 = [];
	private $pos = [];
	private $spawn1 = [];
	private $spawn2= [];
	private $respawn1= [];
	private $respawn2= [];
	
	public function onEnable(){
	    $this->win = new Config($this->getDataFolder(). "win.yml", Config::YAML);
        $this->leaderboard = (new Config($this->getDataFolder()."leaderboard.yml", Config::YAML))->getAll();
		$this->initResources();
		$this->initArenas();
		Entity::registerEntity(MainEntity::class, true);
		$this->getScheduler()->scheduleRepeatingTask($this->scheduler = new BridgeTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask($this->scheduler = new NPC($this), 20);
		$this->getServer()->getPluginManager()->registerEvents(new Arena($this), $this);
		$this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if(empty($this->leaderboard["positions"])){
        $this->getServer()->getLogger()->Info("§a[TheBridge] Plugin Enable! Please specify the position for the win leaderboard In-Game!");
        $this->getServer()->getLogger()->info("§a[TheBridge] This plugin was updated by Developers ");
        $this->getServer()->getLogger()->info("§a[TheBridge] This plugin was updated by Developers  §Added: Void Death Message");
        return;
        }
        $pos = $this->leaderboard["positions"];

        $this->particles[] = new FloatingText($this, new Vector3($pos[0], $pos[1], $pos[2]));
        $this->getScheduler()->scheduleRepeatingTask(new UpdateTask($this), 100);
        $this->getServer()->getLogger()->Info("§a[TheBridge] The leaderboard location is loaded...");
        $this->getServer()->getLogger()->info("§a[TheBridge]");
        $this->getServer()->getLogger()->info("§a[TheBridge" . TextFormat::GOLD . "" . TextFormat::RESET . "I've added a kill message to the bridge game");
    }
	
	public function onDisable(){
		$this->close();
	}
	
	private function initResources(){
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "mapas/");
		@mkdir($this->getDataFolder() . "arenas/");
	}
	
	private function initArenas(){
		$src = $this->getDataFolder() . "arenas/";
		$count = 0;
		foreach(scandir($src) as $file){
			if($file !== ".." and $file !== "."){
				if(file_exists("$src" . $file)){
					$data = (new Config("$src" . $file, Config::YAML))->getAll();
					if(!isset($data["name"])){
						@unlink("$src" . $file);
						continue;
					}
					$this->arenas[strtolower($data["name"])] = new ArenaManager($this, $data);
					$count++;
				}
			}
		}
		return $count;
	}

	public function getPlayerArena(Player $p){
		$arenas = $this->arenas;
		if(count($arenas) <= 0){
			return null;
		}
		foreach($arenas as $arena){
			if($arena->isInArena($p)){
				return $arena;
			}
		}
		return null;
	}
	
	public function updateArenas($value = false){
		if(count($this->arenas) <= 0){
			return false;
		}
		foreach($this->arenas as $arena){
			$arena->onRun($value);
		}
	}
	
	private function close(){
		foreach($this->arenas as $name => $arena){
			$arena->close();
		}
	}
	
	public static function getInArena(){
		return count(self::$data['inarena']);
	}

	public function addInArena(Player $player){
		if (!isset(self::$data['inarena'][$player->getName()])) {
			self::$data['inarena'][$player->getName()] = $player->getName();
		}
	}

	public function deleteInArena(Player $player){
		if (isset(self::$data['inarena'][$player->getName()])) {
			unset(self::$data['inarena'][$player->getName()]);
		}
	}
	
	public function join($player, $mode = "solos"){
		foreach($this->arenas as $name => $arena){
			if($arena->getData()["mode"] == $mode){
				if($arena->join($player)){
					$this->addInArena($player);
					return true;
				}
			}
		}
		return false;
	}
	
	public function createBridge($name, $p, $pos1, $pos2, $spawn1, $spawn2, $respawn1, $respawn2, $pos, $mode = "solos"){
		$src = $this->getDataFolder();
		if(file_exists($src . "arenas/" . strtolower($name) . ".yml")){
			$p->sendMessage( T::RED." There is already an arena with that name");
			return false;
		}
		$config = new Config($src . "arenas/" . $name . ".yml", Config::YAML);
		
		$data = ["name" => $name, "mode" => $mode, "world" => $p->getLevel()->getName(), "waiting-point" => $pos, "pos1" => $pos1, "pos2" => $pos2, "spawn1" => $spawn1, "spawn2" => $spawn2, "respawn1" => $respawn1, "respawn2" => $respawn2];
		
		$arena = new ArenaManager($this, $data);
		
		$this->arenas[strtolower($name)] = $arena;
				
		$config->setDefaults($data);
		$config->save();
		return true;
	}
	
	public function deleteBridge($name){
	    $src = $this->getDataFolder();
		if(file_exists($src . "arenas/" . strtolower($name) . ".yml")){
			if(unlink($src . "arenas/" . strtolower($name) . ".yml")){
				if(isset($this->arenas[strtolower($name)])){
					unset($this->arenas[strtolower($name)]);
				}
				return true;
			}
		}
		return false;
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, String $label, array $args) : bool {
		if(strtolower($cmd->getName()) == "tb"){
			if(!$sender instanceof Player){
			    $sender->sendMessage("§cPlease use this command In-Game!");
				return true;
			}
			if(isset($args[0])){
				switch(strtolower($args[0])){
				    case "help":
                    if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.help")){
                        $sender->sendMessage("§cYou do not have permission to use this command!");
                    return true;
                }
                $sender->sendMessage(">§aTheBridge Commands: \n" .
                    "§7/tb help : Displays list of TheBridge commands \n".
                    "§7/tb <pos1|pos2> : Set the Goal Position <1|2> \n".
                    "§7/tb <spawn1|spawn2> : Set the Spawn Position <1|2> \n".
                    "§7/tb <respawn1|respawn2> : Set the Respawn position <1|2> \n".
                    "§7/tb spawn : Set the Waiting Point \n".
                    "§7/tb create: Create TheBridge arena \n" .
                    "§7/tb delete : Delete TheBridge arena \n" .
                    "§7/tb leaderboard : Spawn TheBridge Solos leaderboard \n" .
                    "§7/tb npc : Spawn a NPC to join arena \n" .
                    "§7/tb join : Connect player to the arena \n");
                    break;
					case "pos1":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.pos1")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->pos1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage( " Goal Position 1 marked in §aX: $x §aY: $y §aZ: $z");
					break;
					case "pos2":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.pos2")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->pos2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage( " Goal Position 2 marked in §aX: $x §aY: $y §aZ: $z");
					break;
					case "spawn1":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.spawn1")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->spawn1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage( " Spawn Position 1 marked in §aX: $x §aY: $y §aZ: $z");
					break;
					case "spawn2":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.spawn2")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->spawn2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage( " Spawn Position 2 marked in §aX: $x §aY: $y §aZ: $z");
					break;
					case "respawn1":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.respawn1")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->respawn1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage( " Respawn Position 1 marked in §aX: $x §aY: $y §aZ: $z");
					break;
					case "respawn2":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.respawn2")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->respawn2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
					$sender->sendMessage( " Respawn Position 2 marked in §aX: $x §aY: $y §aZ: $z");
					break;
					case "spawn":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.spawn")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					$x = $sender->getFloorX();
					$y = $sender->getFloorY();
					$z = $sender->getFloorZ();
					$this->pos[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z, "level" => $sender->getLevel()->getName()];
					$sender->sendMessage( " Waiting point marked in §aX: $x §aY: $y §aZ: $z");
					break;
					case "create":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.create")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					if(isset($args[1])){
						$name = $sender->getName();
						if(!isset($this->pos1[$name])){
							$sender->sendMessage( "Goal Position 1 not found!");
							return true;
						}
						if(!isset($this->pos2[$name])){
							$sender->sendMessage( "Goal Position 2 not found!");
							return true;
						}
						if(!isset($this->spawn1[$name])){
							$sender->sendMessage( "Spawn Position 1 not found!");
							return true;
						}
						if(!isset($this->spawn2[$name])){
							$sender->sendMessage( "Spawn Position 2 not found!");
							return true;
						}
						if(!isset($this->respawn1[$name])){
							$sender->sendMessage( "Respawn Position 1 not found!");
							return true;
						}
						if(!isset($this->respawn2[$name])){
							$sender->sendMessage( "Respawn Position 2 not found!");
							return true;
						}
						if(!isset($this->pos[$name])){
							$sender->sendMessage( "Waiting Point not found!");
							return true;
						}
						$level = $sender->getLevel();
						if(strlen($args[1]) > 15){
							$sender->sendMessage( "Arena name must not be more than 15 characters");
							return true;
						}
						$mode = "solos";
						if(isset($args[2])){
							switch(strtolower($args[2])){
								case "solos":
								case "duos":
								case "squads":
								$mode = strtolower($args[2]);
								break;
								default:
								$sender->sendMessage(" §cThat Mode Doesn't Exist! Available Mode: §fSolos, §6Duos, §aSquads §conly!");
								return true;
							}
						}
						if($this->createBridge($args[1], $sender, $this->pos1[$name], $this->pos2[$name], $this->spawn1[$name], $this->spawn2[$name], $this->respawn1[$name], $this->respawn2[$name], $this->pos[$name], $mode)){
							$sender->sendMessage( " §aArena §b" . $args[1] . " §aSuccessfully Created, with §e" . $args[2] . " §amode!");
						}
					} else {
						$sender->sendMessage( " §bUsage: §c/tb create {world} {mode}");
						return true;
					}
					break;
					case "npc":
					if($sender->hasPermission("bridge.npc.cmd")){
						$npc = new EntityManager();
						$npc->setMainEntity($sender);
					} else {
						$sender->sendMessage("§cYou do not have permission to use this command!");
					}
					break;
					case "delete":
					if(!$sender->hasPermission("bridge.cmd") && !$sender->hasPermission("bridge.cmd.delete")){
						$sender->sendMessage("§cYou do not have permission to use this command!");
						return true;
					}
					if(isset($args[1])){
						if($this->deleteBridge($args[1])){
							$sender->sendMessage( " §bArena: §c" . $args[1] . " §bSuccessfully Deleted!");
						} else {
							$sender->sendMessage( " §cThere is no arena with that name!");
						}
					}
					break;
					case "leaderboard":
                    if(!$sender->hasPermission("bridge.cmd.leaderboard") && !$sender->hasPermission("bridge.cmd")) {
                        $sender->sendMessage("§cYou have not permissions to use this command!");
                        return true;
                    }
                    $config = new Config($this->getDataFolder()."leaderboard.yml", Config::YAML);
                    $config->set("positions", [round($sender->getX()), round($sender->getY()), round($sender->getZ())]);
                    $config->save();
                    $sender->sendMessage("§a> Leaderboard set to X:" . round($sender->getX()) . " Y:" . round($sender->getY()) . " Z:" . round($sender->getZ()) . " Please restart your server!");
                    break;
					case "join":
					$mode = "solos";
					if(isset($args[1])){
						switch(strtolower($args[1])){
							case "solos":
							case "duos":
							case "squads":
							$mode = strtolower($args[1]);
							break;
							default:
							$sender->sendMessage( " §cThat Mode Doesn't Exist! Available Mode: §fSolos, §6Duos, §aSquads §conly!");
							return true;
						}
					}
					if($this->join($sender, $mode)){

					} else {
						$sender->sendMessage( "There is no arena available!");
					}
					break;
					default:
					$sender->sendMessage("§cUsage: /tb help");
					break;
				}
			} else {
				$sender->sendMessage("§cUsage: /tb help");
			}
		}
	return true;
	}
	
	public function getLeaderBoard():string{
	    $solowin = $this->win->getAll();
	    $message = "";
	    $toptb = "§l§6TheBridge Leaderboard\n";
     if(count($solowin) > 0){
      arsort($solowin);
      $i = 0;
      foreach($solowin as $name => $win){
       $message .= "\n§6".($i+1).". §7".$name."§7 - §6".$win."\n\n\n";
       if($i >= 10){
        break;
       }
       ++$i;
      }
     }
     $return = (string) $toptb.$message;
     return $return;
    }

    public function getParticles():array{
     return $this->particles;
    }
}
