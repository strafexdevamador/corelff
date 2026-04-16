<?php

namespace Nathan45\Valea\Utils\Interfaces;
use pocketmine\utils\TextFormat as TE;

interface IMessages
{
    const JOINED_QUEUE       = IUtils::PREFIX . TE::WHITE . "Você entrou na fila com sucesso!";
    const DUEL_END           = TE::WHITE . "{looser} foi derrotado por {killer}{killerHp} no {mode}";
    const USE_SPAWN_COMMAND  = IUtils::PREFIX . TE::WHITE . "Teleportado para o spawn com sucesso!";
    const WORLD_SOON_DELETED = IUtils::PREFIX . TE::WHITE . "O mundo em que você estava será deletado";
    const PEARL_COOLDOWN     = TE::WHITE . "Você está em cooldown : {time} seg. restantes";
    const IN_COMBAT          = IUtils::PREFIX . TE::WHITE . "Você está em combate : aguarde {seconds} segundos.";
    const PLAYER_IN_COMBAT   = IUtils::PREFIX . TE::WHITE . "{player} está em combate : aguarde {seconds} segundos";
    const NOT_PERMISSION     = IUtils::ERROR . TE::WHITE . "Desculpe, você não tem permissão :/";
    const PLAYER_NOT_FOUND   = IUtils::PREFIX . TE::WHITE . "Desculpe, este jogador não existe";
    const SUCCESSFUL         = IUtils::PREFIX . TE::WHITE . "Sucesso!";
    const LEAVE_QUEUE        = IUtils::PREFIX . TE::WHITE . "Você saiu da fila com sucesso";
    const SET_IN_COMBAT      = IUtils::PREFIX . TE::WHITE . "Você agora está em combate com {target}";
    const SET_CPS_COUNTER    = IUtils::PREFIX . TE::WHITE . "Você alterou seu contador de CPS com sucesso!";
    const SET_POTION         = IUtils::PREFIX . TE::WHITE . "Você alterou seu tipo de poção com sucesso";
    const SET_SCOREBOARD     = IUtils::PREFIX . TE::WHITE . "Você alterou seu placar com sucesso";
    const SET_REQUEUE        = IUtils::PREFIX . TE::WHITE . "Você alterou seu reentrada automática com sucesso";
    const SET_REKIT          = IUtils::PREFIX . TE::WHITE . "Você alterou seu reequipamento automático com sucesso";
    const REKIT              = IUtils::PREFIX . TE::WHITE . "Você foi reequipado";
    const RECEIVE_ELO        = IUtils::PREFIX . TE::WHITE . "Você recebeu {elo} de elo, parabéns!";
    const FRIEND_REQUEST     = IUtils::PREFIX . TE::WHITE . "{player}" . TE::AQUA . " enviou um pedido de amizade para você";
    const NO_FRIENDS         = IUtils::PREFIX . TE::WHITE . "Parece que você não tem amigos, tente novamente mais tarde.";
    const NO_FRIEND_REQUESTS = IUtils::PREFIX . TE::WHITE . "Você não tem pedidos de amizade";
    const SET_DEATH_MESSAGE  = IUtils::PREFIX . TE::WHITE . "Você alterou a exibição de mensagens de morte com sucesso!";
}