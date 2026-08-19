<?php

namespace beretezzka\inventory;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;

final class HudBlockScreen {

    private const CHEST_META = 2;

    private const HEIGHTS = [3, -2, 4, 5];

    private const OFFSETS = [[0, 0], [1, 0], [-1, 0], [0, 1], [0, -1]];

    private const PAIR = [1, 0];

    private function __construct(private Player $player, private array $positions, private array $blocks) {}

    public static function open(Player $player, string $title, bool $double = false) : ?self{
        $level = $player->getLevel();

        if ($level === null) {
            return null;
        }

        $origin = $player->floor();

        foreach (self::HEIGHTS as $height) {
            foreach (self::OFFSETS as [$offsetX, $offsetZ]) {
                $position = $origin->add($offsetX, $height, $offsetZ);

                if (!self::isFree($level, $position)) {
                    continue;
                }

                if (!$double) {
                    return self::create($player, [$position], $title);
                }

                $pair = $position->add(self::PAIR[0], 0, self::PAIR[1]);

                if (!self::isFree($level, $pair)) {
                    continue;
                }

                return self::create($player, [$position, $pair], $title);
            }
        }

        return null;
    }

    public function getPosition() : Vector3{
        return $this->positions[0];
    }

    public function isDouble() : bool{
        return count($this->positions) > 1;
    }

    public function restore() : void{
        if (!$this->player->isOnline()) {
            return;
        }

        foreach ($this->positions as $index => $position) {
            $block = $this->blocks[$index];

            $this->sendBlock($position, $block->getId(), $block->getDamage());
        }
    }

    private static function create(Player $player, array $positions, string $title) : self{
        $level = $player->getLevel();

        $blocks = [];
        foreach ($positions as $position) {
            $blocks[] = $level->getBlock($position);
        }

        $screen = new self($player, $positions, $blocks);
        $screen->render($title);

        return $screen;
    }

    private static function isFree(Level $level, Vector3 $position) : bool{
        if ($position->y < 1 || $position->y >= $level->getWorldHeight()) {
            return false;
        }

        return $level->getTile($position) === null;
    }

    private function render(string $title) : void{
        foreach ($this->positions as $position) {
            $this->sendBlock($position, BlockIds::CHEST, self::CHEST_META);
        }

        if (!$this->isDouble()) {
            $this->sendName($this->positions[0], $title, null, false);

            return;
        }

        $this->sendName($this->positions[0], $title, $this->positions[1], true);
        $this->sendName($this->positions[1], $title, $this->positions[0], false);
    }

    private function sendBlock(Vector3 $position, int $id, int $meta) : void{
        $packet = new UpdateBlockPacket();
        $packet->x = $position->getFloorX();
        $packet->y = $position->getFloorY();
        $packet->z = $position->getFloorZ();
        $packet->blockId = $id;
        $packet->blockMeta = $meta;
        $packet->flags = UpdateBlockPacket::FLAG_ALL;

        $this->player->sendDataPacket($packet);
    }

    private function sendName(Vector3 $position, string $title, ?Vector3 $pair, bool $lead) : void{
        $tags = [
            new StringTag('id', 'Chest'),
            new IntTag('x', $position->getFloorX()),
            new IntTag('y', $position->getFloorY()),
            new IntTag('z', $position->getFloorZ()),
            new StringTag('CustomName', $title),
        ];

        if ($pair !== null) {
            $tags[] = new IntTag('pairx', $pair->getFloorX());
            $tags[] = new IntTag('pairz', $pair->getFloorZ());
            $tags[] = new ByteTag('pairlead', $lead ? 1 : 0);
        }

        $this->player->sendDataPacket(BlockActorDataPacket::create(
            $position->getFloorX(),
            $position->getFloorY(),
            $position->getFloorZ(),
            (string) (new NetworkLittleEndianNBTStream())->write(new CompoundTag('', $tags))
        ));
    }
}
