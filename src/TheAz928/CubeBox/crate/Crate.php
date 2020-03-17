<?php
namespace TheAz928\CubeBox\crate;

use pocketmine\item\Item;

use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

use pocketmine\utils\TextFormat;

use TheAz928\CubeBox\Loader;
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

class Crate {

    /** @var string */
    protected $id;

    /** @var string */
    protected $name = "";

    /** @var string */
    protected $description = "";

    /** @var string[] */
    protected $display = [];

    /** @var int */
    protected $moneyCost = -1;

    /** @var int */
    protected $xpCost = -1;

    /** @var Reward[] */
    protected $rewards = [];

    /** @var int[] */
    protected $RGBA = [
        0,
        0,
        0,
        255
    ];

    /** @var Item */
    protected $key;

    /**
     * Crate constructor.
     * @param array $data
     * @throws \Exception
     */
    public function __construct(array $data) {
        $this->id = $data["id"];
        $this->name = TextFormat::colorize($data["name"]);
        $this->description = TextFormat::colorize(implode("\n&r", $data["description"] ?? []));
        $this->moneyCost = $data["money-cost"] ?? -1;
        $this->xpCost = $data["xp-cost"] ?? -1;
        $this->RGBA = $data["RGBA"];

        foreach($data["rewards"] ?? [] as $datum){
            $this->rewards[] = new Reward($datum);
        }
        foreach($data["display"] ?? [] as $dis){
            $this->display[] = TextFormat::colorize($dis);
        }

        /** key stuff */
        $item = Item::fromString(Loader::getInstance()->getConfig()->get("key"));
        $item->setCustomName($this->name . " key\n\n" . TextFormat::RESET . $this->description);
        $item->setNamedTagEntry(new StringTag("crate", $this->id));
        $item->setNamedTagEntry(new ListTag("ench", []));

        $this->key = $item;
    }

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return string[]
     */
    public function getDisplay(): array {
        return $this->display;
    }

    /**
     * @return Reward[]
     */
    public function getRewards(): array {
        return $this->rewards;
    }

    /**
     * @return int
     */
    public function getMoneyCost(): int {
        return $this->moneyCost;
    }

    /**
     * @return int[]
     */
    public function getRGBA(): array {
        return $this->RGBA;
    }

    /**
     * @return int
     */
    public function getXpCost(): int {
        return $this->xpCost;
    }

    /**
     * @return Item
     */
    public function getKey(): Item {
        return $this->key;
    }

    /**
     * @return Reward
     * @throws \Exception
     */
    public function getRandomReward(): Reward {
        if($this->rewards === []){
            return new Reward([]);
        }

        $re = null;
        while(true){
            foreach($this->rewards as $reward){
                if(Utils::chance($reward->getChance())){
                    $re = $reward;

                    break 2;
                }
            }
        }

        return $re; // I know I could've returned it in the loop, byt phpstorm thought it's funny to mark RED if I didn't do this
    }
}