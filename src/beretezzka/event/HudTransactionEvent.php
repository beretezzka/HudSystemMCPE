<?php

namespace beretezzka\event;

use beretezzka\inventory\HudPersonalInventory;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudTransactionEvent extends PluginEvent{

    public function __construct(
        public readonly Plugin $plugin,
        public readonly HudPersonalInventory $inventory, 
        public readonly Player $player,
        public readonly Item $item){
        return parent::__construct($plugin);
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getPlugin(): Plugin{
        return parent::getPlugin();
    }

    public function getInventory() : HudPersonalInventory{
        return $this->inventory;
    }

    public function getItem(): Item{
        return $this->item;
    }

}