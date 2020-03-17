<?php
namespace TheAz928\CubeBox;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

use pocketmine\item\Item;

use pocketmine\nbt\JsonNbtParser;

use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;

use pocketmine\nbt\tag\StringTag;

use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

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

class Utils {

    /**
     * @param CompoundTag $nbt
     */
    public static function addSkinData(CompoundTag $nbt): void {
        $image = imagecreatefrompng(Loader::getInstance()->getDataFolder() . "gift.box.png");
        $data = "";
        for($y = 0, $height = imagesy($image); $y < $height; $y++){
            for($x = 0, $width = imagesx($image); $x < $width; $x++){
                $color = imagecolorat($image, $x, $y);
                $data .= pack("c", ($color >> 16) & 0xFF) . pack("c", ($color >> 8) & 0xFF) . pack("c", $color & 0xFF) . pack("c", 255 - (($color & 0x7F000000) >> 23));
            }
        }
        $nbt->setTag(new CompoundTag("Skin", [
            new StringTag("Name", "gift_box"),
            new StringTag("Data", $data),
            new StringTag("GeometryName", "geometry.gift.box"),
            new ByteArrayTag("GeometryData", file_get_contents(Loader::getInstance()->getDataFolder() . "gift.box.json"))
        ]));
    }

    /**
     * @param float $chance
     * @return bool
     */
    public static function chance(float $chance): bool {
        if($chance <= 0){ // there is no 0% chance, it's either 1 to 100 or 100
            return true;
        }

        $count = strlen(substr(strrchr(strval($chance), "."), 1));
        $multiply = intval("1" . str_repeat("0", $count));

        return mt_rand(1, (100 * $multiply)) <= ($chance * $multiply);
    }

    /**
     * @param array $data
     * @return Item
     * @throws \Exception
     */
    public static function buildItem(array $data): Item {
        if($data === []){
            return Item::get(Item::AIR);
        }
        try{
            $item = null;
            $item = Item::fromString($data["item"]);
            $item->setCount(intval($data["count"] ?? 1));
        }catch(\Exception $exception){
            MainLogger::getLogger()->warning("Item: " . ($data["item"] ?? "null") . " is not valid");
        }finally{
            if($item == null){
                return Item::get(Item::AIR);
            }
        }
        if(isset($data["nbt"])){
            $nbt = JsonNbtParser::parseJson($data["nbt"]);
            $item->setNamedTag($nbt);
        }
        if(isset($data["customName"])){
            $item->setCustomName(TextFormat::colorize(str_replace("\n", "\n", $data["customName"])));
        }
        if(isset($data["lore"]) and empty($data["lore"]) == false){
            $item->setCustomName($item->getName() . "\n" . TextFormat::colorize(implode("\n&r", $data["lore"])));
        }
        if(isset($data["enchants"])){
            $enchants = $data["enchants"];

            foreach($enchants as $e){
                $v = explode(" ", $e);
                $id = $v[0];
                $level = $v[1] ?? 1;

                $ench = Enchantment::getEnchantmentByName($id) ?? Enchantment::getEnchantment(intval($id));
                if($ench !== null){
                    $enchant = new EnchantmentInstance($ench, intval($level));

                    $item->addEnchantment($enchant);
                }
            }
        }

        return $item;
    }
}