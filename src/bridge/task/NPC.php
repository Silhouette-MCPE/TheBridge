<?php
declare(strict_types=1);

namespace bridge\task;
use bridge\{Main, Entity\MainEntity};
use pocketmine\scheduler\Task;
use pocketmine\{Server, Player};
use pocketmine\utils\TextFormat;

class NPC extends Task
{

	public function onRun(int $currentTick)
	{
		$level = Server::getInstance()->getDefaultLevel();
		foreach ($level->getEntities() as $entity)
		{
			if ($entity instanceof MainEntity)
			{
				$entity->setNameTag($this->setTag());
				$entity->setNameTagAlwaysVisible(true);
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
