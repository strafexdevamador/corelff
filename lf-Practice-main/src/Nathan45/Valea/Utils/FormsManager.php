<?php

namespace Nathan45\Valea\Utils;

use Nathan45\Valea\Discord\Embed;
use Nathan45\Valea\Discord\Message;
use Nathan45\Valea\Discord\Webhook;
use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Entities\Bots\NoDeBuffBot;
use Nathan45\Valea\Entities\Bots\SumoBot;
use Nathan45\Valea\Listener\PracticeEvents\PlayerJoinFfaEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Forms\CustomForm;
use Nathan45\Valea\Utils\Forms\ModalForm;
use Nathan45\Valea\Utils\Forms\SimpleForm;
use Nathan45\Valea\Utils\Interfaces\ICache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use Nathan45\Valea\Utils\Interfaces\IUis;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Rank;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as TE;
use pocketmine\world\Position;

class   FormsManager implements IUtils, IUis
{
    const FREEZE = 0;
    const MUTE = 1;
    const VANISH = 2;
    const RANK = 3;
    const REPORT = 4;
    const FRIENDS = 5;
    const UNBAN = 6;
    const CLEAR_SKIN = 7;

    const BY_NAME = 0;
    const TIME = 1;
    const REASON = 2;

    
    private Loader $plugin;
    
    private Utils $utils;
    
    private Cache $cache;

    
    private array $random = [
        "StevePilou5",
        "Pr0GxmerYT927",
        "Xlqniu7",
        "Pilosere505",
        "olopoth78",
        "Venern1050",
        "Trepanton2004",
        "XxxTentacion8236",
        "Viatonplay828",
        "Elmo4Player6",
        "SuperX65",
        "XxPoloYTzX",
        "Brage00Kelvin",
        "CreeperPlayz34",
        "Steppinghorse7",
        "Xq893",
        "EpicPlayer101",
        "Ana7elo3",
        "ChieckenEater93"
    ];

    
    public array $capes = [
        "Vermelha",
        "Azul",
        "Roxa",
        "Preta",
    ];

    public function __construct()
    {
        $this->plugin = Loader::getInstance();
        $this->utils = new Utils();
        $this->cache = Cache::getInstance();
    }

    
    public function sendOnlinePlayersForm(RPlayer $player, int $mode = self::FREEZE) : void{

        $players = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $p){
            if($mode !== self::FRIENDS && in_array($p->getName(), $player->getFriends(), true)) continue;
            $players[] = $p->getName();
        }

        if($mode === self::FRIENDS) unset($players[array_search($player, $players, true)]);

