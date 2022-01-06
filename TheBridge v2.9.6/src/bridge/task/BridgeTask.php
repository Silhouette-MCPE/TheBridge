<?php

namespace bridge\task;

use pocketmine\scheduler\Task;
use bridge\Main;

class BridgeTask extends Task{
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function onRun($timer){
		$this->plugin->updateArenas(true);
	}
}