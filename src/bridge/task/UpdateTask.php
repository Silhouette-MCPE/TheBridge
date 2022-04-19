<?php

declare(strict_types=1);

namespace bridge\task;

use bridge\Main;
use pocketmine\scheduler\Task;

class UpdateTask extends Task{

    public function onRun(): void{
     $lb = Main::getInstance()->getLeaderBoard();
     $list = Main::getInstance()->getParticles();
     foreach($list as $particle){
      $particle->setText($lb);
     }
    }

}