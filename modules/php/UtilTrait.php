<?php

namespace Bga\Games\tinyepicgalaxiesclr;

require_once(__DIR__.'/objects/planet.php');

trait UtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function getPlayersIds() {
        return array_keys($this->loadPlayersBasicInfos());
    }

    function getPlayerCount() {
        return count($this->getPlayersIds());
    }

    function getPlayerDiceCount(int $playerId) {
        return $this->getUniqueIntValueFromDB("SELECT dice_count FROM player where `player_id` = $playerId");
    }

    function getPlanetsFromDb(array $dbObjects) {
        return array_map(fn($dbObject) => new \PlanetCard($dbObject), array_values($dbObjects));
    }
}
