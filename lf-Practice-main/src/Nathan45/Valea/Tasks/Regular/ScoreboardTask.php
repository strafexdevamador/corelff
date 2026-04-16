<?php

namespace Nathan45\Valea\Tasks\Regular;

use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Listener\PracticeEvents\DuelEndEvent;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Tasks\Async\MotdAsyncTask;
use pocketmine\utils\TextFormat as TE;

class ScoreboardTask extends PracticeTask
{
    public function __construct()
    {
        parent::__construct(self::TASK_SCOREBOARD);
    }

    public function run(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if (!$player instanceof RPlayer) continue;
            if (!$player->isConnected()) continue;

            $player->getScoreboard();

            if ($player->getAllowedScoreboard()) {
                $this->cache->scoreboards[$player->getName()]->update();
            }

            $duelInQueue = $player->isInQueue();
            if ($duelInQueue instanceof Duel) {
                $player->sendTip(
                    TE::WHITE . "Aguardando jogadores > " . TE::AQUA . date("h:i:s", $duelInQueue->getWaitingTimeFor($player)) .
                    TE::WHITE . "\n" . TE::AQUA . $duelInQueue->getMode() . TE::WHITE . " " . $duelInQueue->players / 2 . "v" . $duelInQueue->players / 2 .
                    TE::WHITE . " " . ($duelInQueue->isRanked() ? "ranqueado" : "não ranqueado")
                );
            }

            $activeDuel = $player->getDuel();
            if ($activeDuel instanceof Duel && count($activeDuel->getLevel()->getPlayers()) === 1) {
                (new DuelEndEvent($activeDuel, [$player], []))->call();
            }

            if ($player->getPearlCooldown() === TE::GREEN . "Disponível") {
                $player->getXpManager()->setCurrentTotalXp(0);
            } else {
                $player->getXpManager()->setXpLevel((int) $player->getPearlCooldown());
            }

        }

        $this->plugin->getServer()->getAsyncPool()->submitTask(new MotdAsyncTask());
    }

    public function end(): void
    {
    }

    public function getPeriod(): int
    {
        return 10;
    }
}