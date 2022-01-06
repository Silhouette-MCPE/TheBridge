<?php

namespace bridge\task;

use pocketmine\scheduler\Task;


class ArrowCooldown extends Task {

    private $plugin;

    public function __construct(GappleCooldown $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun($tick){
        $this->plugin->timer();
    }
}