<?php

namespace Nathan45\Valea\Database;

use Nathan45\Valea\Loader;

class SQLiteDatabase
{
    private \SQLite3 $db;
    private static self $instance;

    public function __construct(string $dataFolder)
    {
        $this->db = new \SQLite3($dataFolder . "practice.db");
        $this->db->enableExceptions(true);
        $this->db->exec("PRAGMA journal_mode = WAL;");
        $this->db->exec("PRAGMA synchronous = NORMAL;");
        self::$instance = $this;
        $this->initTables();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    private function initTables(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS `ban` (
            `player` TEXT,
            `by_name` TEXT,
            `time_sec` INTEGER,
            `reason` TEXT
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS `valea` (
            `player` TEXT PRIMARY KEY,
            `coins` INTEGER DEFAULT 0,
            `kills` INTEGER DEFAULT 0,
            `death` INTEGER DEFAULT 0,
            `rank` INTEGER DEFAULT 0,
            `elo` INTEGER DEFAULT 0,
            `cps` TEXT DEFAULT 'true',
            `ip` TEXT,
            `id` TEXT,
            `friends` TEXT,
            `inventories` TEXT,
            `scoreboard` TEXT DEFAULT 'true',
            `death_message` TEXT DEFAULT 'true'
        )");
    }

    public function exec(string $query): bool
    {
        try {
            return $this->db->exec($query);
        } catch (\Exception $e) {
            Loader::getInstance()->getLogger()->error("SQLite error: " . $e->getMessage() . " | Query: " . $query);
            return false;
        }
    }

    public function query(string $query): array
    {
        $results = [];
        try {
            $result = $this->db->query($query);
            if ($result instanceof \SQLite3Result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $results[] = $row;
                }
            }
        } catch (\Exception $e) {
            Loader::getInstance()->getLogger()->error("SQLite error: " . $e->getMessage());
        }
        return $results;
    }

    public function escapeString(string $value): string
    {
        return \SQLite3::escapeString($value);
    }

    public function close(): void
    {
        $this->db->close();
    }
}
