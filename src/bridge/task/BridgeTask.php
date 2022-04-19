<?php

namespace bridge\task;

use pocketmine\scheduler\Task;
use bridge\Main;

class BridgeTask extends Task{
	
	public function onRun(): void{
		Main::getInstance()->updateArenas(true);
	}

}