<?php

namespace Bga\Games\tinyepicgalaxiesclr;

require_once(__DIR__.'/objects/planet.php');

trait UtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function getUniqueIntValueFromDB(string $sql) {
        return intval(self::getUniqueValueFromDB($sql));
    }

    function getPlayersIds() {
        return array_keys($this->loadPlayersBasicInfos());
    }

    function getPlayerCount() {
        return count($this->getPlayersIds());
    }

    function getPlayerScore(int $playerId) {
        return $this->getUniqueIntValueFromDB("SELECT player_score FROM player where `player_id` = $playerId");
    }

    function getPlayerDiceCount(int $playerId) {
        return $this->getUniqueIntValueFromDB("SELECT dice_count FROM player where `player_id` = $playerId");
    }

    function getPlanetsFromDb(array $dbObjects) {
        return array_map(fn($dbObject) => new \PlanetCard($dbObject), array_values($dbObjects));
    }

    function isAllRolledDiceUsed(): bool {
        return $this->getUniqueIntValueFromDB("SELECT IF(COUNT(*) > 0, TRUE, FALSE) AS rolled_unused_count FROM dice AS d WHERE d.used = 0 AND d.face <> 0");
    }

    function resetDice() {
        $this->DbQuery("UPDATE dice SET used = false, converter = false, face = 0");
    }

    function rollDice(int $count) {
        for($x = 1; $x <= $count; $x++) {
            $face = \bga_rand(1, 6);
            $this->DbQuery("UPDATE dice SET face = $face WHERE die_id = $x");
        }
    }
}
