<?php

namespace beretezzka\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudDropEvent extends PluginEvent{
    
    public function __construct(
        public readonly Plugin $plugin,
        public readonly Player $player,
        public readonly Item $item){
        return parent::__construct($plugin);
    }

    public function getPlugin(): Plugin
    {
        return parent::getPlugin();
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getItem(): Item{
        return $this->item;
    }

}