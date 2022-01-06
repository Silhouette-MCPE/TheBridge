<?php

namespace bridge\utils\arena;

use pocketmine\event\Listener;

use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

use pocketmine\event\server\DataPacketReceiveEvent;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\EntityExplodeEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\math\Vector3;
use pocketmine\math\Vector2;
use pocketmine\item\Item;
use pocketmine\block\Block;

use pocketmine\entity\Arrow;
use pocketmine\entity\Effect;
use pocketmine\level\Explosion;

use pocketmine\Player;
use bridge\{Main, Form\Form, Form\FormAPI, Entity\EntityManager, Entity\MainEntity};
use pocketmine\utils\TextFormat;

class Arena implements Listener{
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function getPlugin(){
		return $this->plugin;
	}
	
	
	public function onMove(PlayerMoveEvent $e){
		$p = $e->getPlayer();
		$name = strtolower($p->getName());
		
		$this->plugin->win->save();
		$arena = $this->getPlugin()->getPlayerArena($p);
		if(is_null($arena)){
			return true;
		}
		if($arena->stat < 3 or $arena->stat > 3){
			return true;
		}
		$pos = $arena->getPontPos($p);
		if($p->distance($pos) <= 3){
			$arena->addPont($p);
			return true;
		}
		$poss = $arena->getPontPos($p, false);
		if($p->distance($poss) <= 3){
			$p->getInventory()->clearAll();
			$arena->respawnPlayer($p);
			$p->sendMessage( " §cYou cannot score in your own goals!");
		}
	}
	
