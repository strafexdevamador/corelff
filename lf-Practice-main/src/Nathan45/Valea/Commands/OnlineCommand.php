<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Loader;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class OnlineCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("online", "LF - Online Commando", "/online");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();
        $sender->sendMessage(IUtils::PREFIX . "§aLista de player online (" . count($onlinePlayers) . "/" . $this->plugin->getServer()->getMaxPlayers() . ")");
        $names = [];
        foreach ($onlinePlayers as $p) {
            $names[] = $p->getName();
        }
        $sender->sendMessage(implode("§7, §6", $names));
    }
}