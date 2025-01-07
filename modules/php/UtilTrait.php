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

    function getDice() {
        return $this->getCollectionFromDb(
            "SELECT `die_id` `id`, `face`, `used`, `converter` FROM `dice` ORDER BY `die_id`"
        );
    }

    function getDieFaceById(int $dieId) {
        return $this->getUniqueIntValueFromDB("SELECT `face` FROM `dice` where `die_id` = $dieId");
    }

    function getPlanetsFromDb(array $dbObjects) {
        return array_map(fn($dbObject) => new \PlanetCard($dbObject), array_values($dbObjects));
    }

    function isAllRolledDiceUsed(): bool {
        return $this->getUniqueIntValueFromDB("SELECT IF(COUNT(*) = 0, TRUE, FALSE) AS rolled_unused_count FROM dice AS d WHERE d.used = 0 AND d.face <> 0");
    }

    function useDie(int $dieId) {
        $this->DbQuery("UPDATE dice SET used = true WHERE die_id = $dieId");
    }

    function useDieAsConverter(int $die1Id, int $die2Id) {
        $this->DbQuery("UPDATE dice SET used = true, converter = true WHERE die_id = $die1Id OR die_id = $die2Id");
    }

    function updateDieFace(int $dieId, int $newFace) {
        $this->DbQuery("UPDATE dice SET face = $newFace WHERE die_id = $dieId");
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

    function rollDiceIds(array $ids) {
        foreach ($ids as $dieId) {
            $face = \bga_rand(1, 6);
            $this->DbQuery("UPDATE dice SET face = $face WHERE die_id = $dieId");
        }
    }
}
