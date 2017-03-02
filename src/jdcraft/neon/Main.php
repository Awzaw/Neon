<?php

/*
 * Neon for PocketMine-MP
 * Copyright (C) 2016  JDCRAFT <jdcraftmcpe@gmail.com>
 * http://www.jdcraft.net
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jdcraft\neon;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\block\BlockIds;

class Main extends PluginBase implements Listener {

    /**
     * @var Config
     */
    private $neons;
    private $sessions;
    private $lang;

    public function onEnable() {

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        if(!file_exists($this->getDataFolder())) {
            mkdir($this->getDataFolder());
        }

        $this->saveResource("neons.yml");
        $this->saveResource("language.properties");

        $this->neons = [];
        $this->sessions = [];

        $this->lang = new Config($this->getDataFolder() . "language.properties", Config::PROPERTIES);

        $neonYml = new Config($this->getDataFolder() . "neons.yml", Config::YAML);
        $this->neons = $neonYml->getAll();

        $this->signcols = [
            "RND" => "RND",
            "BLACK" => TextFormat::BLACK,
            "DARK_BLUE" => TextFormat::DARK_BLUE,
            "DARK_GREEN" => TextFormat::DARK_GREEN,
            "DARK_AQUA" => TextFormat::DARK_AQUA,
            "DARK_RED" => TextFormat::DARK_RED,
            "DARK_PURPLE" => TextFormat::DARK_PURPLE,
            "GOLD" => TextFormat::GOLD,
            "GRAY" => TextFormat::GRAY,
            "DARK_GRAY" => TextFormat::DARK_GRAY,
            "BLUE" => TextFormat::BLUE,
            "GREEN" => TextFormat::GREEN,
            "AQUA" => TextFormat::AQUA,
            "RED" => TextFormat::RED,
            "LIGHT_PURPLE" => TextFormat::LIGHT_PURPLE,
            "YELLOW" => TextFormat::YELLOW,
            "WHITE" => TextFormat::WHITE
        ];
    }

    public function onDisable() {
        $this->saveNeons();
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $param) {
        switch($cmd->getName()) {
            case "neon":
                if(!$sender instanceof Player) {
                    $sender->sendMessage(TextFormat::GREEN . $this->getMessage("run-in-game"));
                    break;
                }

                if(!$sender->hasPermission("neon")) {
                    $sender->sendMessage($this->getMessage("no-permission"));
                    break;
                }

                if(isset($param[0]) && (strtolower($param[0]) !== "help")) {

                    switch($param[0]) {

                        case "list":

                            foreach($this->neons as $neon => $neondata) {

                                $sender->sendMessage($neon . " - " . implode(" ", $neondata));
                            }

                            break;

                        case "set":

                            if(count($param) !== 6) {
                                $sender->sendMessage($this->getMessage("invalid-cols"));
                                break;
                            }

                            foreach($this->neons as $neon => $neondata) {

                                if(strtolower($param[1]) === strtolower($neon)) {
                                    $sender->sendMessage($this->getMessage("theme-exists"));
                                    break 2;
                                }
                            }

                            $color1 = strtoupper($param[2]);
                            $color2 = strtoupper($param[3]);
                            $color3 = strtoupper($param[4]);
                            $color4 = strtoupper($param[5]);

                            $colarray = array_keys($this->signcols);

                            if(!in_array($color1, $colarray) || !in_array($color2, $colarray) || !in_array($color3, $colarray) || !in_array($color4, $colarray)) {
                                $sender->sendMessage($this->getMessage("invalid-cols"));
                                break;
                            }

                            $this->neons[strtolower($param[1])] = [$color1, $color2, $color3, $color4];
                            $this->saveNeons();
                            $this->sessions[$sender->getName()] = ["command" => "theme", "params" => strtolower($param[1])];
                            $sender->sendMessage($this->getMessage("set-theme"));
                            return true;

                        case "del":

                            if(count($param) !== 2) {
                                $sender->sendMessage($this->getMessage("invalid-command"));
                                break;
                            }

                            foreach($this->neons as $neon => $neondata) {

                                if(strtolower($param[1]) === strtolower($neon)) {
                                    unset($this->neons[$neon]);
                                    $this->saveNeons();
                                    $sender->sendMessage($this->getMessage("theme-deleted"));
                                    return true;
                                }
                            }

                            $sender->sendMessage($this->getMessage("theme-not-found"));
                            break;


                        case "rnd":

                            $sender->sendMessage($this->getMessage("random-theme"));
                            $this->sessions[$sender->getName()] = ["command" => "random"];
                            return true;

                        case "off":

                            $sender->sendMessage($this->getMessage("session-finished"));
                            unset($this->sessions[$sender->getName()]);
                            return true;

                        default:

                            if(count($param) == 4) {

                                $color1 = strtoupper($param[0]);
                                $color2 = strtoupper($param[1]);
                                $color3 = strtoupper($param[2]);
                                $color4 = strtoupper($param[3]);

                                $colarray = array_keys($this->signcols);

                                if(!in_array($color1, $colarray) || !in_array($color2, $colarray) || !in_array($color3, $colarray) || !in_array($color4, $colarray)) {
                                    $sender->sendMessage($this->getMessage("invalid-cols"));
                                    break;
                                }

                                $this->sessions[$sender->getName()] = ["command" => "names", "params" => $param];
                                $sender->sendMessage($this->getMessage("names-neon"));
                                return true;

                            } else { // Load A Theme for this player
                                $themeexists = false;
                                foreach($this->neons as $neon => $neondata) {
                                    if(strtolower($param[0]) === strtolower($neon)) {
                                        $themeexists = true;
                                        break;
                                    }
                                }

                                if(!$themeexists) {
                                    $sender->sendMessage($this->getMessage("theme-not-found"));
                                    break;
                                }
                                $sender->sendMessage($this->getMessage("theme-neon") . " " . $param[0]);
                                $this->sessions[$sender->getName()] = ["command" => "theme", "params" => strtolower($param[0])];
                                return true;
                            }
                    }
                } else {
                    //LIST HELP
                    $sender->sendMessage(TextFormat::GREEN . $this->getMessage("help-title"));
                    $sender->sendMessage(TextFormat::YELLOW . $this->getMessage("help1"));
                    $sender->sendMessage(TextFormat::YELLOW . $this->getMessage("help2"));
                    $sender->sendMessage(TextFormat::YELLOW . $this->getMessage("help3"));
                    $sender->sendMessage(TextFormat::YELLOW . $this->getMessage("help4"));
                    $sender->sendMessage(TextFormat::YELLOW . $this->getMessage("help5"));
                    $sender->sendMessage(TextFormat::YELLOW . $this->getMessage("help6"));
                    $sender->sendMessage(TextFormat::YELLOW . $this->getMessage("help7"));
                    return true;
                }
        }
        return false;
    }

    public function onInteract(PlayerInteractEvent $event) {
        if ($event->isCancelled()) return;
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            return;
        }

        $block = $event->getBlock();
        if(!in_array($block->getID(), [BlockIds::SIGN_POST, BlockIds::WALL_SIGN])) return;

        $sender = $event->getPlayer();
        if(!isset($this->sessions[$sender->getName()])) {
            return;
        }

        //Player with perms has clicked a SIGN while in NEON mode...

        $command = $this->sessions[$sender->getName()]["command"];

        switch($command) {
            case "theme":

                $theme = $this->sessions[$sender->getName()]["params"];

                $col1 = $this->signcols[strtoupper($this->neons[$theme][0])];
                $col2 = $this->signcols[strtoupper($this->neons[$theme][1])];
                $col3 = $this->signcols[strtoupper($this->neons[$theme][2])];
                $col4 = $this->signcols[strtoupper($this->neons[$theme][3])];

                break;

            case "names":

                $colstringarray = $this->sessions[$sender->getName()]["params"];

                $col1 = $this->signcols[strtoupper($colstringarray[0])];
                $col2 = $this->signcols[strtoupper($colstringarray[1])];
                $col3 = $this->signcols[strtoupper($colstringarray[2])];
                $col4 = $this->signcols[strtoupper($colstringarray[3])];

                break;

            case "random":
                $keys = array_keys($this->signcols);
                $count = count($keys);
                $col1 = $this->signcols[$keys[mt_rand(1, $count - 1)]];
                $col2 = $this->signcols[$keys[mt_rand(1, $count - 1)]];
                $col3 = $this->signcols[$keys[mt_rand(1, $count - 1)]];
                $col4 = $this->signcols[$keys[mt_rand(1, $count - 1)]];
                break;


            default:
                break;
        }

        $v = new Vector3($block->getX(), $block->getY(), $block->getZ());
        $tile = $block->getLevel()->getTile($v);

        $signtext = [
            $col1 . preg_replace("/§./", "", $tile->getText()[0]),
            $col2 . preg_replace("/§./", "", $tile->getText()[1]),
            $col3 . preg_replace("/§./", "", $tile->getText()[2]),
            $col4 . preg_replace("/§./", "", $tile->getText()[3])
        ];

        for($i = 0; $i < count($signtext); $i++) {
            if(substr($signtext[$i], 0, 3) === "RND") {

                $rawtext = str_split(substr($signtext[$i], 3));
                $coloredtext = "";
                $keys = array_keys($this->signcols);
                $count = count($keys);

                foreach($rawtext as $char) {

                    $rndcol = $this->signcols[$keys[mt_rand(1, $count - 1)]];
                    $coloredtext = $coloredtext . $rndcol . $char;
                }
                $signtext[$i] = $coloredtext;
            }
        }

        $tile->setText($signtext[0], $signtext[1], $signtext[2], $signtext[3]);
    }

    public function onPlayerQuit(PlayerQuitEvent $event) {
//Clean up

        if(isset($this->sessions[$event->getPlayer()->getName()])) {
            unset($this->sessions[$event->getPlayer()->getName()]);
        }
    }

    public function getMessage($key, $value = ["%1", "%2"]) {
        if($this->lang->exists($key)) {
            return str_replace(["%1", "%2"], [$value[0], $value[1]], $this->lang->get($key));
        } else {
            return "Language with key \"$key\" does not exist";
        }
    }

    function saveNeons() {
        $neonYml = new Config($this->getDataFolder() . "neons.yml", Config::YAML);
        $neonYml->setAll($this->neons);
        $neonYml->save();
    }

}
