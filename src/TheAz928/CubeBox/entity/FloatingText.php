<?php
namespace TheAz928\CubeBox\entity;

use pocketmine\entity\Living;

use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\level\Position;


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

class FloatingText extends Living {

    public const NETWORK_ID = self::VEX;

    /** @var float */
    public $height = 1.00;

    /** @var float */
    public $width = 1.00;

    /** @var float */
    protected $gravity = 0.00;

    /**
     * @param Position $pos
     * @param string $text
     * @return FloatingText
     */
    public static function spawn(Position $pos, string $text): self {
        $entity = new self($pos->getLevel(), self::createBaseNBT($pos));
        $entity->setNameTag($text);
        $entity->spawnToAll();

        return $entity;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Floating Text";
    }

    protected function initEntity(): void {
        parent::initEntity();

        $this->setImmobile(true);
        $this->setScale(0.0001);
        $this->setNameTagAlwaysVisible(true);
        $this->setCanSaveWithChunk(false);
    }

    /**
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->motion->setComponents(0, 0, 0);

        return parent::entityBaseTick($tickDiff);
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void {

    }
}