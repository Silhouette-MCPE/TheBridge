<?php
declare(strict_types=1);

namespace bridge\Entity;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\entity\Skin;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\{Server, Player};

final class EntityManager
{
	
	public function setMainEntity(Player $player)
	{
		$nbt = Entity::createBaseNBT(new Vector3((float)$player->getX(), (float)$player->getY(), (float)$player->getZ()));
		$nbt->setTag(clone $player->namedtag->getCompoundTag("Skin"));
		$human = new MainEntity($player->getLevel(), $nbt);
		$human->setNameTag("");
		$human->setNameTagVisible(true);
		$human->setNameTagAlwaysVisible(true);
		$human->yaw = $player->getYaw();
		$human->pitch = $player->getPitch();
		$human->spawnToAll();
	}
}
