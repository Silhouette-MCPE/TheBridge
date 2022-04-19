<?php

declare(strict_types=1);

namespace bridge\task;

use bridge\{Main, Entity\MainEntity};
use pocketmine\scheduler\Task;

class NPC extends Task
{

	public function onRun(): void
	{
		$level = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
		foreach ($level->getEntities() as $entity)
		{
			if ($entity instanceof MainEntity)
			{
				$entity->setNameTag($this->setTag());
				$entity->setNameTagAlwaysVisible();
				$entity->setScale(1);
			}
		}
	}

	private function setTag(): string
	{
		$title = "§a»§l§7CLICK TO PLAY§a«"."\n"."§l§eThe Bridge§r"."\n";
		$subtitle = "§eOnline: §b" . Main::getInArena();
		return $title . $subtitle;
	}
}
