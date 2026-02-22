<?php

namespace beretezzka\beretmine\hudsystem\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudDropEvent extends PluginEvent{
    
    public $player, $item;
    public function __construct(Plugin $plugin, Player $player, Item $item){
        $this->player = $player;
        $this->item = $item;
        return parent::__construct($plugin);
    }

    public function getPlugin(): Plugin
    {
        return parent::getPlugin();
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function setCancelled(bool $value = true): void
    {
        parent::setCancelled($value);
    }

    public function getItem(): Item{
        return $this->item;
    }

}