<?php

namespace SudoManager;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\Server;

class EventListener implements Listener {

    public function __construct(SudoManager $plugin) {
        $this->plugin = $plugin;
    }

    /*public function onJoin(PlayerJoinEvent $ev){
      $this->plugin->setDeop($ev->getPlayer());
    }

    public function onQuit(PlayerQuitEvent $ev){
      $this->plugin->setDeop($ev->getPlayer());
    }*/

}
