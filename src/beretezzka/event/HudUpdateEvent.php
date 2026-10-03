<?php

namespace beretezzka\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;

class HudUpdateEvent extends PluginEvent{

    public function __construct(
        public readonly Plugin $plugin, 
        public readonly array $viewers){

        return parent::__construct($plugin);
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function getMini(): array{
        return $this->viewers["mini"];
    }
    
    public function getDouble(): array{
        return $this->viewers["double"];
    }
    
}