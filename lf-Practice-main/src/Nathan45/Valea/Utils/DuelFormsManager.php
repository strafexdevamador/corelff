<?php

namespace Nathan45\Valea\Utils;

use pocketmine\player\Player;
use pocketmine\form\Form;
use pocketmine\form\FormValidationException;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use Nathan45\Valea\Loader;

class DuelFormsManager {
    
    private Loader $plugin;
    private Cache $cache;

    public function __construct() {
        $this->plugin = Loader::getInstance();
        $this->cache = Cache::getInstance();
    }

    /**
     * Formulário principal de duelo
     */
    public function createDuelMenuForm(Player $player): void {
        $form = new SimpleForm(function(Player $p, $data) {
            if ($data === null) return;

            switch ($data) {
                case 0: // Ranked Duels
                    $this->createRankedDuelForm($p);
                    break;
                case 1: // Casual Duels
                    $this->createCasualDuelForm($p);
                    break;
                case 2: // Bot Duels
                    $this->createBotDuelForm($p);
                    break;
                case 3: // My Stats
                    $this->createStatsForm($p);
                    break;
            }
        });

        $form->setTitle("§l§bDUEL SYSTEM");
        $form->setContent("§7Escolha um tipo de duelo");
        $form->addButton("§a§lRANKED\n§7Aumenta ELO", 0);
        $form->addButton("§b§lCASOUAL\n§7Sem ranking", 0);
        $form->addButton("§c§lBOT\n§7Treine", 0);
        $form->addButton("§6§lESTATÍSTICAS\n§7Seu perfil", 0);

        $player->sendForm($form);
    }

    /**
     * Formulário de seleção de modo (ranked)
     */
    private function createRankedDuelForm(Player $player): void {
        $form = new SimpleForm(function(Player $p, $data) {
            if ($data === null) return;
            
            $modes = ["nodebuff", "gapple", "sumo", "boxing", "build", "bridge"];
            if (!isset($modes[$data])) return;

            $mode = $modes[$data];
            $this->createPlayerSelectorForm($p, $mode, true);
        });

        $form->setTitle("§l§bDUELO RANKED");
        $form->setContent("§7Selecione o modo");
        $form->addButton("§a§lNODEBUFF", 0);
        $form->addButton("§c§lGAPPLE", 0);
        $form->addButton("§e§lSUMO", 0);
        $form->addButton("§f§lBOXING", 0);
        $form->addButton("§d§lBUILD", 0);
        $form->addButton("§b§lBRIDGE", 0);

        $player->sendForm($form);
    }

    /**
     * Formulário de seleção de modo (casual)
     */
    private function createCasualDuelForm(Player $player): void {
        $form = new SimpleForm(function(Player $p, $data) {
            if ($data === null) return;
            
            $modes = ["nodebuff", "gapple", "sumo", "boxing", "build", "bridge", "final", "fist"];
            if (!isset($modes[$data])) return;

            $mode = $modes[$data];
            $this->createPlayerSelectorForm($p, $mode, false);
        });

        $form->setTitle("§l§bDUELO CASUAL");
        $form->setContent("§7Selecione o modo");
        $form->addButton("§a§lNODEBUFF", 0);
        $form->addButton("§c§lGAPPLE", 0);
        $form->addButton("§e§lSUMO", 0);
        $form->addButton("§f§lBOXING", 0);
        $form->addButton("§d§lBUILD", 0);
        $form->addButton("§b§lBRIDGE", 0);
        $form->addButton("§g§lFINAL", 0);
        $form->addButton("§h§lFIST", 0);

        $player->sendForm($form);
    }

    /**
     * Formulário de seleção de modo (bot)
     */
    private function createBotDuelForm(Player $player): void {
        $form = new SimpleForm(function(Player $p, $data) {
            if ($data === null) return;
            
            $modes = ["nodebuff", "gapple", "sumo", "boxing"];
            if (!isset($modes[$data])) return;

            $mode = $modes[$data];
            $this->createBotDifficultyForm($p, $mode);
        });

        $form->setTitle("§l§bDUELO COM BOT");
        $form->setContent("§7Selecione o modo");
        $form->addButton("§a§lNODEBUFF", 0);
        $form->addButton("§c§lGAPPLE", 0);
        $form->addButton("§e§lSUMO", 0);
        $form->addButton("§f§lBOXING", 0);

        $player->sendForm($form);
    }

    /**
     * Seletor de dificuldade do bot
     */
    private function createBotDifficultyForm(Player $player, string $mode): void {
        $form = new SimpleForm(function(Player $p, $data) use ($mode) {
            if ($data === null) return;

            // Começar duelo com bot
            $this->startBotDuel($p, $mode, $data);
        });

        $form->setTitle("§l§bESCOLHA A DIFICULDADE");
        $form->setContent("Duelo: §b$mode");
        $form->addButton("§aFÁCIL", 0);
        $form->addButton("§eNORMAL", 0);
        $form->addButton("§cDIFÍCIL", 0);

        $player->sendForm($form);
    }

