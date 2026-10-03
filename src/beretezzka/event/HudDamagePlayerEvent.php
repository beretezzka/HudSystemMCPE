<?php

namespace beretezzka\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HudDamagePlayerEvent extends PluginEvent{

    public function __construct(
        public readonly Plugin $plugin,
        public readonly Player $player){
        return parent::__construct($plugin);
    }

    public function getPlugin(): Plugin
    {
        return parent::getPlugin();
    }

    public function getPlayer(): Player{
        return $this->player;
    }

}