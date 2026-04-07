<?php

namespace beretezzka\beretmine\hudsystem\event;

use beretezzka\beretmine\hudsystem\inventory\HudPersonalInventoryD;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\inventory\Inventory;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudDoubleOpenEvent extends PluginEvent{

    public $plugin, $player, $inventory;
    public function __construct(Plugin $plugin, Player $player, HudPersonalInventoryD $inventory){
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

    public function getInventory(): HudPersonalInventoryD {
        return $this->inventory;
    }
}