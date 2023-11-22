<?php

namespace disguise;

use disguise\factory\Disguise;
use disguise\factory\DisguiseFactory;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase {

    /** @var Loader */
    protected static Loader $instance;

    /**
     * @return void
     */
    public function onLoad() : void {
        self::$instance = $this;
    }

    /**
     * @return void
     */
    public function onEnable() : void {
        foreach($this->getResources() as $resource => $fileInfo){
            $this->saveResource($resource, $this->isDevelopmentVersion());
        }
    }

    /**
     * @return void
     */
    public function onDisable() : void {

    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::colorize("&cRun this command in game."));
            return false;
        }
        $factory = DisguiseFactory::getInstance();
        switch($command->getName()){
            case "disguise":
                if(!$sender->hasPermission($command->getPermission())){
                    $sender->sendMessage(TextFormat::colorize("&cYou don't have permission."));
                    return false;
                }
                if(count($args) === 0){
                    $sender->sendMessage(TextFormat::colorize("&cTry: /disguise <on|off> <optional name>"));
                    return false;
                }
                switch($args[0]){
                    case "on":
                        if(count($args) <= 1){
                            $sender->sendMessage(TextFormat::colorize("&cTry: /disguise on <optional name>"));
                            return false;
                        }
                        $newnickname = $args[1];
                        $oldnickname = $sender->getName();

                        if(strlen($newnickname) <= 5){
                            $sender->sendMessage(TextFormat::colorize("&cName can't contain less than 5 chars."));
                            return false;
                        }
                        if(!ctype_alnum($newnickname)){
                            $sender->sendMessage(TextFormat::colorize("&cName can't contain special chars."));
                            return false;
                        }
                        $factory->addToDisguiseList($newnickname, $oldnickname, date("d/m/y h:i:s A"), microtime(true));

                        if($this->disguise($sender, $newnickname, true)){
                            $sender->sendMessage(TextFormat::colorize("&aYour name was changed from &5".$oldnickname."&a to &5".$newnickname."&a."));
                        }
                    break;
                    case "off":
                        if(!$factory->exists($sender->getName())){
                            $sender->sendMessage(TextFormat::colorize("&cUnrecognized nickname for ".$sender->getName()."."));
                            return false;
                        }
                        $disguise = $factory->getDisguiseFromList($sender->getName());

                        if($this->disguise($sender, $disguise->getRealnickname(), false)){
                            $sender->sendMessage(TextFormat::colorize("&aYour name was changed from &5".$disguise->getNickname()."&a to &5".$disguise->getRealnickname()."&a."));
                        }
                    break;
                }
            break;
            case "drealname":
                if(!$sender->hasPermission($command->getPermission())){
                    $sender->sendMessage(TextFormat::colorize("&cYou don't have permission."));
                    return false;
                }
                if(count($args) === 0){
                    $sender->sendMessage(TextFormat::colorize("&cTry: /drealname <near|online|optional name>"));
                    return false;
                }
                switch($args[0]){
                    case "near":
                        /** @var array<int, string> $list */
                        $list = [];
                        foreach($sender->getWorld()->getNearbyEntities($sender->getBoundingBox()->expandedCopy(150, 150, 150)) as $entity){
                            if(!$entity instanceof Player){
                                continue;
                            }
                            if(!$entity->isSurvival() || $entity->getId() === $sender->getId()){
                                continue;
                            }
                            if(!$factory->exists($entity->getName())){
                                continue;
                            }
                            $disguise = DisguiseFactory::getInstance()->getDisguiseFromList($entity->getName());

                            $list[] = TextFormat::colorize("&aReal nickname from &5".$disguise->getNickname()."&a is &5".$disguise->getRealnickname()."&a was changed &5".$disguise->getDate()."&a, elapsed &5".$disguise->elapsed());
                        }
                        if(count($list) === 0){
                            $sender->sendMessage(TextFormat::colorize("&cEmpty ..."));
                            return false;
                        }
                        $sender->sendMessage(TextFormat::colorize("&6Players hiding their name near you are: "));
                        $sender->sendMessage(implode("\n", $list));
                    break;
                    case "online":
                        /** @var array<int, string> $list */
                        $list = [];
                        foreach(Server::getInstance()->getOnlinePlayers() as $player){
                            if(!$factory->exists($player->getName())){
                                continue;
                            }
                            $disguise = DisguiseFactory::getInstance()->getDisguiseFromList($player->getName());

                            $list[] = TextFormat::colorize("&aReal nickname from &5".$disguise->getNickname()."&a is &5".$disguise->getRealnickname()."&a was changed &5".$disguise->getDate()."&a, elapsed &5".$disguise->elapsed());
                        }
                        if(count($list) === 0){
                            $sender->sendMessage(TextFormat::colorize("&cEmpty ..."));
                            return false;
                        }
                        $sender->sendMessage(TextFormat::colorize("&6Players connected hiding their name are: "));
                        $sender->sendMessage(implode("\n", $list));
                    break;
                    default:
                        $name = $args[0];
                        $disguise = DisguiseFactory::getInstance()->getDisguiseFromListWithNickname($name);
                        if(!$disguise instanceof Disguise){
                            $sender->sendMessage(TextFormat::colorize("&cUnrecognized disguise of ".$name."."));
                            return false;
                        }
                        $sender->sendMessage(TextFormat::colorize("&aReal nickname from &5".$disguise->getNickname()."&a is &5".$disguise->getRealnickname()."&a was changed &5".$disguise->getDate()."&a, elapsed &5".$disguise->elapsed()));
                }
                break;
            default:
                $sender->sendMessage(TextFormat::colorize("Unrecognized command. Try /disguise <name>."));
        }
        return true;
    }

    /**
     * @param Player $player
     * @param string $newnickname
     * @param bool $what
     * @return bool
     */
    protected function disguise(Player $player, string $newnickname, bool $what) : bool {
        $newnickname = TextFormat::clean($newnickname);

        if(!Player::isValidUserName($newnickname)){
            $player->sendMessage(TextFormat::colorize("&cInvalid username ".$newnickname));
            return false;
        }
        if(Server::getInstance()->getPlayerExact($newnickname) && $what){
            $player->sendMessage(TextFormat::colorize("&cName ".$newnickname." is already taken or the player is online."));
            return false;
        }
        $player->setDisplayName($newnickname);

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer){
            $onlinePlayer->getNetworkSession()->onPlayerRemoved($player);
            $onlinePlayer->getNetworkSession()->onPlayerAdded($player);
        }

        $player->setNameTag($newnickname);

        if(!$what){
            DisguiseFactory::getInstance()->removeFromDisguiseList($player->getName());
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isDevelopmentVersion() : bool {
        return true;
    }

    /**
     * @return static
     */
    public static function getInstance() : self {
        return self::$instance;
    }
}

?>