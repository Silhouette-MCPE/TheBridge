<?php

declare(strict_types=1);

namespace bridge\task;

use bridge\Main;
use pocketmine\scheduler\Task;

class UpdateTask extends Task{

    public function __construct(Main $plugin){
     $this->plugin = $plugin;
    }

    public function onRun($tick){
     $lb = $this->plugin->getLeaderBoard();
     $list = $this->plugin->getParticles();
     foreach($list as $particle){
      $particle->setText($lb);
     }
    }

}