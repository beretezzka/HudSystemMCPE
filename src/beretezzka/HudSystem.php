<?php

namespace beretezzka;

use beretezzka\event\HudUpdateEvent;
use beretezzka\inventory\HudBlockScreen;
use beretezzka\inventory\HudPersonalInventory;
use beretezzka\inventory\HudPersonalInventoryD;
use beretezzka\Events;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class HudSystem extends PluginBase{

	// Version 2.0: Submarine

    private const OPEN_DELAY = 5;

    private const PURGE_DELAYS = [1, 5, 20];

    public array $viewers = ["mini" => [], "double" => []],
                  $lists = ["mini" => [], "double" => []];
	public static $instance;

    public function onEnable(){
		self::$instance = $this;
		GitHubUpdater::check($this);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
            $this->onUpdate();
        }), 15);
        Server::getInstance()->getPluginManager()->registerEvents(new Events($this), $this);
    }

	public static function getInstance(): HudSystem{
		return self::$instance;
	}

    public function onUpdate(){
        Server::getInstance()->getPluginManager()->callEvent(new HudUpdateEvent($this, $this->viewers));
    }

	public function setListMini(Player $player, $list){
		return $this->lists["mini"][$player->getLowerCaseName()] = $list;
	}

	public function setListDouble(Player $player, $list){
		return $this->lists["double"][$player->getLowerCaseName()] = $list;
	}

	public function getListDouble(Player $player){
		return $this->lists["double"][$player->getLowerCaseName()];
	}

	public function getListMini(Player $player){
		return $this->lists["mini"][$player->getLowerCaseName()];
	}

	public function isViewMini(Player $player){
		return isset($this->viewers["mini"][$player->getLowerCaseName()]);
	}

	public function isViewDouble(Player $player){
		return isset($this->viewers["double"][$player->getLowerCaseName()]);
	}

	public function getInventory(Player $player) {
		return isset($this->viewers["double"][$player->getLowerCaseName()]) ? $this->viewers["double"][$player->getLowerCaseName()][0] : $this->viewers["mini"][$player->getLowerCaseName()][0];
	}

	public function open(Player $player, string $name, int $id){
        if(!$this->isSupported($player) || $this->isViewDouble($player) || $this->isViewMini($player)){
			return;
		}

        $screen = HudBlockScreen::open($player, $name, false);

		if($screen === null){
			return;
		}

		$this->setListMini($player, $id);

		$inventory = new HudPersonalInventory(Position::fromObject($screen->getPosition(), $player->getLevel()));

		$this->viewers["mini"][$player->getLowerCaseName()] = [$inventory, $screen];

        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($inventory, $player) : void{
            $this->openWindow($inventory, $player);
        }), self::OPEN_DELAY);
    }

    public function openDouble(Player $player, string $name, int $id){
        if(!$this->isSupported($player) || $this->isViewDouble($player) || $this->isViewMini($player)){
			return;
		}

        $screen = HudBlockScreen::open($player, $name, true);

		if($screen === null){
			return;
		}

		$this->setListDouble($player, $id);

		$origin = $screen->getPosition();
		$pair = $origin->add(1, 0, 0);

		$inventory = new HudPersonalInventoryD(new HudPersonalInventory(Position::fromObject($origin, $player->getLevel())), new HudPersonalInventory(Position::fromObject($pair, $player->getLevel())), Position::fromObject($origin, $player->getLevel()));

		$this->viewers["double"][$player->getLowerCaseName()] = [$inventory, $screen];

        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($inventory, $player) : void{
			$this->openWindow($inventory, $player);
        }), self::OPEN_DELAY);
    }

	public function closeDouble(Player $player){
		if(!$player->isValid() || !$this->isViewDouble($player)){
			return;
		}

		unset($this->lists["double"][$player->getLowerCaseName()]);

		$screen = $this->viewers["double"][$player->getLowerCaseName()][1] ?? null;
		unset($this->viewers["double"][$player->getLowerCaseName()]);

		if($screen !== null){
			$screen->restore();
		}

		$this->purge($player);
		$this->purgeLater($player);
	}

	public function closeMini(Player $player){
		if(!$player->isValid() || !$this->isViewMini($player)){
			return;
		}

		$screen = $this->viewers["mini"][$player->getLowerCaseName()][1] ?? null;
		unset($this->viewers["mini"][$player->getLowerCaseName()]);
		unset($this->lists["mini"][$player->getLowerCaseName()]);

		if($screen !== null){
			$screen->restore();
		}

		$this->purge($player);
		$this->purgeLater($player);
	}

    public function openWindow(Inventory $inventory, Player $player){
        if($player instanceof Player and $player->isOnline()){
			$player->addWindow($inventory);
			return;
		}

		unset($this->viewers["mini"][$player->getLowerCaseName()]);
		unset($this->viewers["double"][$player->getLowerCaseName()]);
		unset($this->lists["mini"][$player->getLowerCaseName()]);
		unset($this->lists["double"][$player->getLowerCaseName()]);
    }

	public function isSupported(Player $player) : bool{
		return $player->isValid() && !$player->isSpectator();
	}

	public function isHudItem(Item $item) : bool{
		return isset($item->getNamedTag()["HudItem"]);
	}

	public function purge(Player $player) : void{
		$this->sweep($player->getCursorInventory());
		$this->sweep($player->getCraftingGrid());
		$this->sweep($player->getUIInventory());
		$this->sweep($player->getInventory());
		$this->sweep($player->getArmorInventory());
		$this->sweep($player->getOffHandInventory());
	}

	public function purgeLater(Player $player) : void{
		foreach (self::PURGE_DELAYS as $delay) {
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($player) : void {
				if (!$player->isOnline()) {
					return;
				}

				$this->purge($player);

				$player->getInventory()->sendContents($player);
			}), $delay);
		}
	}

	private function sweep(Inventory $inventory) : void{
		foreach ($inventory->getContents() as $slot => $item) {
			if (isset($item->getNamedTag()["HudItem"])) {
				$inventory->clear($slot);
			}
		}
	}

	public function fillWindowSlot(ContainerInventory $inventory, int $slot, Item $item) : void{
		if($item->getId() !== ItemIds::AIR){
			$nbt = $item->getNamedTag();
        	$nbt->setByte("HudItem", 1);
			$nbt->setString("Title", $inventory->getTitle());
        	$item->setNamedTag($nbt);
		}
        $inventory->setItem($slot, $item);
	}
}

class GitHubUpdater{
    public static function check(PluginBase $plugin) : void{
		try{
            $context = stream_context_create(["http" => ["timeout" => 1, "user_agent" => "HudSystemMCPE-Updater"]]);
            $f = @file_get_contents("https://raw.githubusercontent.com/beretezzka/HudSystemMCPE/master/plugin.yml", false, $context);
            if($f === false){
                return;
            }
            if(!preg_match('/^version:\s*([\d\.]+)/m', $f, $m)){
                return;
            }
            $oldversion = $plugin->getDescription()->getVersion();
            $newversion = $m[1];
            if(version_compare($oldversion, $newversion, '<')){
                Server::getInstance()->getLogger()->alert("У вас устаревшая версия: $oldversion.");
				Server::getInstance()->getLogger()->alert("Доступна новая: $newversion. ");
				Server::getInstance()->getLogger()->alert("Обновите: https://github.com/beretezzka/HudSystemMCPE");
            }
        }catch(\Throwable $e){
        }
    }
}