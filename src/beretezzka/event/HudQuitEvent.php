<?php

namespace beretezzka\beretmine\hudsystem\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudQuitEvent extends PluginEvent{
    
    public array $mini = [],
                 $double = [];
    public $player, $plugin;

    public function __construct(Plugin $plugin, Player $player, $mini = [], $double = []){
        $this->player = $player;
        $this->double = $double;
        $this->mini = $mini;
        return parent::__construct($plugin);
    }

    public function setCancelled(bool $value = true): void{
        parent::setCancelled($value);
    }
    
    public function getPlayer(): Player{
        return $this->player;
    }

    public function getPlugin(): Plugin{
        return parent::getPlugin();
    }

    public function getAllMini(): array{
        return $this->mini;
    }

    public function getAllDouble(): array{
        return $this->double;
    }
}