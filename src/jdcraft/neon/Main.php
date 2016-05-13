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

class Main extends PluginBase implements Listener {

    /**
     * @var Config
     */
    private $neons;
    private $sessions;
    private $lang;

    public function onEnable() {
        if (!file_exists($this->getDataFolder())) {
            mkdir($this->getDataFolder());
        }

                $this->saveResource("neons.yml");
                $this->saveResource("language.properties");
                
        $this->neons = array();
        $this->sessions = [];

        $this->lang = new Config($this->getDataFolder() . "language.properties", Config::PROPERTIES);

        $neonYml = new Config($this->getDataFolder() . "neons.yml", Config::YAML);
        $this->neons = $neonYml->getAll();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->signcols = [
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
        switch ($cmd->getName()) {
            case "neon":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TextFormat::GREEN . $this->getMessage("run-in-game"));
                    return true;
                }

                if (!$sender->hasPermission("neon")) {
                    $sender->sendMessage($this->getMessage("no-permission"));
                    break;
                }

                if (isset($param[0]) && (strtolower($param[0]) !== "help")) {

                    switch ($param[0]) {

                        case "list":

                            foreach ($this->neons as $neon => $neondata) {

                                $sender->sendMessage($neon . " - " . implode(" ", $neondata));
                            }

                            break;

                        case "set":

                            if (count($param) !== 6){
                             $sender->sendMessage($this->getMessage("invalid-cols"));  
                             return;
                            }
                            
                            foreach ($this->neons as $neon => $neondata) {
                                
                                if (strtolower($param[1]) === strtolower($neon)){
                                $sender->sendMessage($this->getMessage("theme-exists"));  
                                return;
                                } 
                            }
                            
                            $color1 = strtoupper($param[2]);
                            $color2 = strtoupper($param[3]);
                            $color3 = strtoupper($param[4]);
                            $color4 = strtoupper($param[5]);
                            
                            $colarray = array_keys($this->signcols);
                            
                            if (!in_array($color1, $colarray) || !in_array($color2, $colarray) || !in_array($color3, $colarray) || !in_array($color4, $colarray)){
                            $sender->sendMessage($this->getMessage("invalid-cols")); 
                            return;
                            }
                            
                            $this->neons[strtolower($param[1])] = array($color1, $color2, $color3, $color4);
                            $this->saveNeons();
                            
                            $this->sessions[$sender->getName()] = array("command" => "theme", "params" => strtolower($param[1]));
                            $sender->sendMessage($this->getMessage("set-theme"));
                            
                            break;

                        case "del":

                            if (count($param) !== 2){
                             $sender->sendMessage($this->getMessage("invalid-command"));   
                            }
                            
                            foreach ($this->neons as $neon => $neondata) {

                                if (strtolower($param[1]) === strtolower($neon)){
                                unset($this->neons[$neon]);
                                $this->saveNeons();
                                $sender->sendMessage($this->getMessage("theme-deleted")); 
                                return;
                                } 
                            }
                            
                            $sender->sendMessage($this->getMessage("theme-not-found"));

                            break;


                        case "rnd":

                            $sender->sendMessage($this->getMessage("random-theme"));
                            $this->sessions[$sender->getName()] = array("command" => "random");
                            break;
                        
                        case "off":

                            $sender->sendMessage($this->getMessage("session-finished"));
                            unset($this->sessions[$sender->getName()]);
                            break;

                        default:

                            if (count($param) == 4) {
                                
                            $color1 = strtoupper($param[0]);
                            $color2 = strtoupper($param[1]);
                            $color3 = strtoupper($param[2]);
                            $color4 = strtoupper($param[3]);
                            
                            $colarray = array_keys($this->signcols);
                            
                            if (!in_array($color1, $colarray) || !in_array($color2, $colarray) || !in_array($color3, $colarray) || !in_array($color4, $colarray)){
                            $sender->sendMessage($this->getMessage("invalid-cols")); 
                            return;
                            }
                            
                                $this->sessions[$sender->getName()] = array("command" => "names", "params" => $param);
                                $sender->sendMessage($this->getMessage("names-neon"));
                                break;
                                
                            } else { // Load A Theme for this player
                                
                                $themeexists = false;
                                foreach ($this->neons as $neon => $neondata) {
                                if (strtolower($param[0]) === strtolower($neon)){
                                $themeexists = true; 
                                break;
                                } 
                            }
                            
                            if (!$themeexists) {
                                $sender->sendMessage($this->getMessage("theme-not-found"));
                                return;
                            }
                                $sender->sendMessage($this->getMessage("theme-neon") . " " . $param[0]);
                                $this->sessions[$sender->getName()] = array("command" => "theme", "params" => strtolower($param[0]));
                                break;
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
                    $sender->sendMessage(TextFormat::YELLOW . $this->getMessage("help5"));
                }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {

        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            return;
        }

        $block = $event->getBlock();
        if (!($block->getID() == 63 or $block->getID() == 68 or $block->getID() == 323))
            return;

        $sender = $event->getPlayer();

        if (!isset($this->sessions[$sender->getName()])) {
            return;
        }

        //Player with perms has clicked a SIGN while in NEON mode...

        $command = $this->sessions[$sender->getName()]["command"];
        
        switch ($command) {
            case "theme":

                $theme = $this->sessions[$sender->getName()]["params"];

                $col1 = $this->signcols[strtoupper($this->neons[$theme][0])];
                $col2 = $this->signcols[strtoupper($this->neons[$theme][1])];
                $col3 = $this->signcols[strtoupper($this->neons[$theme][2])];
                $col4 = $this->signcols[strtoupper($this->neons[$theme][3])];

                break;

            case "names":

                $theme = $this->sessions[$sender->getName()]["params"];
                
                $col1 = $this->signcols[strtoupper($this->neons[$theme][0])];
                $col2 = $this->signcols[strtoupper($this->neons[$theme][1])];
                $col3 = $this->signcols[strtoupper($this->neons[$theme][2])];
                $col4 = $this->signcols[strtoupper($this->neons[$theme][3])];

                break;

            case "random":
                $keys = array_keys($this->signcols);
                $count = count($keys);
                $col1 = $this->signcols[$keys[mt_rand(0, $count - 1)]];
                $col2 = $this->signcols[$keys[mt_rand(0, $count - 1)]];
                $col3 = $this->signcols[$keys[mt_rand(0, $count - 1)]];
                $col4 = $this->signcols[$keys[mt_rand(0, $count - 1)]];
                break;


            default:
                break;
        }

        //$block->setText($signtext);          
//
        $v = new Vector3($block->getX(), $block->getY(), $block->getZ());
        $tile = $block->getLevel()->getTile($v);

        $signtext = array(
            $col1 . $tile->getText()[0],
            $col2 . $tile->getText()[1],
            $col3 . $tile->getText()[2],
            $col4 . $tile->getText()[3]
        );

//      $task = new SignTask($this, $tile, $signtext);
//      $this->getServer()->getScheduler()->scheduleDelayedTask($task, 10);

        $tile->setText($signtext[0], $signtext[1], $signtext[2], $signtext[3]);
    }

    public function onPlayerQuit(PlayerQuitEvent $event) {

//Clean up

        if (isset($this->sessions[$event->getPlayer()->getName()])) {
            unset($this->sessions[$event->getPlayer()->getName()]);
        }
    }

    public function getMessage($key, $value = ["%1", "%2"]) {
        if ($this->lang->exists($key)) {
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
