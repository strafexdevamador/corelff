<?php

namespace Nathan45\Valea\Utils;

use pocketmine\player\Player;
use pocketmine\Server;
use Nathan45\Valea\Loader;

class ClanIntegrationManager {
    
    private Loader $plugin;
    private $clanPlugin = null;

    public function __construct() {
        $this->plugin = Loader::getInstance();
        $this->initClanPlugin();
    }

    /**
     * Inicializa integração com ClanPlugin
     */
    private function initClanPlugin(): void {
        $this->clanPlugin = $this->plugin->getServer()->getPluginManager()->getPlugin("ClanPlugin");
        
        if ($this->clanPlugin !== null && $this->clanPlugin->isEnabled()) {
            $this->plugin->getLogger()->info("§a✓ ClanPlugin integrado com sucesso!");
        } else {
            $this->plugin->getLogger()->warning("§c⚠ ClanPlugin não detectado");
        }
    }

    /**
     * Verifica se ClanPlugin está disponível
     */
    public function isClanPluginAvailable(): bool {
        return $this->clanPlugin !== null && $this->clanPlugin->isEnabled();
    }

    /**
     * Obtém o clã do jogador
     */
    public function getPlayerClan(Player $player) {
        if (!$this->isClanPluginAvailable()) {
            return null;
        }

        try {
            $clanManager = $this->clanPlugin->getClanManager();
            return $clanManager->getClanByPlayer($player->getName());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtém tag do clã formatada
     */
    public function getClanTag(Player $player): ?string {
        $clan = $this->getPlayerClan($player);
        return $clan ? $clan->getTag() : null;
    }

    /**
     * Obtém tag bruta do clã
     */
    public function getClanRawTag(Player $player): ?string {
        $clan = $this->getPlayerClan($player);
        return $clan ? $clan->getRawTag() : null;
    }

    /**
     * Obtém rank do jogador no clã
     */
    public function getPlayerClanRank(Player $player): ?string {
        $clan = $this->getPlayerClan($player);
        if (!$clan) {
            return null;
        }

        return $clan->getRankOf($player->getName());
    }

    /**
     * Adiciona kill ao clã do vencedor
     */
    public function addKillToWinner(Player $winner, Player $loser): void {
        if (!$this->isClanPluginAvailable()) {
            return;
        }

        try {
            $clanManager = $this->clanPlugin->getClanManager();
            $clanManager->addKill($winner->getName());

            $winnerClan = $this->getPlayerClan($winner);
            $loserClan = $this->getPlayerClan($loser);

            if ($winnerClan) {
                $winner->sendMessage("§a+1 kill para seu clã §e" . $winnerClan->getName() . "§a!");
            }

            if ($loserClan) {
                $loser->sendMessage("§c-1 kill para seu clã §e" . $loserClan->getName() . "§c!");
            }
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao adicionar kill ao clã: " . $e->getMessage());
        }
    }

    /**
     * Obtém nome formatado com tag de clã
     */
    public function getFormattedPlayerName(Player $player): string {
        $tag = $this->getClanTag($player);
        if ($tag) {
            return "{$tag} §r{$player->getName()}";
        }
        return $player->getName();
    }

    /**
     * Obtém informações completas do clã
     */
    public function getClanInfo(Player $player): ?array {
        $clan = $this->getPlayerClan($player);
        if (!$clan) {
            return null;
        }

        return [
            'name' => $clan->getName(),
            'tag' => $clan->getTag(),
            'rawTag' => $clan->getRawTag(),
            'level' => $clan->getLevel(),
            'kills' => $clan->getKills(),
            'memberCount' => $clan->getMemberCount(),
            'maxMembers' => $clan->getMaxMembers(),
            'leader' => $clan->getLeader(),
            'playerRank' => $clan->getRankOf($player->getName()),
            'description' => $clan->getDescription(),
        ];
    }
}
