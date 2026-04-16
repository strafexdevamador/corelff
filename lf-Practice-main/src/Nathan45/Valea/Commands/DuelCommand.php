<?php

namespace Nathan45\Valea\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Nathan45\Valea\Loader;
use Nathan45\Valea\Utils\FormsManager;

class DuelCommand extends Command {
    
    private Loader $plugin;
    private FormsManager $formsManager;

    public function __construct(Loader $plugin) {
        $this->plugin = $plugin;
        $this->formsManager = new FormsManager();
        parent::__construct("duel", "Open duel menu", "/duel");
        $this->setPermission("valea.command.duel");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cUse this command in-game.");
            return false;
        }

        // Abre o menu principal de duel
        $this->openMainDuelMenu($sender);
        return true;
    }

    private function openMainDuelMenu(Player $player): void {
        $this->formsManager->createDuelMenuForm($player);
    }
}
