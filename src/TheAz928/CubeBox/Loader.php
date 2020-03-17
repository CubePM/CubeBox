<?php
namespace TheAz928\CubeBox;

use pocketmine\block\EnderChest;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;

use pocketmine\tile\Tile;

use pocketmine\utils\TextFormat;

use TheAz928\CubeBox\crate\Crate;

use TheAz928\CubeBox\entity\FloatingText;
use TheAz928\CubeBox\entity\GiftBoxEntity;

use TheAz928\CubeBox\form\ConfirmOpenForm;
use TheAz928\CubeBox\tile\CrateTile;


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

class Loader extends PluginBase implements Listener {

    public const VERSION = "1.0.0";

    public const TAG_Crate = "crate";

    /** @var self */
    protected static $instance;

    /** @var Crate[] */
    protected $crates = [];

    /** @var array */
    protected $creationSession = [];

    /**
     * @return Loader
     */
    public static function getInstance(): Loader {
        return self::$instance;
    }

    public function onLoad() {
        self::$instance = $this;

        $this->saveDefaultConfig();
        if(file_exists($this->getDataFolder() . "/crates/") == false){
            mkdir($this->getDataFolder() . "/crates/");

            $this->saveResource("crates/example.yml");
            $this->saveResource("gift.box.png");
            $this->saveResource("gift.box.json");
        }
        if($this->getConfig()->get("version") !== self::VERSION){
            $this->getLogger()->info("Config version doesn't match this plugin version, updating...");

            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config." . $this->getConfig()->get("version") . ".yml");
            $this->saveResource("config.yml", true);
            $this->reloadConfig();
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        foreach(glob($this->getDataFolder() . "/crates/*.yml") as $file){
            try{
                $crate = new Crate(yaml_parse_file($file));
                $this->crates[$crate->getId()] = $crate;
            }catch(\Exception $exception){
                $this->getLogger()->logException($exception);
            }
        }

        Tile::registerTile(CrateTile::class, ["CrateTile"]);
        Entity::registerEntity(GiftBoxEntity::class, true);
        Entity::registerEntity(FloatingText::class, true);

        $this->getLogger()->info(TextFormat::GREEN . "[CubeBox] " . TextFormat::GRAY . "Loaded " . count($this->crates) . " crates.");
    }

    /**
     * @return Crate[]
     */
    public function getCrates(): array {
        return $this->crates;
    }

    /**
     * @param string $id
     * @return null|Crate
     */
    public function getCrate(string $id): ?Crate {
        return $this->crates[$id] ?? null;
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlock();
        $tile = $block->getLevel()->getTile($block);

        $crate = $this->creationSession[$player->getName()] ?? null;
        if($crate !== null){
            $event->setCancelled();
            if($tile !== null){
                $tile->close();
            }
            $faces = [
                0 => 4,
                1 => 2,
                2 => 5,
                3 => 3
            ];
            $player->getLevel()->setBlock($block, new EnderChest($faces[$player->getDirection()]), true, true);

            $nbt = CrateTile::createNBT($block);
            $nbt->setString("crate", $crate->getId());
            /** @var CrateTile $crateTile */
            $crateTile = new CrateTile($player->getLevel(), $nbt);
            $crateTile->spawnToAll();

            unset($this->creationSession[$player->getName()]);
        }elseif($tile instanceof CrateTile){
            $event->setCancelled();

            if($item->equals($tile->getCrate()->getKey())){
                if($tile->isInUse() == false){
                    $tile->startAnimationSequence($player);
                }else{
                    $player->sendMessage(TextFormat::GRAY . "[CubeBox] That crate is already in use, please wait!");
                }
            }else{
                $player->sendForm(new ConfirmOpenForm($tile));
            }
        }
    }

    /**
     * @param PlayerItemHeldEvent $event
     */
    public function onHeld(PlayerItemHeldEvent $event): void {
        $player = $event->getPlayer();
        $inv = $player->getInventory();

        foreach($inv->getContents() as $slot => $item){
            if($id = $item->getNamedTag()->getString("crate", "") !== ""){
                $crate = $this->getCrate($id);

                if($crate !== null){
                    if($item->equals($crate->getKey()) == false){
                        $key = clone $crate->getKey();
                        $key->setCount($item->getCount());

                        $inv->setItem($slot, $key);
                    }
                }
            }
        }
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($sender instanceof Player){
            $sub = array_shift($args);

            switch($sub){
                case "create":
                    if(isset($args[0]) == false){
                        $sender->sendMessage(TextFormat::GRAY . "[CubeBox] Usage: /cbx create <CRATE ID>");

                        break;
                    }
                    $crate = $this->getCrate($args[0]);

                    if($crate == null){
                        $sender->sendMessage(TextFormat::GRAY . "[CubeBox] Crate not found with ID: " . $args[0]);

                        break;
                    }

                    $this->creationSession[$sender->getName()] = $crate;
                    $sender->sendMessage(TextFormat::GRAY . "[CubeBox] Now tap a block to turn it into a crate");
                break;
                case "key":
                    if(isset($args[0]) == false){
                        $sender->sendMessage(TextFormat::GRAY . "[CubeBox] Usage: /cbx key <PLAYER|all> <CRATE ID> <AMOUNT>");

                        break;
                    }
                    $player = $this->getServer()->getPlayer($args[0]);

                    if($player == null and $args[0] !== "all"){
                        $sender->sendMessage(TextFormat::GRAY . "[CubeBox] Player not found with name: " . $args[0]);

                        break;
                    }
                    if(isset($args[1]) == false){
                        $sender->sendMessage(TextFormat::GRAY . "[CubeBox] Usage: /cbx key <PLAYER|all> <CRATE ID> <AMOUNT>");

                        break;
                    }
                    $crate = $this->getCrate($args[1]);

                    if($crate == null){
                        $sender->sendMessage(TextFormat::GRAY . "[CubeBox] Crate not found with ID: " . $args[1]);

                        break;
                    }

                    $key = clone $crate->getKey();
                    $key->setCount(max(1, intval($args[2])));

                    if($args[0] !== "all"){
                        $player->getInventory()->addItem($key);
                    }else{
                        foreach($this->getServer()->getOnlinePlayers() as $player){
                            $player->getInventory()->addItem($key);
                        }
                    }

                    $player->sendMessage(TextFormat::GREEN . "[CubeBox] Gave keys successfully");
                break;
                default:
                    $sender->sendMessage(TextFormat::GRAY . "[CubeBox] Usage: /cbx key|create");
            }
        }

        return true;
    }
}