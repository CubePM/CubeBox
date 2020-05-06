<?php
namespace TheAz928\CubeBox\entity;

use onebone\economyapi\EconomyAPI;

use pocketmine\command\ConsoleCommandSender;

use pocketmine\entity\Human;

use pocketmine\entity\object\ItemEntity;

use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\level\particle\LavaParticle;

use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\PopSound;

use pocketmine\math\Vector3;

use pocketmine\Player;

use pocketmine\utils\TextFormat;

use TheAz928\CubeBox\tile\CrateTile;
use TheAz928\CubeBox\Utils;

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

class GiftBoxEntity extends Human {

    /** @var float */
    protected $gravity = 0.00;

    /** @var Player */
    protected $opener;

    /** @var CrateTile */
    protected $tile;

    /** @var int */
    protected $timeCounter = 0;

    /** @var ItemEntity */
    protected $rewardItem;

    /**
     * GiftBoxEntity constructor.
     * @param CrateTile $tile
     * @param Player $opener
     */
    public function __construct(CrateTile $tile, Player $opener) {
        $nbt = self::createBaseNBT($tile->add(0.5, 0, 0.5));
        Utils::addSkinData($nbt);
        parent::__construct($tile->getLevel(), $nbt);

        $this->opener = $opener;
        $this->tile = $tile;
    }

    protected function initEntity(): void {
        parent::initEntity();

        $this->setScale(0.5);
    }

    /**
     * @return Player
     */
    public function getOpener(): Player {
        return $this->opener;
    }

    /**
     * @return CrateTile
     */
    public function getTile(): CrateTile {
        return $this->tile;
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void {

    }

    /**
     * @param int $tickDiff
     * @return bool
     * @throws \Exception
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->timeCounter += $tickDiff;

        if($this->getOpener()->isOnline() == false){
            $this->flagForDespawn();

            $this->getTile()->setIsInUse(false);
            $this->getTile()->setChestState(false);

            if($this->rewardItem !== null){
                $this->rewardItem->flagForDespawn();
            }

            return true;
        }
        if($this->timeCounter < (20 * 4)){
            $position = $this->asPosition();
            $position->y += 0.03;
            $this->setPosition($position);

            $this->yaw += 15;
            if($this->yaw > 360){
                $this->yaw = 0;
            }
            $this->getLevel()->addParticle(new LavaParticle($this));
            $this->getLevel()->addSound(new FizzSound($this));
        }
        if($this->timeCounter > (20 * 4) and $this->timeCounter < (20 * 7)){
            $this->yaw += 30;
            if($this->yaw > 360){
                $this->yaw = 0;
            }

            $this->getLevel()->addSound(new BlazeShootSound($this, 4));

            for($i = 0; $i < 360; $i += 10){
                $vec = $this->asVector3();
                $vec->x += 0.5 * sin($i);
                $vec->y += $this->eyeHeight + 0.2;
                $vec->z += 0.5 * -cos($i);

                $this->getLevel()->addParticle(new HappyVillagerParticle($vec));
            }
        }
        if($this->timeCounter > (20 * 7.5) and $this->rewardItem == null){
            $this->setInvisible(true);
            $this->getLevel()->addSound(new PopSound($this));
            $this->getLevel()->addParticle(new ExplodeParticle($this));

            $reward = $this->getTile()->getCrate()->getRandomReward();

            /** handle payment stuff here */
            $paid = false;
            $key = $this->getTile()->getCrate()->getKey();
            if($this->getOpener()->getInventory()->contains($key)){
                $this->getOpener()->getInventory()->removeItem($key);
                $paid = true;
            }
            if(!$paid){
                $API = EconomyAPI::getInstance();
                $crate = $this->getTile()->getCrate();

                $xpPaid = false;
                $moneyPaid = false;
                if($crate->getMoneyCost() > -1 and $API->myMoney($this->opener) >= $crate->getMoneyCost()){
                    $moneyPaid = true;
                    $API->reduceMoney($this->opener, $crate->getMoneyCost());
                }
                if($crate->getXpCost() > -1 and $this->getOpener()->getXpLevel() >= $crate->getXpCost()){
                    $xpPaid = true;
                    $this->getOpener()->setXpLevel($this->getOpener()->getXpLevel() - $crate->getXpCost());
                }

                $paid = $xpPaid and $moneyPaid;
            }
            /** payment stuff ends here */

            if($paid){
                foreach($reward->getCommands($this->opener) as $cmd){
                    $this->getLevel()->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                }

                $rarity = $reward->getRarityString();
                if($rarity !== ""){
                    $rarity .= "\n";
                }

                if($reward->getItem()->isNull() == false){
                    $this->rewardItem = $this->getLevel()->dropItem($this, $reward->getItem(), new Vector3(0, 0, 0), 12000);
                    $this->rewardItem->setNameTag($rarity . $reward->getItem()->getName());
                    $this->rewardItem->setNameTagAlwaysVisible(true);
                }

                $this->getOpener()->getInventory()->addItem($reward->getItem());
            }else{
                $this->getOpener()->sendMessage(TextFormat::RED . "[CubeBox] Error. No key, enough money or xp level found.");
            }
        }
        if($this->timeCounter > (20 * 11)){
            $this->flagForDespawn();

            if($this->rewardItem !== null){
                $this->rewardItem->flagForDespawn();
            }

            $this->getTile()->setIsInUse(false);
            $this->getTile()->setChestState(false);
        }

        return parent::entityBaseTick($tickDiff);
    }
}