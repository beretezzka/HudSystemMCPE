<?php

namespace beretezzka;

use beretezzka\event\HudCloseEvent;
use beretezzka\event\HudDamagePlayerEvent;
use beretezzka\event\HudDoubleOpenEvent;
use beretezzka\event\HudDropEvent;
use beretezzka\event\HudOpenEvent;
use beretezzka\event\HudQuitEvent;
use beretezzka\event\HudTransactionEvent;
use beretezzka\inventory\HudPersonalInventory;
use beretezzka\inventory\HudPersonalInventoryD;
use beretezzka\event\HudUpdateEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\inventory\transaction\action\ContainerDropItemAction;
use pocketmine\Server;

class Events implements Listener{

    public $loader;

    public function __construct(HudSystem $loader){
        $this->loader = $loader;
    }

    public function quit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if (!HudSystem::getInstance()->isViewDouble($player) && !HudSystem::getInstance()->isViewMini($player)) {
            return;
        }
        Server::getInstance()->getPluginManager()->callEvent(new HudQuitEvent($this->loader, $player, HudSystem::getInstance()->viewers["mini"], HudSystem::getInstance()->viewers["double"]));
        return;
    }

    public function updater(HudUpdateEvent $event){
        foreach(array_keys($event->getMini()) as $nick){
            $player = Server::getInstance()->getPlayer($nick);
            if($player !== null && $player instanceof Player && ($player->isCreative() || $player->getPing() > 200)){
                $player->sendMessage("§cПопробуйте снова..");
                HudSystem::getInstance()->closeMini($player);
                continue;
            }
        }
        foreach(array_keys($event->getDouble()) as $nick){
            $player = Server::getInstance()->getPlayer($nick);
            if($player !== null && $player instanceof Player && ($player->isCreative() || $player->getPing() > 200)){
                $player->sendMessage("§cПопробуйте снова..");
                HudSystem::getInstance()->closeDouble($player);
                continue;
            }
        }
    }

    public function move(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if (!HudSystem::getInstance()->isViewDouble($player) && !HudSystem::getInstance()->isViewMini($player)) {
            return;
        }
        if($event->getTo()->distance($event->getFrom()) > 0.09){
            if(HudSystem::getInstance()->isViewDouble($player)){
                HudSystem::getInstance()->closeDouble($player);
            }
            if(HudSystem::getInstance()->isViewMini($player)){
                HudSystem::getInstance()->closeMini($player);
            }
        }
    }
    
    public function command(PlayerCommandPreprocessEvent $event){
        if (HudSystem::getInstance()->isViewDouble($event->getPlayer()) || HudSystem::getInstance()->isViewMini($event->getPlayer())) {
            $event->getPlayer()->sendMessage("§r§cКоманду невозможно ввести в данный момент.");
            $event->setCancelled();
        }
        
    }

    public function transaction(InventoryTransactionEvent $event){
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        if (!HudSystem::getInstance()->isViewDouble($player) && !HudSystem::getInstance()->isViewMini($player)) {
            return;
        }

        $event->setCancelled();
        if ($transaction instanceof InventoryTransaction) {
            foreach ($transaction->getActions() as $_transaction) {
                if($_transaction instanceof ContainerDropItemAction){
                    $item = $_transaction->getTargetItem();

                    if(!HudSystem::getInstance()->isHudItem($item)){
                        continue;   
                    }

                    if(!$item instanceof Item || $item == null){
                        continue;
                    }
                    Server::getInstance()->getPluginManager()->callEvent(new HudDropEvent($this->loader, $player, $item));
                    continue;
                }elseif($_transaction instanceof DropItemAction){
                    $item = $_transaction->getTargetItem();

                    if(!HudSystem::getInstance()->isHudItem($item)){
                        continue;   
                    }

                    if(!$item instanceof Item || $item == null){
                        continue;
                    }
                    Server::getInstance()->getPluginManager()->callEvent(new HudDropEvent($this->loader, $player, $item));
                    continue;
                }elseif ($_transaction instanceof SlotChangeAction) {
                    $inventory = $_transaction->getInventory();
                    $item = $inventory->getItem($_transaction->getSlot());
                    if(!$item instanceof Item || $item == null){
                        continue;
                    }
                    Server::getInstance()->getPluginManager()->callEvent(new HudTransactionEvent($this->loader, $inventory, $player, $item));
                    continue;
                }
            }
        }
    }

    public function open(InventoryOpenEvent $event){
        $inventory = $event->getInventory();
        $player = $event->getPlayer();

        if (!HudSystem::getInstance()->isViewDouble($player) && !HudSystem::getInstance()->isViewMini($player)) {
            return;
        }

        if($inventory instanceof HudPersonalInventoryD){
            Server::getInstance()->getPluginManager()->callEvent(new HudDoubleOpenEvent($this->loader, $player, $inventory));
            return;
        }
        if($inventory instanceof HudPersonalInventory){
            Server::getInstance()->getPluginManager()->callEvent(new HudOpenEvent($this->loader, $player, $inventory));
            return;
        }
    }

    public function close(InventoryCloseEvent $event){
        $inventory = $event->getInventory();
        $player = $event->getPlayer();
        
        if($inventory instanceof HudPersonalInventoryD || $inventory instanceof HudPersonalInventory){
            Server::getInstance()->getPluginManager()->callEvent(new HudCloseEvent($this->loader, $player, $inventory));
            foreach($player->getInventory()->getContents() as $item){
                if(HudSystem::getInstance()->isHudItem($item)){
                    $player->getInventory()->remove($item);
                }
            }
            return;
        }
    }

    public function drop(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
        if (!HudSystem::getInstance()->isViewDouble($player) && !HudSystem::getInstance()->isViewMini($player)) {
            return;
        }
        
        if(HudSystem::getInstance()->isHudItem($event->getItem())){
            Server::getInstance()->getPluginManager()->callEvent(new HudDropEvent($this->loader, $player, $event->getItem()));
            $event->setCancelled(true);
        }

        return;
    }

    public function regm(PlayerGameModeChangeEvent $event){
        $player = $event->getPlayer();
        if (HudSystem::getInstance()->isViewDouble($player)) {
            HudSystem::getInstance()->closeDouble($player);
            return;
        }elseif(HudSystem::getInstance()->isViewMini($player)){
            HudSystem::getInstance()->closeMini($player);
            return;
        }
    }

    public function damage(EntityDamageEvent $event){
        $entity = $event->getEntity();

        if($entity instanceof Player){
            if (!HudSystem::getInstance()->isViewDouble($entity) && !HudSystem::getInstance()->isViewMini($entity)) {
                return;
            }

            if($event->getBaseDamage() >= $entity->getHealth()){
                $event->setCancelled();
            }

            Server::getInstance()->getPluginManager()->callEvent(new HudDamagePlayerEvent($this->loader, $entity));
            return;
        }
    }
}