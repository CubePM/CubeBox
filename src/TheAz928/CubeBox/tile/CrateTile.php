<?php
namespace TheAz928\CubeBox\tile;

use pocketmine\block\Block;

use pocketmine\level\particle\DustParticle;

use pocketmine\nbt\tag\CompoundTag;

use pocketmine\network\mcpe\protocol\BlockEventPacket;

use pocketmine\Player;
use pocketmine\tile\Spawnable;

use pocketmine\utils\TextFormat;

use TheAz928\CubeBox\crate\Crate;

use TheAz928\CubeBox\entity\FloatingText;
use TheAz928\CubeBox\entity\GiftBoxEntity;

use TheAz928\CubeBox\Loader;

/**
 * CubeBox: The next level crate plugin for PocketMine-MP
 * CopyRight (C)  2020 CubePM (TheAz928)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class CrateTile extends Spawnable {

    /** @var bool */
    protected $isInUse = false;

    /** @var DustParticle[] */
    protected $particles = [];

    /** @var int */
    protected $particleCounter = 0;

    /** @var Crate */
    protected $crate;

    /** @var int */
    protected $displayCounter = 0;

    /** @var FloatingText */
    protected $displayEntity;

    /**
     * @return FloatingText
     */
    public function getDisplayEntity(): FloatingText {
        return $this->displayEntity;
    }

    /**
     * @return Crate
     */
    public function getCrate(): Crate {
        return $this->crate;
    }

    /**
     * @return bool
     */
    public function isInUse(): bool {
        return $this->isInUse;
    }

    /**
     * @param bool $isInUse
     */
    public function setIsInUse(bool $isInUse): void {
        $this->isInUse = $isInUse;
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function readSaveData(CompoundTag $nbt): void {
        $crateId = $nbt->getString("crate", "");

        if(($crate = Loader::getInstance()->getCrate($crateId)) !== null){
            $this->crate = $crate;
        }

        $this->scheduleUpdate();
        if($this->crate !== null){
            $pos = $this->asPosition();
            $pos->x += 0.5;
            $pos->y += 2.4;
            $pos->z += 0.5;

            $this->displayEntity = FloatingText::spawn($pos, $this->getCrate()->getName());
        }
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function writeSaveData(CompoundTag $nbt): void {
        $nbt->setString("crate", $this->crate->getId());
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function addAdditionalSpawnData(CompoundTag $nbt): void {
        $nbt->setString("id", "EnderChest");
    }

    public function close(): void {
        parent::close();

        if($this->displayEntity !== null){
            $this->displayEntity->close();
        }
    }

    /**
     * @param bool $open
     */
    public function setChestState(bool $open) : void{
        $holder = $this->getBlock();

        $pk = new BlockEventPacket();
        $pk->x = (int) $holder->x;
        $pk->y = (int) $holder->y;
        $pk->z = (int) $holder->z;
        $pk->eventType = 1;
        $pk->eventData = $open ? 1 : 0;
        $holder->getLevel()->broadcastPacketToViewers($holder, $pk);
    }

    /**
     * @param Player $opener
     */
    public function startAnimationSequence(Player $opener): void {
        $this->setIsInUse(true);
        $this->setChestState(true);

        (new GiftBoxEntity($this, $opener))->spawnToAll();
    }

    /**
     * @return bool
     */
    public function onUpdate(): bool {
        if($this->getBlock()->getId() !== Block::ENDER_CHEST or $this->crate == null){
            $this->close();

            return false;
        }
        if(Loader::getInstance()->getConfig()->get("particles")){
            if($this->particles === []){
                for($i = 0; $i < 360; $i++){
                    $pos = $this->asVector3();
                    $pos->x = ($pos->x + 0.5) + sin($i);
                    $pos->y += 0.9;
                    $pos->z = ($pos->z + 0.5) + -cos($i);

                    $dust1 = new DustParticle($pos->asVector3(), ...$this->getCrate()->getRGBA());

                    $pos = $this->asVector3();

                    $pos->x = ($pos->x + 0.5) + -sin($i);
                    $pos->z = ($pos->z + 0.5) + cos($i);

                    $dust2 = new DustParticle($pos->asVector3(), ...$this->getCrate()->getRGBA());

                    $this->particles[] = [$dust1, $dust2];
                }
            }

            $particles = $this->particles[$this->particleCounter];

            foreach($particles as $particle){
                $this->getLevel()->addParticle($particle);
            }
            $this->particleCounter++;

            $maxIndex = count($this->particles) - 1;
            if($this->particleCounter > $maxIndex){
                $this->particleCounter = 0;
            }
        }
        if(($this->getLevel()->getServer()->getTick() % 100) === 0){
            $display = "";
            $displays = $this->getCrate()->getDisplay();

            if($displays !== []){
                $display = $displays[$this->displayCounter];

                $this->displayCounter++;
                $maxIndex = count($displays) - 1;
                if($this->displayCounter > $maxIndex){
                    $this->displayCounter = 0;
                }
            }
            if($this->getDisplayEntity()->isClosed() == false){
                if($this->getDisplayEntity()->isClosed()){
                    $pos = $this->asPosition();
                    $pos->x += 0.5;
                    $pos->y += 2.4;
                    $pos->z += 0.5;

                    $this->displayEntity = FloatingText::spawn($pos, "");
                }

                $this->getDisplayEntity()->setNameTag($this->getCrate()->getName() . "\n\n" . TextFormat::RESET . $display);
            }
        }

        return true;
    }
}