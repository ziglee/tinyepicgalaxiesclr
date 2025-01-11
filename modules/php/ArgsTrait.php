<?php

namespace Bga\Games\tinyepicgalaxiesclr;

trait ArgsTrait {
    
    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    public function argPrivateChooseMission(int $playerId) {
        return [
            "missions" => array_values($this->missionCards->getPlayerHand($playerId)),
        ];
    }

    function argChooseAction() {
        $playerId = intval(self::getActivePlayerId());
        return [
            "canFreeReroll" => $this->getGameStateValue(FREE_REROLL_USED) == 0,
            "canReroll" => $this->getUniqueIntValueFromDB("SELECT `energy_level` FROM `player` WHERE `player_id` = $playerId") > 0,
            "canConvert" => $this->getUniqueIntValueFromDB("SELECT COUNT(`die_id`) FROM `dice` WHERE `face` <> '0' AND `used` = FALSE") >= 3,
        ];
    }

    function argConvertDie() {
        return [
            "converterDice" => array_values(
                array_filter(
                    $this->getDice(), 
                    function($die) {
                        return $die['used'] == 0 && $die['face'] != 0;
                    }
                )
            ),
        ];
    }

    function argMoveShip() {
        $playerId = intval(self::getActivePlayerId());
        return [
            "selectableShips" => $this->getPlayerShips($playerId),
        ];
    }

    function argAdvanceEconomy() {
        $playerId = intval(self::getActivePlayerId());
        $ships = $this->getPlayerShips($playerId);
        return [
            "selectableShips" => $ships, // TODO: FILTER
        ];
    }

    function argAdvanceDiplomacy() {
        $playerId = intval(self::getActivePlayerId());
        $ships = $this->getPlayerShips($playerId);
        return [
            "selectableShips" => $ships, // TODO: FILTER
        ];
    }
}
