<?php

namespace beretezzka\event;

use beretezzka\inventory\HudPersonalInventory;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudOpenEvent extends PluginEvent{

    public function __construct(
            public readonly Plugin $plugin,
            public readonly Player $player,
            public readonly HudPersonalInventory $inventory,
            public readonly int $list
        ){
        return parent::__construct($plugin);
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

    public function getList(): int{
        return $this->list;
    }
}