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
use beretezzka\event\HudUpdateEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\PETransaction\TransactionQueue;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\event\inventory\InventoryPickupItemEvent;
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

    public function updater(HudUpdateEvent $event){
        $double = $event->getDouble();
        $mini = $event->getMini();
        foreach($mini as $nick => $data){
            $player = Server::getInstance()->getPlayer($nick);
            if($player instanceof Player && $player->isCreative() && !$player->isOp() || $player->getPing() > 200){
                $player->sendMessage("§cПопробуйте снова..");
                HudSystem::getInstance()->closeMini($player);
                continue;
            }
        }
        foreach($double as $nick => $data){
            $player = Server::getInstance()->getPlayer($nick);
            if($player instanceof Player && $player->isCreative() && !$player->isOp() || $player->getPing() > 200){
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
        if($event->getTo()->distance($event->getFrom()) > 0.12){
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
        if ($transaction instanceof TransactionQueue) {
            foreach ($transaction->getTransactions() as $_transaction) {
                $inventory = $_transaction->getInventory();
                $item = $inventory->getItem($_transaction->getSlot());
                if(!$item instanceof Item || $item == null){
                    continue;
                }
                if (!HudSystem::getInstance()->isHudItem($item)) {
                    continue;
                }
                Server::getInstance()->getPluginManager()->callEvent(new HudTransactionEvent($this->loader, $inventory, $player, $item));
                continue;
            }
        } elseif ($transaction instanceof InventoryTransaction) {
            foreach ($transaction->getActions() as $_transaction) {
                if ($_transaction instanceof SlotChangeAction) {
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
            $event = new HudDoubleOpenEvent($this->loader, $player);
            Server::getInstance()->getPluginManager()->callEvent($event);
            return;
        }
        if($inventory instanceof HudPersonalInventory){
            $event = new HudOpenEvent($this->loader, $player, $inventory);
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

    public function pickupitem(InventoryPickupItemEvent $event) {
        if(!$event->getInventory() instanceof PlayerInventory) return;
        $player = $event->getInventory()->getHolder();
        if (HudSystem::getInstance()->isViewDouble($player) || HudSystem::getInstance()->isViewMini($player)) {
            $event->setCancelled(true);
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