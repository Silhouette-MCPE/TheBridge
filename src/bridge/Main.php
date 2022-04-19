<?php

namespace bridge;

use bridge\Commands\TheBridgeCommand;
use bridge\task\BridgeTask;
use bridge\task\NPC;
use bridge\task\UpdateTask;
use bridge\Entity\{MainEntity, EntityManager};
use bridge\utils\arena\Arena;
use bridge\utils\arena\ArenaManager;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\Entity;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\utils\Config;
use pocketmine\player\Player;

use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as T;
use pocketmine\math\Vector3;
use pocketmine\world\World;

//TODO: Find what the hell this is and see whether I need to code my own scoreboard handler
//use Scoreboards\Scoreboards;

class Main extends PluginBase{

    use SingletonTrait;
	
	public array $arenas = [];
	//TODO: Say goodbye to EconomyAPI ?
    //public EconomyAPI $eco;
	public array $leaderboard;
	public string $prefix = T::WHITE."[".T::YELLOW."TheBridge".T::WHITE."]";
	public Config $win;
	private static array $data = ['inarena' => []];
	private array $particles = [];
	public array $pos1 = [];
	public array $pos2 = [];
	public array $pos = [];
	public array $spawn1 = [];
	public array $spawn2= [];
	public array $respawn1= [];
	public array $respawn2= [];

    protected function onLoad(): void
    {
        Main::setInstance($this);
    }

    protected function onEnable(): void{
	    $this->win = new Config($this->getDataFolder(). "win.yml", Config::YAML);
        $this->leaderboard = (new Config($this->getDataFolder()."leaderboard.yml", Config::YAML))->getAll();
		$this->initResources();
		$this->initArenas();
        $this->registerCommands([
            new TheBridgeCommand()
        ]);
		$this->registerEntities([MainEntity::class]);
		$this->getScheduler()->scheduleRepeatingTask(new BridgeTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new NPC(), 20);
		$this->getServer()->getPluginManager()->registerEvents(new Arena($this), $this);
        //TODO: Uncomment this when economy plugin added :D
		//$this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if(empty($this->leaderboard["positions"])){
            $this->getServer()->getLogger()->warning("Please specify the position for the win leaderboard In-Game!");
        return;
        }
        $pos = $this->leaderboard["positions"];

        $this->particles[] = new FloatingText($this, new Vector3($pos[0], $pos[1], $pos[2]));
        $this->getScheduler()->scheduleRepeatingTask(new UpdateTask($this), 100);
        $this->getServer()->getLogger()->debug("The leaderboard location is loaded...");
    }

    protected function onDisable(): void{
		$this->close();
	}
	
	private function initResources(): void{
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "mapas/");
		@mkdir($this->getDataFolder() . "arenas/");
	}
	
	private function initArenas(): int
    {
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

    private function registerEntities(array $entities): void
    {
        foreach($entities as $entity){
            EntityFactory::getInstance()->register($entity, function(World $world, CompoundTag $nbt) use ($entity): Entity {
                return new $entity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
            }, ["a", "b", "c"]);
        }
    }

	public function getPlayerArena(Player $p): ?array{
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
	
	public function updateArenas($value = false): bool{
		if(count($this->arenas) <= 0){
			return false;
		}
		foreach($this->arenas as $arena){
			$arena->onRun($value);
		}
        return true;
	}
	
	private function close(): void{
		foreach($this->arenas as $name => $arena){
			$arena->close();
		}
	}
	
	public static function getInArena(): int{
		return count(self::$data['inarena']);
	}

	public function addInArena(Player $player): void{
		if (!isset(self::$data['inarena'][$player->getName()])) {
			self::$data['inarena'][$player->getName()] = $player->getName();
		}
	}

	public function deleteInArena(Player $player): void{
		if (isset(self::$data['inarena'][$player->getName()])) {
			unset(self::$data['inarena'][$player->getName()]);
		}
	}
	
	public function join($player, $mode = "solos"): bool{
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
	
	public function createBridge($name, $p, $pos1, $pos2, $spawn1, $spawn2, $respawn1, $respawn2, $pos, $mode = "solos"): bool{
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
	
	public function deleteBridge($name): bool{
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
	
	public function getLeaderBoard(): string{
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

    public function getParticles(): array{
     return $this->particles;
    }

    private function registerCommands(array $commands): void
    {
        foreach($commands as $command){
            if(!$command instanceof Command){
                $this->getLogger()->error("Couldn't register command: " . $command);
                return;
            }
            $this->getServer()->getCommandMap()->register("thebridge", $command);
        }
    }
}
