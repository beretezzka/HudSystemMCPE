<?php

namespace beretezzka\inventory;

use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\inventory\ContainerInventory;
use pocketmine\level\Position;
use pocketmine\Player;
use function count;

class HudPersonalInventory extends ContainerInventory{
    /** @var Player */
    protected $holder;

    /**
     * @param Position $holder
     */
    public function __construct(Position $holder){
        parent::__construct($holder);
    }

    public function getNetworkType() : int{
        return WindowTypes::CONTAINER;
    }

    public function getName() : string{
        return "Chest";
    }

    public function getDefaultSize() : int{
        return 27;
    }

    /**
     * @return Position
     */
    public function getHolder() : Position{
        return $this->holder;
    }

    protected function getOpenSound() : int{
        return LevelSoundEventPacket::SOUND_CHEST_OPEN;
    }

    protected function getCloseSound() : int{
        return LevelSoundEventPacket::SOUND_CHEST_CLOSED;
    }

    public function onOpen(Player $who) : void{
        parent::onOpen($who);

        if(count($this->getViewers()) === 1){
            //TODO: this crap really shouldn't be managed by the inventory
            $this->broadcastBlockEventPacket(true);
            $this->getHolder()->getLevel()->broadcastLevelSoundEvent($this->getHolder()->add(0.5, 0.5, 0.5), $this->getOpenSound());
        }
    }

    public function onClose(Player $who) : void{
        if(count($this->getViewers()) === 1){
            //TODO: this crap really shouldn't be managed by the inventory
            $this->broadcastBlockEventPacket(false);
            $this->getHolder()->getLevel()->broadcastLevelSoundEvent($this->getHolder()->add(0.5, 0.5, 0.5), $this->getCloseSound());
        }
        parent::onClose($who);
    }

    protected function broadcastBlockEventPacket(bool $isOpen) : void{
        $holder = $this->getHolder();

        $pk = new BlockEventPacket();
        $pk->x = (int) $holder->x;
        $pk->y = (int) $holder->y;
        $pk->z = (int) $holder->z;
        $pk->eventType = 1; 
        $pk->eventData = $isOpen ? 1 : 0;
        $holder->getLevel()->broadcastPacketToViewers($holder, $pk);
    }
}
