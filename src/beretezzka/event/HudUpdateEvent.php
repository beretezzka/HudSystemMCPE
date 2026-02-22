<?php

namespace beretezzka\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;

class HudUpdateEvent extends PluginEvent{
    
    public array $viewers;

    public function __construct(Plugin $plugin, array $viewers){
        $this->viewers = $viewers;
        return parent::__construct($plugin);
    }

    public function setCancelled(bool $value = true): void
    {
        parent::setCancelled($value);
    }

    public function isCancelled(): bool
    {
        return parent::isCancelled();
    }

    public function getPlugin(): Plugin
    {
        return parent::getPlugin();
    }

    public function getMini(): array{
        return $this->viewers["mini"];
    }
    
    public function getDouble(): array{
        return $this->viewers["double"];
    }
    
}