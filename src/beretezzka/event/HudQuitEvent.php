<?php

namespace beretezzka\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudQuitEvent extends PluginEvent{

    public function __construct(
        public readonly Plugin $plugin, 
        public readonly Player $player){

        return parent::__construct($plugin);
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getPlugin(): Plugin{
        return parent::getPlugin();
    }
}