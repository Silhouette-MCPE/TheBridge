<?php

namespace bridge\utils;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;
use bridge\Main;

class Utils{

	
	public function backupMap($world, $src): bool
    {
		$path = Main::getInstance()->getServer()->getDataPath();
		$zip = new ZipArchive;
		$zip->open($src . "mapas/$world.zip", ZipArchive::CREATE);
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path."worlds/$world"));
		foreach($files as $file){
			if(is_file($file)){
				$zip->addFile($file, str_replace("\\", "/", ltrim(substr($file, strlen($path."worlds/$world")), "/\\")));
			}
		}
		$zip->close();
		return true;
	}

    //Unused
	/*public function renameMap($old, $new): bool
    {
		if(!is_dir($old_dir = Main::getInstance()->getServer()->getDataPath()."worlds/$old")){
			return false;
		}
		if(is_dir($new_dir = Main::getInstance()->getServer()->getDataPath()."worlds/$new")){
			return false;
		}
		if(Main::getInstance()->getServer()->getWorldManager()->getWorldByName($old) !== null){
			$players = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($old)->getPlayers();
			if($old === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getFolderName() and count($players) > 0){
				return false;
			}
			foreach($players as $player){
				$player->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
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
	}*/
	
	public function backupExists($world): bool
    {
		return file_exists(Main::getInstance()->getDataFolder()."mapas/$world.zip");
	}
	
	public function resetMap($world): bool
    {
		if(!is_dir($directory = Main::getInstance()->getServer()->getDataPath()."worlds/$world")){
			@mkdir($directory);
		}
		if(Main::getInstance()->getServer()->getWorldManager()->getWorldByName($world) !== null){
			$players = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($world)->getPlayers();
			if($world !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()){
				foreach($players as $player){
					$player->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
				}
				$this->unloadMap($world);
			}
		}
		$zip = new ZipArchive;
		if($zip->open(Main::getInstance()->getDataFolder()."mapas/$world.zip") === true){
			$zip->extractTo($directory);
		}
		$zip->close();
		$this->loadMap($world);
		return true;
	}
	
	public function loadMap($world): bool
    {
		if(!Main::getInstance()->getServer()->getWorldManager()->isWorldLoaded($world)){
			Main::getInstance()->getServer()->getWorldManager()->loadWorld($world);
			return true;
		}
		return false;
	}
	
	public function unloadMap($world): bool
    {
		if(Main::getInstance()->getServer()->getWorldManager()->isWorldLoaded($world)){
			Main::getInstance()->getServer()->getWorldManager()->unloadWorld(Main::getInstance()->getServer()->getWorldManager()->getWorldByName($world));
			return true;
		}
		return false;
	}
}