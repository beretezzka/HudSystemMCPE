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
use beretezzka\beretmine\Loader;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\PETransaction\TransactionQueue;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
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
        $event = new HudQuitEvent($this->loader, $player);
        Server::getInstance()->getPluginManager()->callEvent($event);
        return;
    }

    public function transaction(InventoryTransactionEvent $event){
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        if (!HudSystem::getInstance()->isViewDouble($player) && !HudSystem::getInstance()->isViewMini($player)) {
            return;
        }

        $event->setCancelled();
        if ($transaction instanceof TransactionQueue) {
            foreach ($transaction->getTransactions() as $_transaction) {
                $inventory = $_transaction->getInventory();
                $item = $inventory->getItem($_transaction->getSlot());
                $event = new HudTransactionEvent($this->loader, $inventory, $player, $item, HudSystem::getInstance()->getList($player));
                Server::getInstance()->getPluginManager()->callEvent($event);
                return;
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
            $event = new HudDoubleOpenEvent($this->loader, $player, $inventory, HudSystem::getInstance()->getList($player));
            Server::getInstance()->getPluginManager()->callEvent($event);
            return;
        }
        if($inventory instanceof HudPersonalInventory){
            $event = new HudOpenEvent($this->loader, $player, $inventory, HudSystem::getInstance()->getList($player));
            Server::getInstance()->getPluginManager()->callEvent($event);
            return;
        }
    }

    public function close(InventoryCloseEvent $event){
        $inventory = $event->getInventory();
        $player = $event->getPlayer();
        
        if($inventory instanceof HudPersonalInventoryD || $inventory instanceof HudPersonalInventory){
            $event = new HudCloseEvent($this->loader, $player, $inventory);
            Server::getInstance()->getPluginManager()->callEvent($event);
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
        $item = $event->getItem();
        $event->setCancelled();

        $event = new HudDropEvent($this->loader, $player, $item);
        Server::getInstance()->getPluginManager()->callEvent($event);
        return;
    }

    public function damage(EntityDamageEvent $event){
        $entity = $event->getEntity();

        if($entity instanceof Player){
            $player = $entity;
            if (!HudSystem::getInstance()->isViewDouble($player) && !HudSystem::getInstance()->isViewMini($player)) {
                return;
            }

            if($event->getBaseDamage() >= $player->getHealth()){
                $event->setCancelled();
            }

            $event = new HudDamagePlayerEvent($this->loader, $player);
            Server::getInstance()->getPluginManager()->callEvent($event);
            return;
        }
    }
}