<?php
namespace TheAz928\CubeBox\crate;

use pocketmine\item\Item;

use pocketmine\Player;

use pocketmine\utils\TextFormat;
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

class Reward {

    /** @var Item */
    protected $item;

    /** @var string[] */
    protected $commands = [];

    /** @var float */
    protected $chance = 100.00;

    /** @var string */
    protected $rarityString = "";

    /**
     * Reward constructor.
     * @param array $data
     * @throws \Exception
     */
    public function __construct(array $data) {
        $this->item = Utils::buildItem($data);
        $this->chance = $data["chance"] ?? 100;
        $this->commands = $data["commands"] ?? [];
        $this->rarityString = TextFormat::colorize($data["rarityString"] ?? "");
    }

    /**
     * @return string
     */
    public function getRarityString(): string {
        return $this->rarityString;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return float
     */
    public function getChance(): float {
        return $this->chance;
    }

    /**
     * @param null|Player $player
     * @return string[]
     */
    public function getCommands(?Player $player = null): array {
        if($player !== null){
            $commands = [];

            foreach($this->commands as $command){
                $commands[] = str_replace([
                    ".player.",
                    ".nametag.",
                    ".display_name.",
                    ".world.",
                    ".x.",
                    ".y.",
                    ".z."
                ], [
                    $player->getName(),
                    $player->getNameTag(),
                    $player->getDisplayName(),
                    $player->getLevel()->getName(),
                    $player->x,
                    $player->y,
                    $player->z
                ], $command);
            }

            return $commands;
        }

        return $this->commands;
    }
}