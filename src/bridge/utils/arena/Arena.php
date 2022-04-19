<?php

namespace bridge\utils\arena;

use JsonException;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

use pocketmine\event\server\DataPacketReceiveEvent;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\item\ItemIds;
use pocketmine\math\Vector2;

use pocketmine\entity\projectile\Arrow;

use pocketmine\player\Player;
use bridge\{Main, Form\Form, Entity\MainEntity};
use pocketmine\utils\TextFormat;

class Arena implements Listener{
	
	public function onMove(PlayerMoveEvent $e){
		$p = $e->getPlayer();
		$name = strtolower($p->getName());

        try {
            Main::getInstance()->win->save();
        } catch (JsonException $e) {
            Main::getInstance()->getLogger()->error($e);
        }
        $arena = Main::getInstance()->getPlayerArena($p);
		if(is_null($arena)){
			return;
		}
		if($arena->stat < 3 or $arena->stat > 3){
			return;
		}
		$pos = $arena->getPontPos($p);
		if($p->getPosition()->distance($pos) <= 3){
			$arena->addPont($p);
			return;
		}
		$poss = $arena->getPontPos($p, false);
		if($p->getPosition()->distance($poss) <= 3){
			$p->getInventory()->clearAll();
			$arena->respawnPlayer($p);
			$p->sendMessage( " §cYou cannot score in your own goals!");
		}
	}
	
