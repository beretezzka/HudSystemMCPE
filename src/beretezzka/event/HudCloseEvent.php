<?php

namespace beretezzka\event;

use beretezzka\inventory\HudPersonalInventory;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudCloseEvent extends PluginEvent{

    public function __construct(
        public readonly Plugin $plugin,
        public readonly Player $player,
        public readonly HudPersonalInventory $inventory){
        return parent::__construct($plugin);
    }

    public function getPlugin(): Plugin{
        return parent::getPlugin();
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getInventory(): HudPersonalInventory{
        return $this->inventory;
    }
    
}