<?php

namespace beretezzka;

use beretezzka\event\{HudUpdateEvent, HudSwitchListEvent};
use beretezzka\inventory\HudPersonalInventory;
use beretezzka\inventory\HudPersonalInventoryD;
use beretezzka\Events;



use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\tile\Tile;

class HudSystem extends PluginBase{

	// По вопросам: vk.com/To4No_Ne_Beret
	// ебал а нахуя мне эндер чест

    private array $viewers = ["mini" => [], "double" => []], 
                  $lists = ["mini" => [], "double" => []];

    private $loader;
	public static $instance;

    public function onEnable(){
		self::$instance = $this;
		// если оптимизация шатаеться советую оффнуть 
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
            $this->onUpdate();
        }), 10);
        Server::getInstance()->getPluginManager()->registerEvents(new Events($this), $this);
    }

	public static function getInstance(): HudSystem{
		return self::$instance;
	}

	// это я ваще неебу зачем добавил может потом доделаю или вырежу как мать деко
	public function getList(Player $player){
		if($this->isViewDouble($player)){
			return 1;
		}
		if($this->isViewMini($player)){
			return 1;
		}
		return 0;
	}

    public function onUpdate(){
		$event = new HudUpdateEvent($this, $this->viewers);
        Server::getInstance()->getPluginManager()->callEvent($event);
    }

	public function setListMini(Player $player, $list){
		return $this->lists["mini"][$player->getLowerCaseName()] = $list;
	}

	public function setListDouble(Player $player, $list){
		return $this->lists["double"][$player->getLowerCaseName()] = $list;
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

    public function spawnChest(Player $recipient, Block $block){
		$pk = new UpdateBlockPacket();
		$pk->blockId = $block->getId();
		$pk->blockMeta = $block->getDamage();
		$pk->x = $block->x;
		$pk->z = $block->z;
		$pk->y = $block->y;
		$pk->flags = UpdateBlockPacket::FLAG_ALL;
		$recipient->dataPacket($pk);
    }

	public function open(Player $player, string $name){
        if(!$player->isValid()){
			return;
		}

		if($this->isViewDouble($player)){
			return;
		}

        $vector3 = $player->floor()->subtract(0, 3);
        $pairVector3 = $vector3->getSide(Vector3::SIDE_WEST);
        $level = $player->getLevel();
		$blockReplaced = $level->getBlock($vector3);

		$this->spawnChest($player, Block::get(BlockIds::CHEST, 2, Position::fromObject($vector3)));
		
		$tilePacket = new BlockActorDataPacket();
        $tilePacket->x = $vector3->getFloorX();
        $tilePacket->y = $vector3->getFloorY();
        $tilePacket->z = $vector3->getFloorZ();
        $tilePacket->namedtag = (new NetworkLittleEndianNBTStream())->write($this->createTileNBT('Chest', $name, $vector3, $pairVector3));

		$player->dataPacket($tilePacket);

		$this->setListMini($player, 1);

		$inventory = new HudPersonalInventory(Position::fromObject($vector3, $player->getLevel()));

		$this->viewers["mini"][$player->getLowerCaseName()] = [$inventory, $blockReplaced, $player->floor()];

        $this->loader->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($inventory, $player) : void{
            $this->openWindow($inventory, $player, false, false);
        }), 5);
    }

    public function openDouble(Player $player, string $name){
        if(!$player->isValid()){
			return;
		}

		if($this->isViewDouble($player)){
			return;
		}

        $vector3 = $player->floor()->subtract(0, 3);
        $pairVector3 = $vector3->getSide(Vector3::SIDE_WEST);
        $level = $player->getLevel();
		$blockReplaced = $level->getBlock($vector3);
		$blockReplaced2 = $level->getBlock($pairVector3);

		$this->spawnChest($player, Block::get(BlockIds::CHEST, 2, Position::fromObject($vector3)));
		$this->spawnChest($player, Block::get(BlockIds::CHEST, 3, Position::fromObject($pairVector3)));

		$tilePacket = new BlockActorDataPacket();
        $tilePacket->x = $vector3->getFloorX();
        $tilePacket->y = $vector3->getFloorY();
        $tilePacket->z = $vector3->getFloorZ();
        $tilePacket->namedtag = (new NetworkLittleEndianNBTStream())->write($this->createTileNBT('Chest', $name, $vector3, $pairVector3));

		$player->dataPacket($tilePacket);

		$tilePacket = new BlockActorDataPacket();
        $tilePacket->x = $pairVector3->getFloorX();
        $tilePacket->y = $pairVector3->getFloorY();
        $tilePacket->z = $pairVector3->getFloorZ();
        $tilePacket->namedtag = (new NetworkLittleEndianNBTStream())->write($this->createTileNBT('Chest', $name, $pairVector3, $vector3));

		$player->dataPacket($tilePacket);

		$this->setListDouble($player, 1);

		$inventory = new HudPersonalInventoryD(new HudPersonalInventory(Position::fromObject($vector3, $player->getLevel())), new HudPersonalInventory(Position::fromObject($pairVector3, $player->getLevel())), Position::fromObject($vector3, $player->getLevel()));

		$this->viewers["double"][$player->getLowerCaseName()] = [$inventory, $blockReplaced, $blockReplaced2, $player->floor()];

        $this->loader->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($inventory, $player) : void{
            $this->openWindow($inventory, $player, false, false);
        }), 5);
    }

	public function closeDouble(Player $player){
		if(!$player->isValid()){
			return;
		}

		if (!HudSystem::getInstance()->isViewDouble($player)) {
            return;
        }

		if(!$player instanceof Player or !$player->isOnline()){
			return;
		}
		
		if(HudSystem::getInstance()->isViewDouble($player)){
			$blocksReplaced = $this->viewers["double"][$player->getLowerCaseName()][1];
		}else{
			return;
		}

		if($player->isValid()){
			$player->getLevel()->sendBlocks([$player], [$blocksReplaced], UpdateBlockPacket::FLAG_ALL_PRIORITY);
			$tile = $player->getLevel()->getTile($blocksReplaced->asPosition());
			if($tile instanceof Tile){
				$tile->spawnTo($player);
			}
		}
		unset($this->lists["double"][$player->getLowerCaseName()]);
		unset($this->viewers["double"][$player->getLowerCaseName()]);
	}

	public function closeMini(Player $player){
		if(!$player->isValid()){
			return;
		}

		if (!HudSystem::getInstance()->isViewMini($player)) {
            return;
        }
		if(!$player instanceof Player or !$player->isOnline()){
			return;
		}

		
		if(HudSystem::getInstance()->isViewMini($player)){
			$blocksReplaced = $this->viewers["mini"][$player->getLowerCaseName()][1];
		}else{
			return;
		}

		if($player->isValid()){
			$player->getLevel()->sendBlocks([$player], [$blocksReplaced], UpdateBlockPacket::FLAG_ALL_PRIORITY);
			$tile = $player->getLevel()->getTile($blocksReplaced->asPosition());
			if($tile instanceof Tile){
				$tile->spawnTo($player);
			}
		}
		unset($this->viewers["mini"][$player->getLowerCaseName()]);
		unset($this->lists["mini"][$player->getLowerCaseName()]);
	}

    public function openWindow(Inventory $inventory, Player $player, bool $mini = false, $ender = false){
        if($player instanceof Player and $player->isOnline()){
			$player->addWindow($inventory);
			return;
		}

		unset($this->viewers["mini"][$player->getLowerCaseName()]);
		unset($this->viewers["double"][$player->getLowerCaseName()]);
		unset($this->lists["mini"][$player->getLowerCaseName()]);
		unset($this->lists["double"][$player->getLowerCaseName()]);
    }

	public function isHudItem(Item $item){
		return isset($item->getNamedTag()["HudItem"]);
	}

	public function isValidItem(ContainerInventory $inventory, Item $item){
		return $item->getNamedTag()["Title"] == $inventory->getTitle();
	}
    
    public function fillWindowSlot(ContainerInventory $inventory, int $slot, Item $item) : void{
		$nbt = $item->getNamedTag() ?? new CompoundTag();
        $nbt->setByte("HudItem", 1);
		$nbt->setString("Title", $inventory->getTitle());
        $item->setNamedTagEntry($nbt);
        $inventory->setItem($slot, $item);
	}
	
	public static function createTileNBT(string $saveId, string $customName, Vector3 $pos, Vector3 $pairPos) : CompoundTag{
		return new CompoundTag("", [
			new StringTag("id", $saveId),

			new IntTag("x", $pos->x),
			new IntTag("y", $pos->y),
			new IntTag("z", $pos->z),

			new IntTag("pairx", $pairPos->x),
			new IntTag("pairz", $pairPos->z),

			new StringTag("CustomName", $customName)
		]);
	}
}