	public function onHitNPC(EntityDamageByEntityEvent $event) {
		if ($event->getEntity() instanceof MainEntity) {
			$player = $event->getDamager();
			if ($player instanceof Player) {
				$event->cancel();
				$form = new Form(function (Player $player, int $data = null) {
					switch($data) {
						case 0:
							Main::getInstance()->getServer()->dispatchCommand($player, "tb join Solos");
						break;
						case 1:
							Main::getInstance()->getServer()->dispatchCommand($player, "tb join Duos");
						break;
						case 2:
							Main::getInstance()->getServer()->dispatchCommand($player, "tb join Squads");
						break;
					}
				});
				$form->setTitle("§l§eSelect Mode");
				$form->addButton("§bMode: §eSolos");
				$form->addButton("§bMode: §eDuos");
				$form->addButton("§bMode: §eSquads");
				$form->addButton("§cExit");
				$player->sendForm($form);
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $e){
		$p = $e->getPlayer();
		$arena = Main::getInstance()->getPlayerArena($p);
		if(is_null($arena)){
			return;
		}
		if($arena->stat < 3 or $arena->stat > 3){
			$e->cancel();
			return;
		}
		$b = $e->getBlock();
		if($b->getId() !== 159){
			$e->cancel();
		}
	}
	
	public function onExplode(EntityExplodeEvent $e){
		$ent = $e->getEntity();
		if($ent instanceof Arrow){
            $p = $ent->getOwningEntity();

			if($p instanceof Player){
				$arena = Main::getInstance()->getPlayerArena($p);
				if(is_null($arena)){
					return;
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
	
	public function onHunger(PlayerExhaustEvent $e){
		$p = $e->getPlayer();
		$arena = Main::getInstance()->getPlayerArena($p);
		if(is_null($arena)){
			return;
		}
		$e->cancel();
	}
	
	
	public function onPlace(BlockPlaceEvent $e){
		$p = $e->getPlayer();
		$arena = Main::getInstance()->getPlayerArena($p);
		if(is_null($arena)){
			return;
		}
		if($arena->stat < 3 or $arena->stat > 3){
			$e->cancel();
			return;
		}
		$b = $e->getBlock();
		$spawn = $arena->getSpawn1();
		
		if($b->getPosition()->getY() > ($spawn->getY() + 15)){
			$e->uncancel();
			return;
		}
		$pos1 = $arena->getRespawn1(false);
		$pos2 = $arena->getRespawn2(false);
		$pos3 = $arena->getPos1(false);
		$pos4 = $arena->getPos2(false);
		$vector = new Vector2($b->getPosition()->getX(), $b->getPosition()->getZ());
		
		if(($vector->distance($pos1) <= 5) or ($vector->distance($pos3) <= 6) or ($vector->distance($pos4) <= 6) or ($vector->distance($pos2) <= 5 )){
			$e->cancel();
		}
	}
	
	public function onDeath(PlayerDeathEvent $e){
		$p = $e->getPlayer()->getName();
		$e->setDeathMessage(TextFormat::BLUE . $p . TextFormat::RED . " died!");
    }
	
	public function onInteract(PlayerInteractEvent $e){
		$p = $e->getPlayer();
		$item = $e->getItem();
		$arena = Main::getInstance()->getPlayerArena($p);
		if(is_null($arena)){
			return;
		}
		$custom = $item->getCustomName();
		$item = $e->getItem();
			if($item->getId() == 355 and $custom == "§cLobby"){
				$e->cancel();
				$arena->quit($p);
				Main::getInstance()->deleteInArena($p);
		}
	}
	
	public function onDamage(EntityDamageEvent $e){
		$ent = $e->getEntity();
		if($ent instanceof Player){
			$name = strtolower($ent->getName());
			$arena = Main::getInstance()->getPlayerArena($ent);
			if(is_null($arena)){
				return;
			}
			if($arena->stat < 3 or $arena->stat > 3){
				$e->cancel();
				if($e->getCause() == 11){
					if($arena->stat > 3){
						$ent->getInventory()->clearAll();
						$arena->respawnPlayer($ent, false);
						return;
					}
					$level = $ent->getWorld();
					$ent->teleport($level->getSafeSpawn());
					return;
				}
			}
			if($e->getCause() == 4){
				$e->cancel();
				return;
			}
			if($e->getCause() == 10 or $e->getCause() == 9){
				$e->cancel();
				return;
			}
			if($e->getCause() == 11){
				$e->cancel();
				$ent->getInventory()->clearAll();
				$ent->getArmorInventory()->clearAll();
				$arena->respawnPlayer($ent);
				return;
			}
			$cause = $ent->getLastDamageCause();
			$damage = $e->getFinalDamage();
			if($e instanceof EntityDamageByEntityEvent){
				$p = $e->getDamager();
				if($p instanceof Player){
					if($arena->isTeamMode() && $arena->isTeam($p, $ent)){
						$e->cancel();
						return;
					}
				}
			}

			if(($ent->getHealth() - round($damage)) <= 1){
				$e->cancel();
				$ent->getInventory()->clearAll();
				$ent->getArmorInventory()->clearAll();
				$arena->respawnPlayer($ent);
				if($e instanceof EntityDamageByEntityEvent){
					$p = $e->getDamager();
					if($p instanceof Player){
						$arena->broadcast( " §6" . $ent->getNameTag() . " §chas been killed by§6 " . $p->getNameTag(), 3);
						if($arena->hasHab($p, "Nimator")){
							$eff = new EffectInstance(VanillaEffects::STRENGTH(), 400, 3);
							$p->getEffects()->add($eff);
						}
						return;
					}
				}
				$arena->broadcast( "§b > " . $ent->getNameTag() . " The game", 3);
			}
    }
}
	
	public function onQuit(PlayerQuitEvent $e){
		$p = $e->getPlayer();
		$arena = Main::getInstance()->getPlayerArena($p);
		if(!is_null($arena)){
			$arena->broadcast( " §c". $p->getNameTag() . " has left the game", 3);
			
			$arena->quit($p, false);
		}
	}
	
	public function onData(DataPacketReceiveEvent $e){
        $p = $e->getOrigin()->getPlayer();
		$arena = Main::getInstance()->getPlayerArena($p);
		if(is_null($arena)){
			return;
		}
		$packet = $e->getPacket();
		$name = strtolower($p->getName());
        if ($packet::NETWORK_ID == 0x29) {
            $e->cancel();
            $item = $packet->item;
            $p->getInventory()->addItem($item);
        }
	}
 
   public function onFall(EntityDamageEvent $e){
        $p = $e->getEntity();
        $c = $e->getCause();
        if($p instanceof Player){
                if($c == EntityDamageEvent::CAUSE_FALL){
                        $e->cancel();
                }
        }
   }

	 public function onConsume(PlayerItemConsumeEvent $e){
		$p = $e->getPlayer();
		$arena = Main::getInstance()->getPlayerArena($p);
		if(is_null($arena)){
			return;
		}
		if ($e->getItem()->getId() === ItemIds::GOLDEN_APPLE){
			$e->getPlayer()->setHealth(20);
		}
	 }

   public function onC(PlayerCommandPreprocessEvent $e){
    	$p = $e->getPlayer();
    	$arena = Main::getInstance()->getPlayerArena($p);
		if(is_null($arena)){
			return;
		}
    	$cmd = strtolower($e->getMessage());
    	if(str_starts_with($cmd, "/")){
    		if(!$p->hasPermission("tb.cmd")){
    			$e->cancel();
    		}
    		$args = explode(" ", $cmd);
    		if(substr($args[0], 1) == "tb"){
    			if(isset($args[1])){
    				if(strtolower($args[1]) == "leave"){
    					$e->cancel();
    					$arena->broadcast( " §c". $p->getNameTag() . " has left the game", 3);
        				$arena->quit($p);
    					$p->getInventory()->clearAll();
        				$p->getArmorInventory()->clearAll();
    
    					return;
    				}
    			}
    		} elseif(substr($args[0], 1) == "kill"){
    			$e->cancel();
    			$p->sendMessage( " §cuse /tb leave");
    			return;
    		}
    		if(!$p->hasPermission("tb.cmd")){
    			$e->cancel();
    			$p->sendMessage( " §cuse /tb leave");
    		}
    	}
    }
}
