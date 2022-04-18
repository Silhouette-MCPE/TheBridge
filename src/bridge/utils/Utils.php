<?php

namespace bridge\utils;

use ZipArchive;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\{CompoundTag, StringTag};
use pocketmine\level\generator\Generator;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use bridge\Main;

class Utils{
	
	private $plugin;
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function getPlugin(){
		return $this->plugin;
	}
	
	public function getServer(){
		return $this->plugin->getServer();
	}
	
	public function backupMap($world, $src){
		$path = $this->getServer()->getDataPath();
		$zip = new ZipArchive;
		$zip->open($src . "mapas/$world.zip", ZipArchive::CREATE);
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path."worlds/$world"));
		foreach($files as $file){
			if(is_file($file)){
				$zip->addFile($file, str_replace("\\", "/", ltrim(substr($file, strlen($path."worlds/$world")), "/\\")));
			}
		}
		$zip->close();
		return true;
	}
	
	public function renameMap($old, $new){
		if(!is_dir($old_dir = $this->getPlugin()->getServer()->getDataPath()."worlds/$old")){
			return false;
		}
		if(is_dir($new_dir = $this->getPlugin()->getServer()->getDataPath()."worlds/$new")){
			return false;
		}
		if($this->getPlugin()->getServer()->getLevelByName($old) !== null){
			$players = $this->getPlugin()->getServer()->getLevelByName($old)->getPlayers();
			if($old === $this->getPlugin()->getServer()->getDefaultLevel()->getName() and count($players) > 0){
				return false;
			}
			foreach($players as $player){
				$player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSafeSpawn());
			}
		}
		rename($old_dir, $new_dir);
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->readCompressed(file_get_contents($new_dir."/level.dat"));
		$data = $nbt->getData();
		$leveldata = "";
		if($data->Data instanceof CompoundTag){
			$leveldata = $data->Data;
		}
		$leveldata["LevelName"] = $new;
		$nbt->setData(new CompoundTag("", ["Data" => $leveldata]));
		$buffer = $nbt->writeCompressed();
		file_put_contents($new_dir."/level.dat", $buffer);
		$this->loadMap($new);
		if($old === $this->getPlugin()->getServer()->getDefaultLevel()->getName()){
			$this->getPlugin()->getServer()->setDefaultLevel($this->getPlugin()->getServer()->getLevelByName($new));
			$config = new Config($this->getPlugin()->getServer()->getDataPath()."server.properties", Config::PROPERTIES);
			$config->set("level-name", $new);
			$config->save();
		}
		return true;
	}
	
	public function backupExists($world){
		return file_exists($this->getPlugin()->getDataFolder()."mapas/$world.zip");
	}
	
	public function resetMap($world){
		if(!is_dir($directory = $this->getPlugin()->getServer()->getDataPath()."worlds/$world")){
			@mkdir($directory);
		}
		if($this->getPlugin()->getServer()->getLevelByName($world) !== null){
			$players = $this->getPlugin()->getServer()->getLevelByName($world)->getPlayers();
			if($world !== $this->getPlugin()->getServer()->getDefaultLevel()->getName()){
				foreach($players as $player){
					$player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSafeSpawn());
				}
				$this->unloadMap($world);
			}
		}
		$zip = new ZipArchive;
		if($zip->open($this->getPlugin()->getDataFolder()."mapas/$world.zip") === true){
			$zip->extractTo($directory);
		}
		$zip->close();
		$this->loadMap($world);
		return true;
	}
	
	public function loadMap($world){
		if(!$this->getPlugin()->getServer()->isLevelLoaded($world)){
			$this->getPlugin()->getServer()->loadLevel($world);
			return true;
		}
		return false;
	}
	
	public function unloadMap($world){
		if($this->getPlugin()->getServer()->isLevelLoaded($world)){
			$this->getPlugin()->getServer()->unloadLevel($this->getPlugin()->getServer()->getLevelByName($world));
			return true;
		}
		return false;
	}
}