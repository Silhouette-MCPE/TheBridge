<?php
declare(strict_types=1);

namespace bridge\scoreboard;

use JetBrains\PhpStorm\Pure;
use pocketmine\network\mcpe\protocol\{
	RemoveObjectivePacket,
	SetDisplayObjectivePacket,
	SetScorePacket, 
	types\ScorePacketEntry
};
use pocketmine\{player\Player};
use bridge\Main;
class Scoreboard
{
    public array $scoreboards;

    public function new(Player $player, string $objectiveName, string $displayName): void {
      	if($player->isConnected()){
    		if(isset($this->scoreboards[$player->getName()])){
    			$this->remove($player);
    		}
    		$pk = new SetDisplayObjectivePacket();
    		$pk->displaySlot = "sidebar";
    		$pk->objectiveName = $objectiveName;
    		$pk->displayName = $displayName;
    		$pk->criteriaName = "dummy";
    		$pk->sortOrder = 0;
    		$player->getNetworkSession()->sendDataPacket($pk);
    		$this->scoreboards[$player->getName()] = $objectiveName;
    	}
    }
    public function remove(Player $player): void {
      if($player->isConnected()){
    	  $objectiveName = $this->getObjectiveName($player);
    	  $pk = new RemoveObjectivePacket();
    	  $pk->objectiveName = $objectiveName;
    	  $player->getNetworkSession()->sendDataPacket($pk);
    	  unset($this->scoreboards[$player->getName()]);
      }
    }
    public function setLine(Player $player, int $score, string $message): void {
    	if($player->isConnected()){
    		if(!isset($this->scoreboards[$player->getName()])){
    			Main::getInstance()->getLogger()->error("Cannot set a score to a player with no scoreboard");
    			return;
    		}
    		if($score > 15 || $score < 1){
                Main::getInstance()->getLogger()->error("Score must be between the value of 1-15. $score out of range");
    			return;
    		}
    		$objectiveName = $this->getObjectiveName($player);
    		$entry = new ScorePacketEntry();
    		$entry->objectiveName = $objectiveName;
    		$entry->type = $entry::TYPE_FAKE_PLAYER;
    		$entry->customName = $message;
    		$entry->score = $score;
    		$entry->scoreboardId = $score;
    		$pk = new SetScorePacket();
    		$pk->type = $pk::TYPE_CHANGE;
    		$pk->entries[] = $entry;
    		$player->getNetworkSession()->sendDataPacket($pk);
    	}
    }
    #[Pure] public function getObjectiveName(Player $player): ?string {
    	return $this->scoreboards[$player->getName()] ?? null;
    }
    
    public function getScoreboards(): array
    {
        return $this->scoreboards;
    }
}
