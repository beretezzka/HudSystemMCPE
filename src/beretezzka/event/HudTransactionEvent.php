<?php

namespace beretezzka\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudTransactionEvent extends PluginEvent{
    
    public array $mini = [],
                 $double = [];
    public $player, $inventory, $list, $item;

    public function __construct(Plugin $plugin, $inventory, Player $player, Item $item, int $list, $mini = [], $double = []){
        $this->player = $player;
        $this->inventory = $inventory;
        $this->list = $list;
        $this->double = $double;
        $this->mini = $mini;
        $this->item = $item;
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

    public function getInventory() {
        return $this->inventory;
    }

    public function getList(): int{
        return $this->list;
    }

    public function getAllMini(): array{
        return $this->mini;
    }

    public function getItem(): Item{
        return $this->item;
    }

    public function getAllDouble(): array{
        return $this->double;
    }
}