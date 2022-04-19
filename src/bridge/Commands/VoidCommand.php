<?php

namespace bridge\Commands;

use bridge\Main;
use bridge\utils\arena\ArenaManager;
use JsonException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class VoidCommand extends Command
{

    private Config $config;

    public function __construct()
    {
        parent::__construct("void", "Set a custom void level", "/void <y-level | reset>", []);
        Main::getInstance()->saveResource("config.yml");
        $this->config = new Config(Main::getInstance()->getDataFolder() . "config.yml", Config::YAML);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player) return;
        if (!$sender->hasPermission("tb.void")) return;
        if (!isset($args[0])){
            $sender->sendMessage(TF::RED . "Usage: /void <y-level | reset>");
            return;
        }
        $coord = (int)$args[0];
        if ($coord >= -41 && $coord < 40){
            $this->config->set("void-y-level", $coord);
            try {
                $this->config->save();
            } catch (JsonException $e) {
                Main::getInstance()->getLogger()->error($e);
            }
            $sender->sendMessage(TF::GREEN . "Successfully set the void level to $args[0].");
            $this->config->reload();
            ArenaManager::$config->reload();
        }
        if ($args[0] === "reset"){
            $this->config->set("void-y-level", 0);
            try {
                $this->config->save();
            } catch (JsonException $e) {
                Main::getInstance()->getLogger()->error($e);
            }
            $sender->sendMessage(TF::GREEN . "Successfully reset the void level.");
            $this->config->reload();
            ArenaManager::$config->reload();
        }
    }
}