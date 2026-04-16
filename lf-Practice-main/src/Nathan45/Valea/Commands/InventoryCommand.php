<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Inventories;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class InventoryCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("inventory", "LF - Inventory Command", "/inventory [salvar|restaurar]");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer) return;
        if ($sender->getWorld()->getFolderName() !== IUtils::LOBBY_WORLD_NAME) return;

        if (!isset($args[0])) {
            (new FormsManager())->sendInventoriesForm($sender);
            return;
        }

        if (!$sender->isFreeze() || $sender->getInventoryId() === 0) {
            $sender->sendMessage(IUtils::PREFIX . "§crun /inventory before set or reset this one.");
        }

        switch ($args[0]) {
            case "salvar":
                $sender->setInventory($sender->getInventory()->getContents());
                $this->plugin->getServer()->getCommandMap()->dispatch($sender, "spawn");
                $sender->unFreeze();
                $sender->setInventoryId(0);
                break;

            case "restaurar":
                $inventories = new Inventories();
                $sender->getInventory()->setContents($inventories->getInventory($sender->getInventoryId()));
                $sender->setInventory($sender->getInventory()->getContents());
                break;

            default:
                $sender->sendMessage(IUtils::PREFIX . $this->usageMessage);
        }
    }
}