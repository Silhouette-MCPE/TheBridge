<?php
declare(strict_types=1);

namespace bridge\Entity;

use pocketmine\player\Player;

final class EntityManager
{
	
	public function setMainEntity(Player $player): void
    {
        $skin = $player->getSkin();
		$human = new MainEntity($player->getLocation(), $skin);
		$human->setNameTag("");
		$human->setNameTagVisible();
		$human->setNameTagAlwaysVisible();
		$human->spawnToAll();
	}
}
