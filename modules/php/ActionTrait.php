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

        $face = $this->getDieFaceById($dieId);
        switch ($face) {
            case DICE_FACE_ENERGY:

                break;
            case DICE_FACE_CULTURE:
                break;
            case DICE_FACE_MOVE_SHIP:
                break;
            case DICE_FACE_ECONOMY:
                break;
            case DICE_FACE_DIPLOMACY:
                break;
            case DICE_FACE_EMPIRE:
                break;
        }

        $this->gamestate->nextState("afterActionCheck");
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

    public function actSelectConverterDice(int $die1id, int $die2id): void
    {
        $this->useDieAsConverter($die1id, $die2id);

        $this->notifyAllPlayers(
            "diceUpdated", 
            "", 
            array(
                'dice' => $this->getDice(),
            )
        );

        $this->gamestate->nextState("selectNewDieFace");
    }

    public function actConvertDie(int $dieId, int $newFace): void
    {
        $this->updateDieFace($dieId, $newFace);

        $this->notifyAllPlayers(
            "diceUpdated", 
            "", 
            array(
                'dice' => $this->getDice(),
            )
        );
        
        $this->gamestate->nextState("");
    }

    public function actPass(): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();
        self::giveExtraTime($player_id);

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} passes'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("pass");
    }
}
