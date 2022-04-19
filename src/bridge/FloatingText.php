<?php

declare(strict_types=1);

namespace bridge;

use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class FloatingText extends FloatingTextParticle{

    private Vector3 $pos;
    private ?World $level;

    public function __construct(Vector3 $pos){
        parent::__construct("");
        $this->level = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
        $this->pos = $pos;
    }

    public function setText(string $text):void{
     $this->text = $text;
     $this->update();
    }

    public function setTitle(string $title):void{
     $this->title = $title;
    }

    public function update():void{
     $this->level->addParticle($this->pos, $this);
    }

}