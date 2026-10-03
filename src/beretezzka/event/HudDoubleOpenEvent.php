<?php

namespace beretezzka\event;

use beretezzka\inventory\HudPersonalInventoryD;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudDoubleOpenEvent extends PluginEvent{

    public function __construct(
        public readonly Plugin $plugin,
        public readonly Player $player,
        public readonly HudPersonalInventoryD $inventory,
        public readonly int $list){
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

    public function getList(): int{
        return $this->list;
    }
}