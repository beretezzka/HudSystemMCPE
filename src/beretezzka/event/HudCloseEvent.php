<?php

namespace beretezzka\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\Inventory;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudCloseEvent extends PluginEvent{

    public $player, $inventory;

    public function __construct(Plugin $plugin, Player $player, ContainerInventory $inventory){
        $this->player = $player;
        $this->inventory = $inventory;
        return parent::__construct($plugin);
    }

    public function getPlugin(): Plugin{
        return parent::getPlugin();
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function setCancelled(bool $value = true): void
    {
        parent::setCancelled($value);
    }

    public function getInventory(): ContainerInventory{
        return $this->inventory;
    }
    
}