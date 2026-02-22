<?php

namespace beretezzka\beretmine\hudsystem\event;

use beretezzka\beretmine\hudsystem\inventory\HudPersonalInventory;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\Inventory;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudOpenEvent extends PluginEvent{

    public $plugin, $player, $inventory, $list;
    public function __construct(Plugin $plugin, Player $player, HudPersonalInventory $inventory, int $list = 0){
        $this->player = $player;
        $this->inventory = $inventory;
        $this->list = $list;
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

    public function getList(): int{
        return $this->list;
    }
}