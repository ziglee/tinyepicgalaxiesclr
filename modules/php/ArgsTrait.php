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

    public function argChooseAction() {
        $playerId = intval(self::getActivePlayerId());
        return [
            "canFreeReroll" => $this->getGameStateValue(FREE_REROLL_USED) == 0,
            "canReroll" => $this->getUniqueIntValueFromDB("SELECT `energy_level` FROM `player` WHERE `player_id` = $playerId") > 0,
            "canConvert" => $this->getUniqueIntValueFromDB("SELECT COUNT(`die_id`) FROM `dice` WHERE `face` <> '0' AND `used` = FALSE") >= 3,
        ];
    }

    public function argConvertDie() {
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

    public function argMoveShip() {
        $playerId = intval(self::getActivePlayerId());
        return [
            "selectableShips" => $this->getObjectListFromDB("SELECT DISTINCT(ship_id) FROM ships WHERE player_id = $playerId", true),
        ];
    }

    public function argAdvanceEconomy() {
        $playerId = intval(self::getActivePlayerId());
        // TODO filter ships in planets of track type diplomacy
        return [
            "selectableShips" => $this->getObjectListFromDB("SELECT DISTINCT(ship_id) FROM ships WHERE player_id = $playerId AND track_progress IS NOT NULL", true),
        ];
    }

    public function argAdvanceDiplomacy() {
        $playerId = intval(self::getActivePlayerId());
        // TODO filter ships in planets of track type diplomacy
        return [
            "selectableShips" => $this->getObjectListFromDB("SELECT DISTINCT(ship_id) FROM ships WHERE player_id = $playerId AND track_progress IS NOT NULL", true),
        ];
    }

    public function argChooseEmpireAction() {
        $playerId = intval(self::getActivePlayerId());
        $playerObj = $this->getPlayerObject($playerId);
        $nextEmpireLevel = $playerObj['empire_level'] + 1;
        if ($nextEmpireLevel == 1) {
            $nextEmpireLevel = 2;
        }
        $energyLevel = $playerObj['energy_level'];
        $cultureLevel = $playerObj['culture_level'];
        $canUpgradeEmpireWithEnergy = $nextEmpireLevel <= 6 && $energyLevel >= $nextEmpireLevel;
        $canUpgradeEmpireWithCulture = $nextEmpireLevel <= 6 && $cultureLevel >= $nextEmpireLevel;
        $colonizedPlanets = array_keys($this->planetCards->getCardsInLocation('colony', $playerId));
        $canUtilizeColony = count($colonizedPlanets) > 0;
        return [
            "canUpgradeEmpireWithEnergy" => $canUpgradeEmpireWithEnergy,
            "canUpgradeEmpireWithCulture" => $canUpgradeEmpireWithCulture,
            "canUtilizeColony" => $canUtilizeColony,
        ];
    }

    public function argDecideFollow() {
        return [
            "nibiruTriggered" => $this->getGameStateValue(NIBIRU_TRIGGERED) == 1,
        ];
    }

    public function argPlanetHelios() {
        $occupiedPlanetsIds = $this->getObjectListFromDB("SELECT DISTINCT(planet_id) FROM ships WHERE planet_id IS NOT NULL", true);
        $planetsIds = array_keys($this->planetCards->getCardsInLocation('centerrow'));
        return [
            "elegiblePlanetsIds" => array_values(array_diff($planetsIds, $occupiedPlanetsIds))
        ];
    }

    public function argPlanetJorg() {
        $playerId = intval(self::getActivePlayerId());
        return [
            "selectableShips" => $this->getObjectListFromDB("SELECT DISTINCT(ship_id) FROM ships WHERE planet_id IS NOT NULL AND track_progress IS NOT NULL AND track_progress > 0 AND player_id <> $playerId", true),
        ];
    }
}
