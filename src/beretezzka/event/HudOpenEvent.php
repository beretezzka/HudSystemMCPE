<?php

namespace beretezzka\event;

use beretezzka\inventory\HudPersonalInventory;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudOpenEvent extends PluginEvent{

    public $plugin, $player, $inventory;
    public function __construct(Plugin $plugin, Player $player, HudPersonalInventory $inventory){
        $this->player = $player;
        $this->inventory = $inventory;
        return parent::__construct($plugin);
    }

    public function setCancelled(bool $value = true): void{
        parent::setCancelled($value);
    }

    public function getPlugin(): Plugin{
        return parent::getPlugin();
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getInventory(): HudPersonalInventory {
        return $this->inventory;
    }

}