<?php
namespace TheAz928\CubeBox\form;

use onebone\economyapi\EconomyAPI;

use pocketmine\form\Form;

use pocketmine\Player;

use pocketmine\utils\TextFormat;

use TheAz928\CubeBox\tile\CrateTile;

/**
 * CubeBox: The next level crate plugin for PocketMine-MP
 * CopyRight (C)  2020 CubePM (TheAz928)
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class ConfirmOpenForm implements Form {

    /** @var CrateTile */
    protected $tile;

    /**
     * ConfirmOpenForm constructor.
     * @param CrateTile $tile
     */
    public function __construct(CrateTile $tile) {
        $this->tile = $tile;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        $content = TextFormat::GRAY . "Open this crate for ";
        if(($m = $this->tile->getCrate()->getMoneyCost()) > -1){
            $content .= "$" . number_format($m);
        }
        if(($e = $this->tile->getCrate()->getXpCost()) > -1){
            $content .= " and " . number_format($e) . "XP Level(s)";
        }
        $content .= "?";

        return [
            "type" => "form",
            "title" => $this->tile->getCrate()->getName(),
            "content" => $content,
            "buttons" => [
                ["text" => TextFormat::DARK_GREEN . "Yes"],
                ["text" => TextFormat::RED . "No"]
            ]
        ];
    }

    /**
     * @param Player $player
     * @param mixed $data
     */
    public function handleResponse(Player $player, $data): void {
        if($data === 0){
            $API = EconomyAPI::getInstance();
            $crate = $this->tile->getCrate();

            $xpPaid = false;
            $moneyPaid = false;
            if($crate->getMoneyCost() > -1 and $API->myMoney($player) >= $crate->getMoneyCost()){
                $moneyPaid = true;
            }
            if($crate->getXpCost() > -1 and $player->getXpLevel() >= $crate->getXpCost()){
                $xpPaid = true;
            }
            if($xpPaid and $moneyPaid){
                $this->tile->startAnimationSequence($player);
            }else{
                $player->sendMessage(TextFormat::RED . "[CubeBox] You don't have enough money or xp levels!");
            }
        }
    }
}