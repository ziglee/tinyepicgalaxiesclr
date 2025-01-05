<?php

namespace Bga\Games\tinyepicgalaxiesclr;

use Bga\GameFramework\Actions\Types\IntArrayParam;

trait ActionTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    public function actChooseMission(int $selectedMissionId): void 
    {
        $playerId = intval(self::getCurrentPlayerId());

        $missionCards = $this->missionCards->getPlayerHand($playerId);
        foreach ($missionCards as $missionId => $missionCard) {
            if ($missionId == $selectedMissionId) {
                $type = $missionCard['type'];
                $this->DbQuery("UPDATE player SET mission = '$type' WHERE player_id = '$playerId'");
                $this->notifyPlayer($playerId, 'missionChoosed', \clienttranslate('You selected mission ${mission}'), [
                    "mission" => $type,
                ]);
            } else {
                $this->missionCards->playCard($missionId);
            }
        }
        
        $this->gamestate->setPlayerNonMultiactive($playerId, 'start');
        self::giveExtraTime($playerId);
    }

    public function actActivateDie(int $dieId): void
    {
        $playerId = intval(self::getCurrentPlayerId());
        self::giveExtraTime($playerId);

        $this->useDie($dieId, false);

        $this->notifyAllPlayers(
            "diceUpdated", 
            "", 
            array(
                'dice' => $this->getDice(),
            ) 
        );

        $this->gamestate->nextState("executeAction");
    }

    public function actRerollDice(#[IntArrayParam] array $ids): void
    {
        $playerId = intval(self::getCurrentPlayerId());

        $freeRollUsed = $this->getGameStateValue("free_roll_used");
        $energyLevel = $this->getUniqueIntValueFromDB("SELECT `energy_level` FROM `player` WHERE `player_id` = $playerId");

        if ($freeRollUsed == 1 && $energyLevel == 0) {
            throw new \BgaUserException('You have no energy to reroll dice');
        }

        if ($freeRollUsed == 0) {
            $this->setGameStateValue("free_roll_used", 1);
        } else {
            $this->DbQuery("UPDATE `player` SET `energy_level` = `energy_level` - 1 WHERE `player_id` = $playerId");
        }

        $this->rollDiceIds($ids);

        $this->notifyAllPlayers(
            "diceUpdated", 
            "", 
            array(
                'dice' => $this->getDice(),
            ) 
        );

        if ($freeRollUsed == 0) {
            $this->notifyAllPlayers("freeRerollWasUsed", clienttranslate('${player_name} used free reroll'), [
                "player_id" => $playerId,
                "player_name" => $this->getActivePlayerName(),
            ]);
        } else {
            $this->notifyAllPlayers(
                "energyLevelChanged", 
                "", 
                array(
                    "player_id" => $playerId,
                    'new_energy_level' => $energyLevel - 1,
                ) 
            );
        }

        $this->gamestate->nextState("executeAction");
    }

    public function actConvertDie(): void
    {
        // TODO
        
        $this->gamestate->nextState("executeAction");
    }

    public function actPlayCard(int $card_id): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // check input values
        $args = $this->argPlayerTurn();
        $playableCardsIds = $args['playableCardsIds'];
        if (!in_array($card_id, $playableCardsIds)) {
            throw new \BgaUserException('Invalid card choice');
        }

        // Add your game logic to play a card here.
        //$card_name = self::$CARD_TYPES[$card_id]['card_name'];

        // Notify all players about the card played.
        // $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
        //     "player_id" => $player_id,
        //     "player_name" => $this->getActivePlayerName(),
        //     "card_name" => $card_name,
        //     "card_id" => $card_id,
        //     "i18n" => ['card_name'],
        // ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("playCard");
    }

    public function actPass(): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} passes'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("pass");
    }
}