        $form = new CustomForm(function (RPlayer $player, $data) use ($players, $mode) {
            if($data === null || !$player instanceof RPlayer) return;

            $target = (empty($data[1])) ? $players[$data[0]] : $data[1];
            $target = Server::getInstance()->getPlayerExact($target);
            if(!$target instanceof RPlayer AND ($mode !== self::RANK) && ($mode !== self::UNBAN)){
                $player->sendMessage(self::ERROR . TE::WHITE . "Jogador não encontrado, tente novamente");
                return;
            }

            switch ($mode){
                case self::FREEZE:
                    if($target->isFreeze()) {
                        $target->unFreeze();
                        $player->sendMessage(self::PREFIX . TE::WHITE . $target->getName() . TE::AQUA . " foi descongelado!");
                        return;
                    }
                    $target->setFreeze(99999999*20, $player);
                    $player->sendMessage(self::PREFIX . TE::WHITE . $target->getName() . TE::AQUA . " foi congelado!");
                    break;

                case self::UNBAN:
                    $this->utils->unban((empty($data[1])) ? $players[$data[0]] : $data[1], $player);
                    break;

                case self::MUTE:
                    $target->setMuted(!$target->isMuted());
                    $message = (!$target->isMuted()) ? self::PREFIX . TE::WHITE . $target->getName() . TE::AQUA . " foi desmutado!" : self::PREFIX . TE::WHITE . $target->getName() . TE::AQUA . " foi mutado!";
                    $player->sendMessage($message);
                    break;

                case self::VANISH:
                    $player->teleport(new Position($target->getPosition()->x, $target->getPosition()->y + 2, $target->getPosition()->z, $target->getWorld()));
                    $player->sendMessage(self::PREFIX . TE::WHITE . "Teleportado com sucesso para " . TE::AQUA . $target->getName());
                    break;

                case self::RANK:
                    $this->sendRankForm($player,  (empty($data[1])) ? $players[$data[0]] : $data[1]);
                    break;

                case self::REPORT:
                    $this->sendReportPlayerForm($player, $target);
                    break;

                case self::FRIENDS:
                    $player->sendFriendRequestTo($target);
                    break;

                case self::CLEAR_SKIN:
                    $target->setSkin($player->getSkin());
                    $target->sendSkin();
                    $player->sendMessage(IUtils::PREFIX . TE::WHITE . "Skin do jogador resetada");
                    $target->sendMessage(IUtils::PREFIX . TE::WHITE . "Sua skin foi resetada por um membro da staff devido a uma skin inválida.");
                    break;
            }
        });
        $form->setTitle(IUis::PLAYERS_TITLE);
        $form->addDropdown("Selecione um jogador", $players);
        $form->addInput("Ou digite o nome dele: ");
        $form->sendToPlayer($player);
    }

    

    public static function sendWelcomeForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){

        });
        $form->setTitle(TE::AQUA . "- Retorno do LIFE NEX -");
        $form->setContent(TE::WHITE . "\nDepois de fecharmos alguns meses atrás, por conta de falhas mas agora voltamos com tudo!\n \n" . TE::AQUA . "[NOTA]" . TE::WHITE . " Fizemos o servifor de um sistema base muito otimizado, porem ainda em testes, por favor reporte!\n \n" . TE::BLUE . "[DISCORD]" . TE::WHITE . " Sinta-se à vontade para entrar no nosso discord - discord.gg/lifenex\n \nVamos dar nosso melhor para uma boa experiencia no servidor, aproveite esse tempo com nosso servidor <3");
    }

    

    public static function sendRankEditForm(RPlayer $player): void
    {
        $rankId = $player->getRank()->getId();

        $allColors = [
            ["code" => "§4", "tf" => TextFormat::DARK_RED,    "name" => "Vermelho Escuro"],
            ["code" => "§c", "tf" => TextFormat::RED,          "name" => "Vermelho"],
            ["code" => "§6", "tf" => TextFormat::GOLD,         "name" => "Dourado"],
            ["code" => "§e", "tf" => TextFormat::YELLOW,       "name" => "Amarelo"],
            ["code" => "§2", "tf" => TextFormat::DARK_GREEN,   "name" => "Verde Escuro"],
            ["code" => "§a", "tf" => TextFormat::GREEN,        "name" => "Verde"],
            ["code" => "§b", "tf" => TextFormat::AQUA,         "name" => "Azul Claro"],
            ["code" => "§3", "tf" => TextFormat::DARK_AQUA,    "name" => "Azul Escuro"],
            ["code" => "§1", "tf" => TextFormat::DARK_BLUE,    "name" => "Azul Marinho"],
            ["code" => "§9", "tf" => TextFormat::BLUE,         "name" => "Azul"],
            ["code" => "§d", "tf" => TextFormat::LIGHT_PURPLE, "name" => "Roxo Claro"],
            ["code" => "§5", "tf" => TextFormat::DARK_PURPLE,  "name" => "Roxo Escuro"],
            ["code" => "§f", "tf" => TextFormat::WHITE,        "name" => "Branco"],
            ["code" => "§7", "tf" => TextFormat::GRAY,         "name" => "Cinza"],
            ["code" => "§8", "tf" => TextFormat::DARK_GRAY,    "name" => "Cinza Escuro"],
            ["code" => "§0", "tf" => TextFormat::BLACK,        "name" => "Preto"],
        ];

        $allowedIndexes = match ($rankId) {
            Rank::RANK_VALEA                     => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
            Rank::RANK_YOUTUBE                   => [1, 2, 3, 10, 11, 12],
            Rank::RANK_BUILDER                   => [4, 5, 9, 13, 14],
            Rank::RANK_DEVELOPER                 => [6, 7, 8, 9, 10, 11],
            Rank::RANK_HELPER                    => [4, 5],
            Rank::RANK_TMOD                      => [4],
            Rank::RANK_MOD                       => [6, 7],
            Rank::RANK_SRMOD                     => [8],
            Rank::RANK_ADMIN                     => [1],
            Rank::RANK_MANAGER, Rank::RANK_OWNER => [0, 1],
            default                              => [],
        };

        if (empty($allowedIndexes)) {
            $player->sendMessage(IUtils::PREFIX . TE::WHITE . "Você não tem acesso à personalização de cores do rank.");
            return;
        }

        $availableColors = array_values(array_intersect_key($allColors, array_flip($allowedIndexes)));

        $form = new SimpleForm(function (RPlayer $player, $data) use ($availableColors) {
            if ($data === null || !$player instanceof RPlayer) return;
            if (!isset($availableColors[$data])) return;

            $color    = $availableColors[$data];
            $rankName = $player->getRank()->getName();
            $tag      = TE::WHITE . "[" . $color["code"] . $rankName . TE::WHITE . "] " . $color["code"] . $player->getName();

            $player->setNameTag($tag);
            $player->sendMessage(TE::WHITE . "Você alterou a cor do seu rank para " . $color["tf"] . $color["name"] . TE::WHITE . "!");
        });

        $form->setTitle(TE::GOLD . "Editar cor do rank");
        foreach ($availableColors as $color) {
            $form->addButton(
                TE::WHITE . "[" . $color["tf"] . $player->getRank()->getName() . TE::WHITE . "] " .
                $color["tf"] . $player->getName() . TE::GRAY . "\nClique aqui!"
            );
        }
        $form->sendToPlayer($player);
    }

    

    public function sendSocialMenuForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            switch ($data){
                case 0:
                    break;

                case 1:
                    $this->sendFriendForm($player);
                    break;
            }

            $player->sendMessage(TE::WHITE . "Em breve...");
        });
        $form->setTitle(self::SOCIAL_MENU_TITLE);
        $form->setContent(self::SOCIAL_MENU_CONTENT);
        $form->addButton("Clãs", 0, "textures/ui/empty_armor_slot_shield.png");
        $form->addButton("Amigos", 0, "textures/gui/newgui/Friends.png");
        $form->sendToPlayer($player);
    }

    public function sendFriendForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            switch ($data){
                case 0:
                    $this->sendFriendListForm($player);
                    break;

                case 1:
                    $this->sendOnlinePlayersForm($player, self::FRIENDS);
                    break;

                case 2:
                    $this->sendFriendRequestForm($player);
                    break;
            }
        });
        $form->setTitle(self::FRIEND_MENU_TITLE);
        $form->setContent(self::FRIEND_MENU_CONTENT);
        $form->addButton("Lista de amigos", 0, "textures/gui/newgui/Friends.png");
        $form->addButton("Adicionar amigo", 0, "textures/ui/plus.png");
        $form->addButton("Pedidos de amizade\n" . TE::WHITE . "(" . count($player->getFriendsRequests()) . ")", 0, "textures/ui/icon_setting.png");
        $form->sendToPlayer($player);
    }

    public function sendFriendRequestForm(RPlayer $player): void{
        if(count($player->getFriendsRequests()) === 0){
            $player->sendMessage(IMessages::NO_FRIEND_REQUESTS);
            return;
        }

        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            $this->sendAcceptOrDenyRequestForm($player, $data);
        });
        $form->setTitle(self::FRIEND_MENU_TITLE);
        $form->setContent(self::FRIEND_MENU_CONTENT);
        foreach ($player->getFriendsRequests() as $requestor){
            $form->addButton(TE::WHITE . $requestor, -1, "", $requestor);
        }
        $form->sendToPlayer($player);
    }

    public function sendAcceptOrDenyRequestForm(RPlayer $player, string $requestor): void{
        $form = new SimpleForm(function (RPlayer $player, $data) use($requestor){
            if($data === null || !$player instanceof RPlayer) return;

            if($data === 0){
                $player->addFriend($requestor);
            }else{
                $player->removeRequest($requestor);
            }
        });
        $form->setTitle(self::FRIEND_MENU_TITLE);
        $form->setContent(TE::WHITE . "Você quer aceitar ou recusar o pedido de amizade de " . TE::AQUA . $requestor . TE::WHITE . "?");
        $form->addButton(TE::GREEN . "Aceitar");
        $form->addButton(TE::RED . "Recusar");
        $form->sendToPlayer($player);
    }

    public function sendFriendListForm(RPlayer $player): void{
        if(count($player->getFriends()) === 0){
            $player->sendMessage(IMessages::NO_FRIENDS);
            return;
        }
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            $this->getProfileForm($player,$data);
        });
        $form->setTitle(self::FRIEND_MENU_TITLE);
        $form->setContent(self::FRIEND_MENU_CONTENT);
        foreach ($player->getFriends() as $friend){
            $form->addButton($friend, -1, "", $friend);
        }
        $form->sendToPlayer($player);
    }

    

    public function sendRankForm(RPlayer $player, string $target): void{
        $utils = new Utils();
        if(!$utils->accountExist($target)){
            $player->sendMessage(IMessages::PLAYER_NOT_FOUND);
            return;
        }

        $form = new CustomForm(function (RPlayer $player, $data) use($target, $utils){
           if($data === null) return;

           $utils->setRank($target, $data[0]);
           $player->sendMessage(IMessages::SUCCESSFUL);
        });

        $form->setTitle(str_replace("{target}", $target, self::RANK_TITLE));
        $form->addDropdown(self::RANK_CONTENT, $utils->getAllRanks());
        $form->sendToPlayer($player);
    }

    

    public function openTagForm(RPlayer $player){
        $form = new SimpleForm(function (RPlayer $player, $data){;
            if($data === null || !$player instanceof RPlayer) return;

            if($data === "remove"){
                $player->setNameTag($player->getRank()->toString() . $player->getDisplayName());
                $player->sendMessage(IUtils::PREFIX . TE::WHITE . "Tag foi removida");
                return;
            }

            $player->setNameTag($data . TE::WHITE . "| " . $player->getRank()->toString() . $player->getDisplayName());
            $player->sendMessage(IUtils::PREFIX . TE::WHITE . "Tag atualizada para " . $data);

        });
        $form->setTitle(self::TAGS_TITLE);
        $form->setContent(self::TAGS_CONTENT);
        $form->setContent(self::TAGS_CONTENT);
        $form->addButton(TE::DARK_RED . "Rei", -1, '', TE::DARK_RED . "Rei");
        $form->addButton(TE::LIGHT_PURPLE . "Rainha", -1, "", TE::LIGHT_PURPLE . "Rainha");
        $form->addButton(TE::RED . "Remover Tag", -1, '', "remove");
        $form->sendToPlayer($player);
    }

    

    public function openFfaForm(RPlayer $player) : void
    {
        $form = new SimpleForm(function (RPlayer $player, $data) {
            if ($data === null || !$player instanceof RPlayer || $data === "soon") return;

            $player->removeQueue();
            $event = new PlayerJoinFfaEvent($player, $data);
            $event->call();
            if (!$event->isCancelled()) {
                $this->utils->joinFfa($player, $data);
            }
        });
        $form->setTitle(self::FFA_FORM_TITLE);
        $form->setContent(self::FFA_FORM_CONTENT);
        $wm = $this->plugin->getServer()->getWorldManager();
        //$form->addButton("Rush\n" . TE::WHITE . "Jogando : " . TE::AQUA . count(($wm->getWorldByName(IUtils::RUSH_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/bed_red.png", "Rush");
        //$form->addButton("Soup\n" . TE::WHITE . "Jogando : " . TE::AQUA . count(($wm->getWorldByName(IUtils::SOUP_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/mushroom_stew.png", "Soup");
     
        $form->addButton("Gapple\n" . TE::WHITE . "Jogando : " . TE::AQUA . count(($wm->getWorldByName(IUtils::GAPPLE_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/apple_golden.png", "Gapple");
        $form->addButton("NoDeBuff\n" . TE::WHITE . "Jogando : " . TE::AQUA . count(($wm->getWorldByName(IUtils::NODEBUFF_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/potion_bottle_splash_heal.png", "NoDeBuff");
        $form->addButton("Sumo\n" . TE::WHITE . "Jogando : " . TE::AQUA . count(($wm->getWorldByName(IUtils::SUMO_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/fish_cooked.png", "Sumo");
        $form->addButton("Fist\n" . TE::WHITE . "Jogando : " . TE::AQUA . count(($wm->getWorldByName(IUtils::FIST_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/beef_cooked.png", "Fist");
        //$form->addButton("Combo\n" . TE::WHITE . "Jogando : " . TE::AQUA . count(($wm->getWorldByName(IUtils::COMBO_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/fish_pufferfish_raw.png", "Combo");
        $form->sendToPlayer($player);
    }

    

    public function openDuelForm(RPlayer $player): void{
    $form = new SimpleForm(function (RPlayer $player, $data){
        if($data === null || !$player instanceof RPlayer) return;

        switch($data){
            case 0:
                $this->sendPlayerCounterForDuelForm($player, true);
                break;

            case 1:
                $this->sendPlayerCounterForDuelForm($player, false);
                break;

            case 2:
                $player->sendMessage(TE::WHITE . "Em breve...");
                break;

            case 3:
                $player->sendMessage(TE::WHITE . "Em breve...");
                break;
        }
    });
    $form->setTitle(self::DUEL_FORM_TITLE);
    $form->setContent(self::DUEL_FORM_CONTENT);
    $form->addButton("Ranqueado\n" . TE::WHITE . $this->cache->getRankedDuels(), 0, "textures/items/diamond_sword.png", 0);  // ← adicionado valor 0
    $form->addButton("Não Ranqueado\n" . TE::WHITE . $this->cache->getRankedDuels(false), 0, "textures/items/iron_axe.png", 1);  // ← adicionado valor 1
    $form->addButton("Espectar\n" . TE::WHITE . "em breve...", 0, "textures/items/ender_eye.png", 2);  // ← adicionado valor 2
    $form->addButton("Histórico de Duels\n" . TE::WHITE . "em breve...", 0, "textures/items/paper.png", 3);  // ← adicionado valor 3
    $form->sendToPlayer($player);
}

    public function sendPlayerCounterForDuelForm(RPlayer $player, bool $ranked = false): void{
    $form = new SimpleForm(function (RPlayer $player, $data) use ($ranked){
        if($data === null || !$player instanceof RPlayer) return;

        if($data === 4) {
            $this->openDuelForm($player);
        } else {
            $this->sendNewDuelForm($player, $ranked, $data);
        }
    });
    $form->setTitle(self::DUEL_FORM_TITLE);
    $form->setContent(self::DUEL_FORM_CONTENT);
    $form->addButton("1v1\n" . TE::WHITE . $this->cache->getPlayersInDuel($ranked) . " jogadores", 0, "textures/1v1.png", 2);  // 2 players
    $form->addButton("2v2\n" . TE::WHITE . "em breve..", 0, "textures/gui/newgui/Friends.png", 4);  // 4 players
    $form->addButton("3v3\n" . TE::WHITE . "em breve...", 0, "textures/3v3.png", 6);  // 6 players
    $form->sendToPlayer($player);
}
    public function sendNewDuelForm(RPlayer $player, bool $ranked, int $players = 2): void{
        $form = new SimpleForm(function (RPlayer $player, $data) use ($ranked, $players){
            if($data === null || !$player instanceof RPlayer || $data === "soon") return;
            $this->utils->addInQueue($player, $ranked, $players, $data);

        });
        $form->setTitle(self::DUEL_FORM_TITLE);
        $form->setContent(self::DUEL_FORM_CONTENT);
        $form->addButton("NoDeBuff\n" . TE::WHITE . "Fila : " . TE::AQUA . $this->cache->getDuel($ranked, $players, "nodebuff"), 0, "textures/items/potion_bottle_splash_heal.png", "NoDeBuff");
        $form->addButton("Gapple\n" . TE::WHITE . "Fila : " . TE::AQUA . $this->cache->getDuel($ranked, $players, "gapple"), 0, "textures/items/apple_golden.png", "Gapple");
        $form->addButton("Fist\n" . TE::WHITE . "Fila : " . TE::AQUA . $this->cache->getDuel($ranked, $players, "fist"), 0, "textures/items/beef_cooked.png", "Fist");
        $form->addButton("Sumo\n" . TE::WHITE . "Fila : " . TE::AQUA . $this->cache->getDuel($ranked, $players, "sumo"), 0, "textures/items/fish_cooked.png", "Sumo");
        $form->addButton("Build UHC\n" . TE::WHITE . "Fila : " . TE::AQUA . $this->cache->getDuel($ranked, $players, "build"), 0, "textures/items/bucket_lava.png", "Build");
        
      
     
        $form->sendToPlayer($player);
    }

    public function nickNameForm(RPlayer $player):void{
        $form = new SimpleForm(function (RPlayer $player, $data) {
            if($data === null || !$player instanceof RPlayer) return;
            switch($data){
                case 0:
                    $nick = $this->random[array_rand($this->random)];
                    $player->setNameTag(TE::GREEN . $nick);
                    $player->sendMessage(TE::WHITE . "Você agora está com o apelido " . TE::AQUA . $nick);
                    break;
                case 1:
                    self::sendRenameForm($player);
                    break;
                case 2:
                    $player->setNameTag($player->getRank()->toString() . $player->getName());;
                    $player->sendMessage(TE::WHITE . "Você resetou seu apelido");
                    break;
            }
        });

        $form->setTitle(IUis::NICK_TITLE);
        $form->addButton("Aleatório");
        $form->addButton("Personalizado");
        $form->addButton("Limpar");
        $form->sendToPlayer($player);
    }

    public function displayForm(RPlayer $player){
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;
            switch($data){
                case 0:
                    $player->setCpsCounter(($player->getCpsCounter() === "true") ? 'false' : 'true');
                    $player->sendMessage(IMessages::SET_CPS_COUNTER);
                    break;
                case 1:
                    $player->setAllowedScoreboard(!$player->getAllowedScoreboard());
                    if($player->getAllowedScoreboard()) $player->getScoreboard();
                    break;

                case 2:
                    $player->setDeathMessage(!$player->getDeathMessage());
                    $player->sendMessage(IMessages::SET_DEATH_MESSAGE);
                    break;
            }
        });

        $form->setTitle(TE::WHITE . "- " . TE::AQUA . "Tela" . TE::WHITE . " -");
        $form->addButton("Contador de CPS: " . TE::AQUA . (($player->getCpsCounter() === "true") ? "Ativado" : "Desativado") . "\n" . TE::WHITE . (($player->getCpsCounter() === "true") ? "Clique para desativar" : "Clique para ativar"));
        $form->addButton("Scoreboard: " . TE::AQUA . (($player->getAllowedScoreboard()) ? "Ativado" : "Desativado") . "\n" . TE::WHITE . (($player->getAllowedScoreboard()) ? "Clique para desativar" : "Clique para ativar"));
        $form->addButton("Exibir mensagens de morte: " . TE::AQUA . (($player->getDeathMessage()) ? "Ativado" : "Desativado") . "\n" . TE::WHITE . "Clique para " . (($player->getDeathMessage()) ? "desativar" : "ativar"));
        $form->sendToPlayer($player);
    }

    public function privacyForm(RPlayer $player){
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;
            switch ($data){
                case 0:
                    $this->nickNameForm($player);
                    break;
                case 1:
                    $player->chat("/editrank");
                    break;
                case 2:
                    $this->sendChooseCapeForm($player);
                    break;
            }
        });
        $form->setTitle(TE::WHITE . "- " . TE::AQUA . "Jogador" . TE::WHITE . " -");
        $form->addButton("Disfarce");
        $form->addButton("Modificar Rank");
        $form->addButton("Cosméticos");
        $form->sendToPlayer($player);
    }

    public function gamePlayForm(RPlayer $player){
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;
            switch ($data){
                case 0:
                    $player->chat("/autosprint");
                    break;
                case 1:
                    $player->sendMessage(TE::WHITE . "Em breve!");
                    break;
            }
        });
        $form->setTitle(TE::WHITE . "- " . TE::AQUA . "Jogabilidade" . TE::WHITE . " -");
        $form->addButton("Autosprint");
        $form->addButton("Partículas");
        $form->sendToPlayer($player);
    }

    

    public function cosmeticsForm(RPlayer $player) :void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            switch ($data){

                case 0:
                    $this->displayForm($player);
                    break;
                case 1:
                    $this->gamePlayForm($player);
                    break;
                case 2:
                    $this->privacyForm($player);
                    break;
            }
        });
        $form->setTitle(self::COSMETICS_TITLE);
        $form->setContent(self::COSMETICS_CONTENT);
        $form->addButton("Display"); 
        $form->addButton("Jogabilidade"); 
        $form->addButton("Jogador"); 
        $form->sendToPlayer($player);
    }

    private static function sendRenameForm(RPlayer $player) : void{
        $form = new CustomForm(function (RPlayer $player, $data) {
            if($data === null OR !$player instanceof RPlayer) return;

            if($data[0] == null) {
                $player->sendMessage(self::PREFIX . TE::WHITE . "Você deve digitar um nome.");
                self::sendRenameForm($player);
            } else {
                if ($player->hasPermission("OP")) {
                    $player->setDisplayName($data[0]);
                    $player->sendMessage(self::PREFIX . TE::WHITE . "Você mudou seu nome para " . TE::AQUA . $data[0]);
                    $player->setNick(true);
                    $player->setNameTag($player->getRank()->toString() . ' ' . $player->getDisplayName());
                } else {
                    $player->sendMessage(TE::RED . "Você não tem permissão para usar isso");
                }
            }
        });
        $form->setTitle(IUis::CUSTOM_NICK_TITLE);
        $form->addInput("Escolha um apelido personalizado:", "Apelido aqui");
        $form->sendToPlayer($player);
    }

    public function sendChooseCapeForm(RPlayer $player) : void {
        $form = new SimpleForm(function (RPlayer $player, $data) {
            if($data === null OR !$player instanceof RPlayer) return;
            $player->setCape($data);
        });
        $form->setTitle(IUis::CAPES_TITLE);
        $form->setContent(IUis::CAPES_CONTENT);
        foreach ($this->capes as $cape){
            $form->addButton($cape, -1, "", $cape);
        }
        $form->sendToPlayer($player);
    }

    

    
    public function getProfileForm(RPlayer $player, string|RPlayer $target): void{
        if($target instanceof RPlayer) $target = $target->getName();
        if(!isset($this->cache->players[$target])){
            $player->sendMessage(IUtils::PREFIX . TE::RED . "Este jogador não existe!");
            return;
        }

        $form = new CustomForm(function (RPlayer $player, $data){});
        $form->setTitle(str_replace("{player}", $target, self::PROFILE_TITLE));
        $array = $this->cache->players[$target];
        $form->addLabel(TE::WHITE . ">>" . TE::EOL .
            TE::WHITE . "Moedas: " . TE::AQUA . $array[ICache::COINS] . TE::EOL .
            TE::WHITE . "Abates: " . TE::AQUA . $array[ICache::KILLS] . TE::EOL .
            TE::WHITE . "Mortes: " . TE::AQUA . $array[ICache::DEATH] . TE::EOL .
            TE::WHITE . "Rank: " . TE::AQUA . $this->utils->getRank($target, null)->toString() . TE::EOL .
            TE::WHITE . "Elo: " . TE::AQUA . $array[ICache::ELO] . TE::EOL);
        $form->sendToPlayer($player);
    }

    

    public function sendReportPlayerForm(RPlayer $player, RPlayer $target): void{
        $array = [
            "Abuso de Chat - Texto",
            "Trapaça",
            "Nome|Skin Ofensivo ou Inapropriado",
            "Comportamento Desrespeitoso",
            "Ameaças",
            "Outro"
        ];
        $form = new CustomForm(function (RPlayer $player, array $data = null) use ($target, $array){
            if($data === null || !$player instanceof RPlayer || !$target instanceof RPlayer) return;

            $reason = $array[$data[0]];
            $text = $data[1];
            $player->sendMessage(IUtils::PREFIX . TE::WHITE . "Você nos ajuda a melhorar este servidor, obrigado!");

            $msg = new Message();
            $embed = new Embed();
            $msg->setUsername($player->getName());
            $embed->setTitle("Jogador reportado");
            $embed->setColor(self::PURPLE);
            $embed->setFooter(date('l jS \of F Y h:i:s A'));
            $embed->setDescription($player->getName() . " reportou " . $target->getName() . " por " . $reason . " Detalhes : " . $text);
            $msg->addEmbed($embed);
            (new Webhook(IUtils::REPORT_PLAYER_WEBHOOK))->send($msg);
        });
        $form->setTitle(self::BASIC_REPORT_TITLE);
        $form->addDropdown("Selecione :", $array);
        $form->addInput("Seja mais preciso", "Obrigado!", "sem detalhes");
        $form->sendToPlayer($player);
    }

    

    public function sendBotForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer || $data === "soon") return;
            $this->sendTypeBotForm($player, $data);
        });
        $form->setTitle(self::BOT_TITLE);
        $form->setContent(self::BOT_CONTENT);
        $form->addButton("NoDeBuff", -1, "textures/items/potion_bottle_splash_heal.png", "NoDeBuff");
        $form->addButton("Sumo", -1, "textures/items/fish_cooked.png", "Sumo");
        $form->addButton("Gapple\n" . TE::WHITE . "em breve...", -1, "textures/items/apple_golden.png", "soon");
        $form->addButton("Fist\n" . TE::WHITE . "em breve...", -1, "textures/items/beef_cooked.png", "soon");
        $form->sendToPlayer($player);
    }

    public function sendTypeBotForm(RPlayer $player, string $mode): void{
        $form = new SimpleForm(function (RPlayer $player, $data) use ($mode){
            if($data === null or !$player instanceof RPlayer) return;

            switch ($data){
                case 5:
                    $this->sendCustomBotForm($player, $mode);
                    break;

                default:
                    $skin = new Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom", "{}");
                    $location = new Location($player->getPosition()->x - 10, $player->getPosition()->y, $player->getPosition()->z - 10, $player->getWorld(), 0.0, 0.0);
                    if(strtolower($mode) === "nodebuff") $entity = new NoDeBuffBot($location, $skin, null, $player, TE::RED . "Valea " . TE::WHITE . "Bot", $data);
                    else $entity = new SumoBot($location, $skin, null, $player, TE::RED . "Valea " . TE::WHITE . "Bot", $data);
                    if(!$entity instanceof Bot) return;
                    $this->utils->startBotDuel($player, $entity, $mode);
            }

        });
        $form->setTitle(self::TYPE_BOT_TITLE);
        $form->setContent(self::TYPE_BOT_TITLE);
        $form->addButton(TE::GREEN . "Fácil", -1, "", 1);
        $form->addButton(TE::GOLD . "Médio", -1, "", 2);
        $form->addButton(TE::RED . "Difícil", -1, "", 3);
        $form->addButton(TE::DARK_RED . "Hacker", -1, "", 4);
        $form->addButton(TE::AQUA . "Personalizável", -1, "", 5);
        $form->sendToPlayer($player);
    }

    public function sendCustomBotForm(RPlayer $player, string $mode): void{
        $form = new CustomForm(function(RPlayer $player, $data) use ($mode){
            if($data === null || !$player instanceof RPlayer || $data === "soon") return;

            $skin = new Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom", "{}");
            $location = new Location($player->getPosition()->x - 10, $player->getPosition()->y, $player->getPosition()->z - 10, $player->getWorld(), 0.0, 0.0);
            if(strtolower($mode) === "nodebuff") $entity = new NoDeBuffBot($location, $skin, null, $player, TE::RED . "Valea " . TE::WHITE . "Bot", Bot::CUSTOM, $data);
            else $entity = new SumoBot($location, $skin, null, $player, TE::RED . "Valea " . TE::WHITE . "Bot", Bot::CUSTOM, $data);
            if(!$entity instanceof Bot) return;
            $this->utils->startBotDuel($player, $entity, $mode);
        });
        $form->setTitle(self::CUSTOM_BOT_TITLE);
        $form->addSlider("Alcance (1 - 10)", 1, 10, 1, 3, "reach");
        $form->addSlider("Vida (10 -30)", 10, 30, 2, 20, "health");
        $form->addSlider("Precisão (1 - 100)", 1, 100, 1, 50, "accuracy");
        $form->addSlider("Dano (1 - 20)", 1, 20, 1, 8, "damage");
        $form->sendToPlayer($player);
    }

    

    public function sendInventoriesForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, int $data = null){
            if($data === null or !$player instanceof RPlayer) return;
            $inventory = $data + 1;
            $player->setFreeze(99999*60);
            $inventories = new Inventories();
            $player->getInventory()->setContents($inventories->getInventory($inventory));
            $player->sendMessage(TE::WHITE . "Quando terminar, execute o comando " . TE::AQUA . "/inventory salvar" . TE::WHITE . ", se quiser resetar seu inventário, execute " . TE::AQUA . "/inventory resturar");
            $player->setInventoryId($inventory);
        });
        $form->setTitle(self::INVENTORIES_TITLE);
        $form->setContent(self::INVENTORIES_CONTENT);
        $form->addButton("NoDeBuff", 0, "textures/items/potion_bottle_splash_heal.png");
        $form->addButton("Gapple", 0, "textures/items/apple_golden.png");
        $form->addButton("Sumo", 0, "textures/items/fish_cooked.png");
        $form->addButton("Fist", 0, "textures/items/beef_cooked.png");
        //$form->addButton("Rush", 0, "textures/items/bed_red.png");
        //$form->addButton("Soup", 0, "textures/items/mushroom_stew.png");
        $form->addButton("Boxing", 0, "textures/items/diamond_sword.png");
        $form->addButton("Build UHC", 0, "textures/items/bucket_lava.png");
        //$form->addButton("Combo", 0, "textures/items/fish_pufferfish_raw.png");
        $form->sendToPlayer($player);
    }

    

    public function sendRulesForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || $data === 1) $this->sendRulesForm($player);
        });
        $form->setTitle(self::RULES_TITLE);
        $form->setContent(self::RULES_CONTENT);
        $form->addButton(TE::GREEN . "Eu concordo", 0, "textures/ui/check.png");
        $form->addButton(TE::RED . "Eu não concordo", 0, "textures/ui/crossout.png");
        $form->sendToPlayer($player);
    }
}