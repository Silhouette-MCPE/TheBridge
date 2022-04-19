<?php

namespace bridge\task;

use pocketmine\scheduler\Task;


class ArrowCooldown extends Task {

    private GappleCooldown $plugin;

    public function __construct(GappleCooldown $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void{
        $this->plugin->timer(5);
    }
}
