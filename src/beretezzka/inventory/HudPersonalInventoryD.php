<?php


namespace beretezzka\inventory;

use pocketmine\inventory\InventoryHolder;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\item\Item;
use beretezzka\beretmine\npc\auction\Auction;
use pocketmine\inventory\ChestInventory;

use function array_merge;
use function array_slice;
use function count;

class HudPersonalInventoryD extends HudPersonalInventory implements InventoryHolder{
	private $left;
	private $right;

	public function __construct(HudPersonalInventory $left, HudPersonalInventory $right, Position $holder){
		$this->left = $left;
		$this->right = $right;
		parent::__construct($holder);
	}

	public function getName() : string{
		return "HUD_PERSONAL_INVENTORY";
	}

	public function getDefaultSize() : int{
		return 54;
	}

	public function onOpen(Player $who): void
    {
		if(count($this->getViewers()) === 1){
			$this->right->broadcastBlockEventPacket(true);
		}
		parent::onOpen($who);
	}

	public function onClose(Player $who): void
    {
		if(count($this->getViewers()) === 1){
			$this->right->broadcastBlockEventPacket(false);
		}
		parent::onClose($who);
		Auction::getInstance()->addToDelayedClose($who);
	}

	public function getItem(int $index) : Item{
		return $index < $this->left->getSize() ? $this->left->getItem($index) : $this->right->getItem($index - $this->left->getSize());
	}

	public function setItem(int $index, Item $item, bool $send = true) : bool{
		$old = $this->getItem($index);
		if($index < $this->left->getSize() ? $this->left->setItem($index, $item, $send) : $this->right->setItem($index - $this->left->getSize(), $item, $send)){
			$this->onSlotChange($index, $old, $send);
			return true;
		}
		return false;
	}

    public function getContents(bool $includeEmpty = false): array
    {
        if ($this->getLeftSide() instanceof ChestInventory && $this->getRightSide() instanceof ChestInventory) {
            $result = $this->getLeftSide()->getContents($includeEmpty);
            $leftSize = $this->getLeftSide()->getSize();

            foreach ($this->getRightSide()->getContents($includeEmpty) as $i => $item) {
                $result[$i + $leftSize] = $item;
            }
        }
        return $result;
    }

	public function setContents(array $items, bool $send = true) : void{
		$size = $this->getSize();
		if(count($items) > $size){
			$items = array_slice($items, 0, $size, true);
		}

		$leftSize = $this->left->getSize();

		for($i = 0; $i < $size; ++$i){
			if(!isset($items[$i])){
				if(($i < $leftSize and isset($this->left->slots[$i])) or isset($this->right->slots[$i - $leftSize])){
					$this->clear($i, false);
				}
			}elseif(!$this->setItem($i, $items[$i], false)){
				$this->clear($i, false);
			}
		}

		if($send){
			$this->sendContents($this->getViewers());
		}
	}

	public function firstOccupied(): int
    {
		return -1;
	}

	/**
	 * @return ChestInventory
	 */
	public function getLeftSide() : HudPersonalInventory{
		return $this->left;
	}

	/**
	 * @return ChestInventory
	 */
	public function getRightSide() : HudPersonalInventory{
		return $this->right;
	}

	public function getInventory() : HudPersonalInventory{
		return $this;
	}

	public function invalidate(){
		$this->left = null;
		$this->right = null;
	}
}
