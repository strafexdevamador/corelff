<?php

namespace Nathan45\Valea\Events;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Scoreboards\Scoreboard;
use Nathan45\Valea\Tasks\Async\CreateWorld;
use Nathan45\Valea\Tasks\Async\DeleteWorld;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\math\Vector3;

class Event
{
    const PREFIX = "§bLifeNex §f| §eEvento §f> §r";
    const TIME_REMAINING_BEFORE_START = 180;

    private array $players = [];
    private int $timeRemaining;
    private Cache $cache;
    private Loader $plugin;
    private int $round = 0;
    private array $fighters = [];
    private array $winners = [];
    private int $phase = 1;
    private int $unique_id;

    public function __construct(private RPlayer $hoster, private string $type, private bool $private = false, private string|null $password = null)
    {
        $this->timeRemaining = self::TIME_REMAINING_BEFORE_START + time();
        $this->cache = Cache::getInstance();
        $this->plugin = Loader::getInstance();
        $this->unique_id = ++$this->cache->duelCount;
        
        // Verifica se a constante está definida
        $eventWorld = defined(IUtils::class . '::NODEBUFF_EVENT_WORLD_NAME') ? IUtils::NODEBUFF_EVENT_WORLD_NAME : "NodebuffEvent";
        $this->plugin->getServer()->getAsyncPool()->submitTask(new CreateWorld(true, $this->type . "event" . $this->unique_id, $this->plugin->getServer()->getDataPath(), $eventWorld));
        $this->setSpawnLocation();
    }

    public function setSpawnLocation(): void
    {
        $world = $this->getLevel();
        if ($world === null) return;
        
        $world->setSpawnLocation(match ($this->type) {
            "gapple"   => new Vector3(
                defined(IUtils::class . '::EVENT_GAPPLE_X') ? IUtils::EVENT_GAPPLE_X : 256,
                defined(IUtils::class . '::EVENT_GAPPLE_Y') ? IUtils::EVENT_GAPPLE_Y : 70,
                defined(IUtils::class . '::EVENT_GAPPLE_Z') ? IUtils::EVENT_GAPPLE_Z : 256
            ),
            "nodebuff" => new Vector3(
                defined(IUtils::class . '::EVENT_NODEBUFF_X') ? IUtils::EVENT_NODEBUFF_X : 256,
                defined(IUtils::class . '::EVENT_NODEBUFF_Y') ? IUtils::EVENT_NODEBUFF_Y : 70,
                defined(IUtils::class . '::EVENT_NODEBUFF_Z') ? IUtils::EVENT_NODEBUFF_Z : 256
            ),
            default    => new Vector3(
                defined(IUtils::class . '::EVENT_SUMO_X') ? IUtils::EVENT_SUMO_X : 256,
                defined(IUtils::class . '::EVENT_SUMO_Y') ? IUtils::EVENT_SUMO_Y : 70,
                defined(IUtils::class . '::EVENT_SUMO_Z') ? IUtils::EVENT_SUMO_Z : 256
            )
        });
    }

    public function getHoster(): RPlayer
    {
        return $this->hoster;
    }

    public function getName(): string
    {
        return $this->getHoster()->getName();
    }

    public function getTp1(): Vector3
    {
        return match (strtolower($this->type)) {
            "sumo"  => new Vector3(320, 81, 303),
            default => new Vector3(320, 81, 303),
        };
    }

    public function getTp2(): Vector3
    {
        return match (strtolower($this->type)) {
            "sumo"  => new Vector3(320, 81, 315),
            default => new Vector3(320, 81, 315),
        };
    }

    public function getBaseLocation(): Position
    {
        $this->setSpawnLocation();
        $level = $this->getLevel();
        if ($level === null) {
            return new Position(0, 0, 0, null);
        }
        return $level->getSafeSpawn();
    }

    public function getTimeRemaining(): int
    {
        return $this->timeRemaining - time();
    }

    public function hasStarted(): bool
    {
        return ($this->timeRemaining - time()) <= 0;
    }

