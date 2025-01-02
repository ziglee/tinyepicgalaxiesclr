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
        // TODO

        return [
            "missions" => array_values($this->missionCards->getPlayerHand($playerId)),
        ];
    }

    function argChooseAction() {        
        $playerId = intval(self::getActivePlayerId());

        // TODO

        return [
            "playableCardsIds" => [1, 2],
        ];
    }

}