	public function onHitNPC(EntityDamageByEntityEvent $event) {
		if ($event->getEntity() instanceof MainEntity) {
			$player = $event->getDamager();
			if ($player instanceof Player) {
				$event->setCancelled(true);
				$form = new Form(function (Player $player, int $data = null) {
					switch($data) {
						case 0:
						break;
						case 1:
						$this->getPlugin()->getServer()->dispatchCommand($player, "tb join Solos");
						break;
						case 2:
						$this->getPlugin()->getServer()->dispatchCommand($player, "tb join Duos");
						break;
						case 3:
						$this->getPlugin()->getServer()->dispatchCommand($player, "tb join Squads");
						break;
					}
				});
				$form->setTitle("§l§eSelect Mode");
				$form->addButton("§cExit");
				$form->addButton("§bMode: §eSolos");
				$form->addButton("§bMode: §eDuos");
				$form->addButton("§bMode: §eSquads");
				$player->sendForm($form);
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $e){
		$p = $e->getPlayer();
		$arena = $this->getPlugin()->getPlayerArena($p);
		if(is_null($arena)){
			return true;
		}
		if($arena->stat < 3 or $arena->stat > 3){
			$e->setCancelled();
			return true;
		}
		$b = $e->getBlock();
		if($b->getId() !== 159){
			$e->setCancelled();
		}
	}
	
	public function onExplode(EntityExplodeEvent $e){
		$ent = $e->getEntity();
		if($ent instanceof Arrow){
			$p = $ent->shootingEntity;
			
			if($p instanceof Player){
				$arena = $this->getPlugin()->getPlayerArena($p);
				if(is_null($arena)){
					return true;
				}
				$arr = [];
				foreach($e->getBlockList() as $block){
					if($block->getId() == 159 and $block->getDamage() >= 1){
						$arr[] = $block;
					}
				}
				$e->setBlockList($arr);
			}
		}
	}
	
	
	public function onPlace(BlockPlaceEvent $e){
		$p = $e->getPlayer();
		$arena = $this->getPlugin()->getPlayerArena($p);
		if(is_null($arena)){
			return true;
		}
		if($arena->stat < 3 or $arena->stat > 3){
			$e->setCancelled();
			return true;
		}
		$b = $e->getBlock();
		$spawn = $arena->getSpawn1();
		
		if($b->y > ($spawn->y + 15)){
			$e->setCancelled(false);
			return true;
		}
		$pos1 = $arena->getRespawn1(false);
		$pos2 = $arena->getRespawn2(false);
		$pos3 = $arena->getPos1(false);
		$pos4 = $arena->getPos2(false);
		$vector = new Vector2($b->x, $b->z);
		
		if(($vector->distance($pos1) <= 5) or ($vector->distance($pos3) <= 6) or ($vector->distance($pos4) <= 6) or ($vector->distance($pos2) <= 5 )){
			$e->setCancelled(true);
		} else {

		}
	}
	
	public function onDeath(PlayerDeathEvent $e){
		$p = $e->getPlayer();
		$e->setDeathMessage($p . TextFormat::RED . "accidentally and fell into the void");

		        }
	
	public function onInteract(PlayerInteractEvent $e){
		$p = $e->getPlayer();
		$item = $e->getItem();
		$arena = $this->getPlugin()->getPlayerArena($p);
		if(is_null($arena)){
			return true;
		}
		$custom = $item->getCustomName();
		$item = $e->getItem();
			if($item->getId() == 355 and $custom == "§cLobby"){
				$e->setCancelled();
				$arena->quit($p);
				$this->getPlugin()->deleteInArena($p);
		}
	}
	
	public function onDamage(EntityDamageEvent $e){
		$ent = $e->getEntity();
		if($ent instanceof Player){
			$name = strtolower($ent->getName());
			$arena = $this->getPlugin()->getPlayerArena($ent);
			if(is_null($arena)){
				return true;
			}
			if($arena->stat < 3 or $arena->stat > 3){
				$e->setCancelled();
				if($e->getCause() == 11){
					if($arena->stat > 3){
						$ent->getInventory()->clearAll();
						$arena->respawnPlayer($ent, false);
						return true;
					}
					$level = $ent->getLevel();
					$ent->teleport($level->getSafeSpawn());
					return true;
				}
			}
			if($e->getCause() == 4){
				$e->setCancelled();
				return true;
			}
			if($e->getCause() == 10 or $e->getCause() == 9){
				$e->setCancelled();
				return true;
			}
			if($e->getCause() == 11){
				$e->setCancelled();
				$ent->getInventory()->clearAll();
				$ent->getArmorInventory()->clearAll();
				$arena->respawnPlayer($ent);
				return true;
			}
			$cause = $ent->getLastDamageCause();
			$damage = $e->getFinalDamage();
			if($e instanceof EntityDamageByEntityEvent){
				$p = $e->getDamager();
				if($p instanceof Player){
					if($arena->isTeamMode() && $arena->isTeam($p, $ent)){
						$e->setCancelled();
						return true;
					}
				}
			}
			
			if(($ent->getHealth() - round($damage)) <= 1){
				$e->setCancelled();
				$ent->getInventory()->clearAll();
				$ent->getArmorInventory()->clearAll();
				$arena->respawnPlayer($ent);
				if($e instanceof EntityDamageByEntityEvent){
					$p = $e->getDamager();
					if($p instanceof Player){
						$arena->broadcast( " §6" . $ent->getNameTag() . " §chas been killed by§6 " . $p->getNameTag(), 3);
						if($arena->hasHab($p, "Nimator")){
							$eff = Effect::getEffect(5);
							$eff->setDuration(20*20);
							$eff->setAmplifier(3);
							
							$p->addEffect($eff);
						}
						return true;
					}
				}
				$arena->broadcast( "§b > " . $ent->getNameTag() . " The game", 3);
				return true;
			}
			switch($e->getCause()){
				case 1:
				case 2:
				case 4:
				case 11:
				if($cause instanceof EntityDamageByEntityEvent){
				}
				break;
			}
}
}
	
	public function onQuit(PlayerQuitEvent $e){
		$p = $e->getPlayer();
		$arena = $this->getPlugin()->getPlayerArena($p);
		if(!is_null($arena)){
			$arena->broadcast( " §c". $p->getNameTag() . " Has left the game", 3);
			
			$arena->quit($p, false);
		}
	}
	
	public function onData(DataPacketReceiveEvent $e){
		$p = $e->getPlayer();
		$arena = $this->getPlugin()->getPlayerArena($p);
		if(is_null($arena)){
			return true;
		}
		$packet = $e->getPacket();
		$name = strtolower($p->getName());
		switch($packet::NETWORK_ID){
			case 0x29:
			$e->setCancelled();
			$item = $packet->item;
			$p->getInventory()->addItem($item);
			break;
		}
	}
	
   public function onC(PlayerCommandPreprocessEvent $e){
    	$p = $e->getPlayer();
    	$arena = $this->getPlugin()->getPlayerArena($p);
		if(is_null($arena)){
			return true;
		}
    	$cmd = strtolower($e->getMessage());
    	if(substr($cmd, 0, 1) == "/"){
    		if(!$p->hasPermission("bridge.cmd")){
    			$e->setCancelled();
    		}
    		$args = explode(" ", $cmd);
    		if(substr($args[0], 1) == "tb"){
    			if(isset($args[1])){
    				if(strtolower($args[1]) == "leave"){
    					$e->setCancelled();
    					$arena->broadcast( " §c". $p->getNameTag() . " Has left the game", 3);
        				$arena->quit($p);
    					$p->getInventory()->clearAll();
        				$p->getArmorInventory()->clearAll();
    
    					return true;
    				}
    			}
    		} elseif(substr($args[0], 1) == "kill"){
    			$e->setCancelled();
    			$p->sendMessage( " §cuse /tb leave");
    			return true;
    		}
    		if(!$p->hasPermission("bridge.cmd")){
    			$e->setCancelled();
    			$p->sendMessage( " §cuse /tb leave");
    		}
    	}
    }
}