    /**
     * Seletor de jogadores para duelo
     */
    private function createPlayerSelectorForm(Player $player, string $mode, bool $ranked): void {
        $onlinePlayers = [];
        
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
            if ($p->getName() !== $player->getName()) {
                $clan = $this->getClanTag($p);
                $displayName = $clan ? $clan . " §r" . $p->getName() : $p->getName();
                $onlinePlayers[$p->getName()] = $displayName;
            }
        }

        if (empty($onlinePlayers)) {
            $player->sendMessage("§cNenhum jogador online para desafiar!");
            $this->createDuelMenuForm($player);
            return;
        }

        $form = new SimpleForm(function(Player $p, $data) use ($onlinePlayers, $mode, $ranked) {
            if ($data === null) return;

            $playerNames = array_keys($onlinePlayers);
            if (!isset($playerNames[$data])) return;

            $targetName = $playerNames[$data];
            $target = $this->plugin->getServer()->getPlayerExact($targetName);
            
            if (!$target || !$target->isOnline()) {
                $p->sendMessage("§cJogador desconectou!");
                return;
            }

            $this->sendDuelInvite($p, $target, $mode, $ranked);
        });

        $form->setTitle("§l§bESCOLHA UM ADVERSÁRIO");
        $form->setContent("Modo: §b$mode\n" . ($ranked ? "Ranked" : "Casual"));

        foreach ($onlinePlayers as $name) {
            $form->addButton($name, 0);
        }

        $player->sendForm($form);
    }

    /**
     * Envia convite de duelo
     */
    private function sendDuelInvite(Player $inviter, Player $target, string $mode, bool $ranked): void {
        $clan1 = $this->getClanTag($inviter) ?: "Sem Clã";
        $clan2 = $this->getClanTag($target) ?: "Sem Clã";

        $form = new SimpleForm(function(Player $p, $data) use ($inviter, $mode, $ranked) {
            if ($data === null) return;

            if ($data === 0) {
                // Aceitar duelo
                $this->acceptDuelInvite($p, $inviter, $mode, $ranked);
            }
        });

        $form->setTitle("§l§bCONVITE DE DUELO");
        $form->setContent("§b{$inviter->getName()}§7 ({$clan1})\n\n§7está te desafiando para um duelo\n§bModo: §f{$mode}\n§b" . ($ranked ? "RANKED" : "CASUAL"));
        $form->addButton("§a§lACEITAR", 0);
        $form->addButton("§c§lRECUSAR", 0);

        $target->sendForm($form);

        $inviter->sendMessage("§aDesafio enviado para §b{$target->getName()}");
    }

    /**
     * Aceita o convite de duelo
     */
    private function acceptDuelInvite(Player $target, Player $inviter, string $mode, bool $ranked): void {
        if (!$inviter->isOnline()) {
            $target->sendMessage("§cJogador desconectou!");
            return;
        }

        // Criar duelo
        $duelClass = \Nathan45\Valea\Duels\Duel::class;
        $duel = new $duelClass($inviter, $mode, $ranked);
        $duel->addInQueue($target);

        $inviter->sendMessage("§a{$target->getName()} §aaceitou o duelo!");
        $target->sendMessage("§aVocê aceitou o duelo!");
    }

    /**
     * Inicia duelo com bot
     */
    private function startBotDuel(Player $player, string $mode, int $difficulty): void {
        // Lógica para criar bot
        $duelClass = \Nathan45\Valea\Duels\Duel::class;
        $duel = new $duelClass($player, $mode, false);
        
        // Aqui você cria o bot e adiciona à fila
        // $bot = new BotEntity(...);
        // $duel->addInQueue($bot);

        $player->sendMessage("§aEncontrando bot...");
    }

    /**
     * Formulário de estatísticas
     */
    private function createStatsForm(Player $player): void {
        $rPlayer = $this->cache->getPlayer($player->getName());
        
        if (!$rPlayer) {
            $player->sendMessage("§cErro ao carregar dados!");
            return;
        }

        $form = new CustomForm(function(Player $p, $data) {
            if ($data === null) return;
        });

        $form->setTitle("§l§bSUAS ESTATÍSTICAS");
        $form->addLabel("§6NOME: §f" . $player->getName());
        $form->addLabel("§6KILLS: §f" . $rPlayer->getKills());
        $form->addLabel("§6MORTES: §f" . $rPlayer->getDeaths());
        $form->addLabel("§6ELO: §f" . $rPlayer->getElo());
        $form->addLabel("§6MOEDAS: §f" . $rPlayer->getCoins());

        // Tenta obter dados do clã
        $clanTag = $this->getClanTag($player);
        if ($clanTag) {
            $form->addLabel("§6CLÃ: §f" . $clanTag);
        }

        $player->sendForm($form);
    }

    /**
     * Obtém tag do clã do jogador
     */
    private function getClanTag(Player $player): ?string {
        try {
            $clanPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("ClanPlugin");
            
            if ($clanPlugin === null || !$clanPlugin->isEnabled()) {
                return null;
            }

            $clanManager = $clanPlugin->getClanManager();
            $clan = $clanManager->getClanByPlayer($player->getName());

            return $clan ? $clan->getTag() : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