    public function addPlayer(RPlayer $player): void
    {
        if (count($this->players) >= 32) {
            $player->sendMessage(IUtils::PREFIX . "§cDesculpe, este evento está cheio!");
            return;
        }

        $position = $this->getPosition();
        if ($position === null) {
            $player->sendMessage(IUtils::PREFIX . "§cErro ao entrar no evento: mundo não carregado.");
            return;
        }

        $this->players[] = $player;
        $player->teleport($position);
        $player->sendMessage(IUtils::PREFIX . "§aVocê entrou no evento com sucesso!");

        if ($player->getAllowedScoreboard()) {
            $this->cache->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_EVENT_REMAINING, null, null, $this);
        }
    }

    public function getFighter1(): ?RPlayer
    {
        return $this->fighters[0] ?? null;
    }

    public function getFighter2(): ?RPlayer
    {
        return $this->fighters[1] ?? null;
    }

    public function getRound(): int
    {
        return $this->round;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getLevel(): ?World
    {
        $levelName = $this->getType() . "event" . $this->unique_id;
        $worldManager = $this->plugin->getServer()->getWorldManager();
        
        if (!$worldManager->isWorldLoaded($levelName)) {
            $worldManager->loadWorld($levelName);
        }
        
        return $worldManager->getWorldByName($levelName);
    }

    public function setStart(bool $start = true): void
    {
        shuffle($this->players);

        if (count($this->players) <= 1) {
            $this->getHoster()->sendMessage(IUtils::PREFIX . "§cVocê está sozinho no evento!");
            return;
        }

        ++$this->round;
        $this->setNewRound();
    }

    public function getMaxRounds(): int
    {
        return (int) ceil(count($this->players) / 2);
    }

    public function getPhase(): int
    {
        return $this->phase;
    }

    public function addPhase(): void
    {
        ++$this->phase;

        if ($this->phase > $this->getMaxPhases()) {
            $winner = $this->winners[array_key_first($this->winners)] ?? null;
            if ($winner !== null) {
                $this->endEvent($winner);
            }
            return;
        }

        $previousPhase = $this->phase - 1;
        $this->broadcastMessage("§6Fase §e{$previousPhase} §6terminou, iniciando fase §e{$this->phase}");
        $this->winners = [];
    }

    public function getMaxPhases(): int
    {
        $int = count($this->players);

        if ($int > 16) return 5;
        if ($int > 8)  return 4;
        if ($int > 4)  return 3;
        if ($int > 2)  return 2;

        return 1;
    }

    public function getPosition(): ?Position
    {
        $this->setSpawnLocation();
        $level = $this->getLevel();
        
        if ($level === null) {
            return null;
        }
        
        return $level->getSafeSpawn();
    }

    public function setNewRound(): void
    {
        $this->fighters = [];

        $p1 = $this->players[($this->round * 2) - 2] ?? null;
        $p2 = $this->players[($this->round * 2) - 1] ?? null;

        foreach ([$p1, $p2] as $key => $player) {
            if (!$player instanceof RPlayer) {
                if ($player !== null) {
                    unset($this->players[array_search($player, $this->players, true)]);
                }
                $this->setNewRound();
                return;
            }

            if (in_array($player, $this->winners, true)) {
                $this->setNewRound();
                return;
            }

            $player->reKit($this->type);
            $this->fighters[] = $player;
            $tpMethod = "getTp" . ($key + 1);
            $level = $this->getLevel();
            
            if ($level !== null) {
                $player->teleport(new Position($this->{$tpMethod}()->x, $this->{$tpMethod}()->y, $this->{$tpMethod}()->z, $level));
            }
        }

        if ($p1 instanceof RPlayer && $p2 instanceof RPlayer) {
            $this->broadcastMessage("§6Nova rodada iniciada entre §e{$p2->getName()} §6e §e{$p1->getName()}");
        }
    }

    public function broadcastMessage(string $message): void
    {
        foreach ($this->players as $player) {
            if ($player instanceof RPlayer && $player->isOnline()) {
                $player->sendMessage(self::PREFIX . $message);
            }
        }
    }

    public function hasEliminated(RPlayer $player, RPlayer $killer): void
    {
        $key = array_search($player, $this->players, true);
        if ($key !== false) {
            unset($this->players[$key]);
        }
        
        if ($player->isOnline()) {
            $this->plugin->getServer()->getCommandMap()->dispatch($player, "spawn");
            $player->sendMessage(IUtils::PREFIX . "§cVocê foi eliminado do evento por §e" . $killer->getName());
        }
    }

    public function winRound(RPlayer $player): void
    {
        $baseLocation = $this->getBaseLocation();
        if ($baseLocation->isValid()) {
            $player->teleport($baseLocation);
        }
        
        $this->winners[] = $player;
        $player->sendMessage(self::PREFIX . "§aVocê venceu esta rodada!");

        $validate = 0;

        foreach ($this->players as $key => $p) {
            if (!$p instanceof RPlayer) {
                unset($this->players[$key]);
                continue;
            }

            if (in_array($p, $this->winners, true)) {
                ++$validate;

                if (count($this->players) === $validate) {
                    $this->addPhase();
                }
            }
        }
    }

    public function endEvent(RPlayer $winner): void
    {
        $this->broadcastMessage("§6O evento terminou! O vencedor é §e{$winner->getName()}§6! Ele ganha §a30 Elos§6. Obrigado pela participação!");
        $winner->addElo(30);
        $winner->sendMessage(self::PREFIX . "§aParabéns! Você ganhou 30 Elos por vencer o evento!");
        
        $level = $this->getLevel();
        if ($level !== null) {
            $this->plugin->getServer()->getAsyncPool()->submitTask(new DeleteWorld($this->plugin->getServer()->getDataPath() . "worlds/" . $level->getFolderName(), $level));
        }
        
        // Remove todos os jogadores do evento
        foreach ($this->players as $player) {
            if ($player instanceof RPlayer && $player->isOnline()) {
                $this->plugin->getServer()->getCommandMap()->dispatch($player, "spawn");
            }
        }
    }
}