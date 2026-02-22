<?php

namespace beretezzka\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudDamagePlayerEvent extends PluginEvent{
    
    public $player;

    public function __construct(Plugin $plugin, Player $player){
        $this->player = $player;
        return parent::__construct($plugin);
    }

    public function getPlugin(): Plugin
    {
        return parent::getPlugin();
    }

    public function setCancelled(bool $value = true): void
    {
        parent::setCancelled($value);
    }

    public function getPlayer(): Player{
        return $this->player;
    }

}