<?php

namespace Bga\Games\tinyepicgalaxiesclr;

trait ArgsTrait {
    
    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    public function argPlayerTurn(): array
    {
        // Get some values from the current game situation from the database.

        return [
            "playableCardsIds" => [1, 2],
        ];
    }

    public function argPrivateChooseMission(int $playerId) {
        return [
            "missions" => array_values($this->missionCards->getPlayerHand($playerId)),
        ];
    }

    function argChooseAction() {
        $playerId = intval(self::getActivePlayerId());
        return [
            "canFreeReroll" => $this->getGameStateValue("free_roll_used") == 0,
            "canReroll" => $this->getUniqueIntValueFromDB("SELECT `energy_level` FROM `player` WHERE `player_id` = $playerId") > 0,
            "canConvert" => $this->getUniqueIntValueFromDB("SELECT COUNT(`die_id`) FROM `dice` WHERE `face` <> '0' AND `used` = FALSE") >= 2,
        ];
    }

}