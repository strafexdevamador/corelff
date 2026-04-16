<?php

namespace Nathan45\Valea\Tasks\Async;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class MotdAsyncTask extends AsyncTask
{
    private static array $motd = [
        "§7[§bLifeNex§7] §aVoltamos!",
    ];

    private static int $old = 0;

    public function onRun(): void
    {
    }

    public function onCompletion(): void
    {
        Server::getInstance()->getNetwork()->setName(self::$motd[self::$old]);
        self::$old = (self::$old + 1) % count(self::$motd);
    }
}
