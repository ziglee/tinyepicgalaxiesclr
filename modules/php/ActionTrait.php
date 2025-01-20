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
    }

    public function actActivateDie(int $dieId): void
    {
        $playerId = intval(self::getCurrentPlayerId());
        $this->useDie($dieId, false);

        $this->notifyAllPlayers(
            "diceUpdated", 
            "", 
            array(
                'dice' => $this->getDice(),
            )
        );

        $face = $this->getDieFaceById($dieId);
        $this->setGameStateValue(DIE_FACE_ACTIVATED, $face);
        switch ($face) {
            case DICE_FACE_ENERGY:
                $shipsInEnergySpotCount = $this->getPlayerShipsInEnergySpotCount($playerId);
                $this->acquireEnergy($playerId, $shipsInEnergySpotCount);
                $this->gamestate->nextState("nextFollower");
                break;
            case DICE_FACE_CULTURE:
                $shipsInCultureSpotCount = $this->getPlayerShipsInCultureSpotCount($playerId);
                $this->acquireCulture($playerId, $shipsInCultureSpotCount);
                $this->gamestate->nextState("nextFollower");
                break;
            case DICE_FACE_MOVE_SHIP:
                $this->gamestate->nextState("moveShip");
                break;
            case DICE_FACE_ECONOMY:
                if ($this->checkPlayerShipsCanAdvance($playerId, PLANET_TRACK_ECONOMY)) {
                    $this->gamestate->nextState("advanceEconomy");
                } else {
                    $this->gamestate->nextState("nextFollower");
                }
                break;
            case DICE_FACE_DIPLOMACY:
                if ($this->checkPlayerShipsCanAdvance($playerId, PLANET_TRACK_DIPLOMACY)) {
                    $this->gamestate->nextState("advanceDiplomacy");
                } else {
                    $this->gamestate->nextState("nextFollower");
                }
                break;
            case DICE_FACE_EMPIRE:
                $this->executeEmpireAction($playerId);
                break;
        }
    }

    private function checkPlayerShipsCanAdvance(int $playerId, string $trackType): bool {
        $ships = $this->getPlayerShips($playerId);
        foreach ($ships as $ship) {
            $planetId = $ship['planet_id'];
            if (!is_null($planetId) && !is_null($ship['track_progress'])) {
                $planetCard = $this->planetCards->getCard($planetId);
                $planet = new \PlanetCard($planetCard);
                if ($planet->info->trackType == $trackType) {
                    return true;
                }
            }
        }
        return false;
    }

    private function acquireEnergy(int $playerId, int $delta) {
        $newEnergyLevel = $this->incrementPlayerEnergy($playerId, $delta);
        $playerObj = $this->getPlayerObject($playerId);
        $this->notifyAllPlayers("energyLevelUpdated", clienttranslate('${player_name} acquired energy'), [
            "player_id" => $playerId,
            "player_name" => $playerObj['player_name'],
            "energy_level" => $newEnergyLevel,
        ]);
    }

    private function acquireCulture(int $playerId, int $delta) {
        $newCultureLevel = $this->incrementPlayerCulture($playerId, $delta);
        $playerObj = $this->getPlayerObject($playerId);
        $this->notifyAllPlayers("cultureLevelUpdated", clienttranslate('${player_name} acquired culture'), [
            "player_id" => $playerId,
            "player_name" => $playerObj['player_name'],
            "culture_level" => $newCultureLevel,
        ]);
    }

    public function actRerollDice(#[IntArrayParam] array $ids): void
    {
        $playerId = intval(self::getCurrentPlayerId());

        $freeRollUsed = $this->getGameStateValue(FREE_REROLL_USED);
        $energyLevel = $this->getUniqueIntValueFromDB("SELECT `energy_level` FROM `player` WHERE `player_id` = $playerId");

        if ($freeRollUsed == 1 && $energyLevel == 0) {
            throw new \BgaUserException('You have no energy to reroll dice');
        }

        if ($freeRollUsed == 0) {
            $this->setGameStateValue(FREE_REROLL_USED, 1);
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
                "energyLevelUpdated", 
                "", 
                array(
                    "player_id" => $playerId,
                    'energy_level' => $energyLevel - 1,
                ) 
            );
        }

        $this->gamestate->nextState("chooseAnotherAction");
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

    public function actMoveShip(int $shipId, ?int $planetId, bool $isTrack): void
    {
        $player_id = (int)$this->getActivePlayerId();

        $ship = $this->getShipById($shipId);
        if ($ship['player_id'] != $player_id) {
            throw new \BgaUserException('You cannot move ships of other players');
        }
        if ($ship['planet_id'] == $planetId) {
            throw new \BgaUserException('You cannot move the ship to the same place of origin');
        }
        if (!is_null($planetId)) {
            $ships = $this->getPlayerShips($player_id);
            foreach ($ships as $loopShipId => $loopShip) {
                if ($loopShipId != $shipId && $loopShip['planet_id'] == $planetId && (
                    (is_null($loopShip['track_progress']) && !$isTrack) ||
                    (!is_null($loopShip['track_progress']) && $isTrack)
                )) {
                    throw new \BgaUserException('You cannot move the ship where you already have another ship');
                }
            }
        }

        $this->updateShipLocation($shipId, $planetId, $isTrack ? 0 : null);

        $message = '${player_name} ship moved back to its galaxy';
        if (!is_null($planetId)) {
            $planetCard = $this->planetCards->getCard($planetId);
            $planet = new \PlanetCard($planetCard);
            $message = '${player_name} ship moved to surface of '. $planet->info->name;
            if ($isTrack) {
                $message = '${player_name} ship moved to orbit of '. $planet->info->name;
            }
        }
        $this->notifyAllPlayers("shipUpdated", clienttranslate($message), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
            "ship" => $this->getShipById($shipId),
        ]);

        if (!is_null($planetId) && !$isTrack) {
            $this->executePlanetAction($player_id, $planetId);
        } else {
            $this->gamestate->nextState("nextFollower");
        }
    }

    public function actAdvanceEconomy(?int $shipId): void
    {
        if (!is_null($shipId)) {
            $this->advanceShip($shipId, PLANET_TRACK_ECONOMY);
        }
        $this->gamestate->nextState("");
    }

    public function actAdvanceDiplomacy(?int $shipId): void
    {
        if (!is_null($shipId)) {
            $this->advanceShip($shipId, PLANET_TRACK_DIPLOMACY);
        }
        $this->gamestate->nextState("");
    }

    private function advanceShip(int $shipId, string $planetTrackType) 
    {
        $player_id = (int)$this->getActivePlayerId();
        $ship = $this->getShipById($shipId);

        if ($ship['player_id'] != $player_id) {
            throw new \BgaUserException('You cannot move ships of other players');
        }
        $planetId = $ship['planet_id'];
        if (is_null($planetId)) {
            if ($planetTrackType == PLANET_TRACK_ECONOMY) {
                throw new \BgaUserException('You cannot move ship that is not on a economy orbit');
            } else {
                throw new \BgaUserException('You cannot move ship that is not on a diplomacy orbit');
            }
        }
        $planetCard = $this->planetCards->getCard($planetId);
        $planet = new \PlanetCard($planetCard);
        if ($planet->info->trackType != $planetTrackType) {
            if ($planetTrackType == PLANET_TRACK_ECONOMY) {
                throw new \BgaUserException('You cannot move ship that is not on a economy orbit');
            } else {
                throw new \BgaUserException('You cannot move ship that is not on a diplomacy orbit');
            }
        }

        $currentProgress = $this->advanceShipProgress($shipId);
        if (($planet->info->trackLength + 1) == $currentProgress) {
            $ships = $this->getShipsByPlanet($planetId);
            foreach (array_keys($ships) as $thisShipId) { 
                $this->updateShipLocation($thisShipId, null, null);
                $updatedShip = $this->getShipById($thisShipId);
                $this->notifyAllPlayers("shipUpdated", "", [
                    "ship" => $updatedShip,
                ]);
            }

            $this->planetCards->moveCard($planetId, "colony", $player_id);
            $draftedPlanets = $this->planetCards->pickCardsForLocation(1, 'deck', 'centerrow');
            $this->notifyAllPlayers("planetColonized", clienttranslate('${player_name} colonized planet ${planet_name}'), [
                "player_id" => $player_id,
                "player_name" => $this->getActivePlayerName(),
                "planet_name" => $planet->info->name,
                "planet_id" => $planetId,
                "drafted_planet" => new \PlanetCard($draftedPlanets[0]),
            ]);

            $newScore = $this->incrementPlayerScore($player_id, $planet->info->pointsWorth);
            $this->notifyAllPlayers("playerScoreChanged", "", [
                "player_id" => $player_id,
                "player_name" => $this->getActivePlayerName(),
                "score" => $newScore,
            ]);
        } else {
            $ship = $this->getShipById($shipId);
            $this->notifyAllPlayers("shipUpdated", clienttranslate('${player_name} ship advanced economy orbit'), [
                "player_id" => $player_id,
                "player_name" => $this->getActivePlayerName(),
                "ship" => $ship,
            ]);
        }
    }

    private function upgradeEmpire(string $type): void
    {
        $playerId = (int)$this->getActivePlayerId();

        $playerObj = $this->getPlayerObject($playerId);
        $nextEmpireLevel = $playerObj['empire_level'] + 1;
        if ($nextEmpireLevel == 1) {
            $nextEmpireLevel = 2;
        }
        $energyLevel = $playerObj['energy_level'];
        $cultureLevel = $playerObj['culture_level'];
        $delta = $nextEmpireLevel * (-1);

        switch ($type) {
            case 'energy':
                $this->incrementPlayerEnergy($playerId, $delta);
                $this->notifyAllPlayers("energyLevelUpdated", clienttranslate('${player_name} upgraded empire with energy'), [
                    "player_id" => $playerId,
                    "player_name" => $this->getActivePlayerName(),
                    "energy_level" => $energyLevel + $delta,
                ]);
                break;
            case 'culture':
                $this->incrementPlayerCulture($playerId, $delta);
                $this->notifyAllPlayers("cultureLevelUpdated", clienttranslate('${player_name} upgraded empire with culture'), [
                    "player_id" => $playerId,
                    "player_name" => $this->getActivePlayerName(),
                    "culture_level" => $cultureLevel + $delta,
                ]);
                break;
        }

        $this->updatePlayerEmpire($playerId, $nextEmpireLevel);
        $this->notifyAllPlayers("empireLevelUpdated", "", [
            "player_id" => $playerId,
            "player_name" => $this->getActivePlayerName(),
            "empire_level" => $nextEmpireLevel,
        ]);

        switch ($nextEmpireLevel) {
            case 3:
            case 5:
                $this->DbQuery("INSERT INTO ships (player_id) VALUES ($playerId)");
                $shipId = $this->DbGetLastId();
                $this->dump( 'shipId', $shipId );
                $ship = $this->getShipById($shipId);
                $this->notifyAllPlayers("shipAdded", "", [
                    "player_id" => $playerId,
                    "player_name" => $this->getActivePlayerName(),
                    "ship" => $ship,
                ]);
                break;
            case 2:
            case 4:
            case 6:
                $this->incrementPlayerAddDieNextTurn($playerId);
                break;
        }
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

    private function executePlanetAction(int $playerId, int $planetId) {
        $planetCard = $this->planetCards->getCard($planetId);
        $planet = new \PlanetCard($planetCard);
        $playerObj = $this->getPlayerObject($playerId);

        switch ($planet->type_arg) {
            case PLANET_AUGHMOORE:
                $shipsInGalaxyCount = $this->getPlayerShipsInGalaxyCount($playerId);
                $this->acquireCulture($playerId, $shipsInGalaxyCount);
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_BIRKOMIUS:
                $this->setGameStateValue(BIRKOMIUS_TRIGGERED, 1);
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_BISSCHOP:
                $this->setGameStateValue(BISSCHOP_TRIGGERED, 1);
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_GLEAMZANIER:
                $playersIds = $this->getPlayersIds();
                foreach ($playersIds as $loopPlayerId) {
                    if ($loopPlayerId == $playerId) {
                       $this->acquireEnergy($loopPlayerId, 2);
                    } else {
                        $this->acquireEnergy($loopPlayerId, 1);
                    }
                }
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_HOEFKER:
                $energyLevel = $playerObj['energy_level'];
                if ($energyLevel > 0) {
                    $cultureLevel = $playerObj['culture_level'];

                    $this->incrementPlayerEnergy($playerId, -1);
                    $this->incrementPlayerCulture($playerId, 2);

                    $this->notifyAllPlayers("energyLevelUpdated", '', [
                        "player_id" => $playerId,
                        "player_name" => $playerObj['player_name'],
                        "energy_level" => $energyLevel - 1,
                    ]);
                    $this->notifyAllPlayers("cultureLevelUpdated", '', [
                        "player_id" => $playerId,
                        "player_name" => $playerObj['player_name'],
                        "culture_level" => $cultureLevel + 2,
                    ]);
                }
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_JAC110912:
                $playersIds = $this->getPlayersIds();
                foreach ($playersIds as $loopPlayerId) {
                    if ($loopPlayerId == $playerId) {
                       $this->acquireCulture($loopPlayerId, 2);
                    } else {
                        $this->acquireCulture($loopPlayerId, 1);
                    }
                }
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_JAKKS:
                $this->acquireCulture($playerId, 1);
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_MJ120210:
                $this->acquireEnergy($playerId, 2);
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_NIBIRU:
                $turnOwnerId = $this->getGameStateValue(TURN_OWNER_ID);
                if ($turnOwnerId == $playerId) {
                    $this->setGameStateValue(NIBIRU_TRIGGERED, 1);
                }
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_VICIKS156:
                $this->acquireEnergy($playerId, 1);
                $this->gamestate->nextState("nextFollower");
                break;
            default: // TODO every other planet and remove default case
                $this->gamestate->nextState("nextFollower");
                break;
        }
    }
    
    public function actDecideFollow(bool $follow): void
    {
        if (!$follow) {
            $this->gamestate->nextState("nextFollower");
            return;
        }

        $turnOwnerId = $this->getGameStateValue(TURN_OWNER_ID);
        if ($this->getGameStateValue(BIRKOMIUS_TRIGGERED) == 1) {
            $this->acquireCulture($turnOwnerId, 1);
        }
        if ($this->getGameStateValue(BISSCHOP_TRIGGERED) == 1) {
            $this->acquireEnergy($turnOwnerId, 1);
        }
        $cultureCost = -1;
        if ($this->getGameStateValue(NIBIRU_TRIGGERED) == 1) {
            $cultureCost = -2;
        }

        $playerId = (int)$this->getActivePlayerId();

        $playerObj = $this->getPlayerObject($playerId);
        $cultureLevel = $playerObj['culture_level'];

        $this->incrementPlayerCulture($playerId, $cultureCost);
        $this->notifyAllPlayers("cultureLevelUpdated", clienttranslate('${player_name} decided to follow last action'), [
            "player_id" => $playerId,
            "player_name" => $this->getActivePlayerName(),
            "culture_level" => $cultureLevel + $cultureCost,
        ]);

        $face = $this->getGameStateValue(DIE_FACE_ACTIVATED);
        switch ($face) {
            case DICE_FACE_ENERGY:
                $shipsInEnergySpotCount = $this->getPlayerShipsInEnergySpotCount($playerId);
                $this->acquireEnergy($playerId, $shipsInEnergySpotCount);
                $this->gamestate->nextState("nextFollower");
                break;
            case DICE_FACE_CULTURE:
                $shipsInCultureSpotCount = $this->getPlayerShipsInCultureSpotCount($playerId);
                $this->acquireCulture($playerId, $shipsInCultureSpotCount);
                $this->gamestate->nextState("nextFollower");
                break;
            case DICE_FACE_MOVE_SHIP:
                $this->gamestate->nextState("moveShip");
                break;
            case DICE_FACE_ECONOMY:
                if ($this->checkPlayerShipsCanAdvance($playerId, PLANET_TRACK_ECONOMY)) {
                    $this->gamestate->nextState("advanceEconomy");
                } else {
                    $this->gamestate->nextState("nextFollower");
                }
                break;
            case DICE_FACE_DIPLOMACY:
                if ($this->checkPlayerShipsCanAdvance($playerId, PLANET_TRACK_DIPLOMACY)) {
                    $this->gamestate->nextState("advanceDiplomacy");
                } else {
                    $this->gamestate->nextState("nextFollower");
                }
                break;
            case DICE_FACE_EMPIRE:
                $this->executeEmpireAction($playerId);
                break;
        }
    }

    private function executeEmpireAction(int $playerId): void {
        $colonizedPlanets = array_keys($this->planetCards->getCardsInLocation('colony', $playerId));

        $playerObj = $this->getPlayerObject($playerId);
        $nextEmpireLevel = $playerObj['empire_level'] + 1;
        if ($nextEmpireLevel == 1) {
            $nextEmpireLevel = 2;
        }
        $energyLevel = $playerObj['energy_level'];
        $cultureLevel = $playerObj['culture_level'];

        $canUtilizeColony = count($colonizedPlanets) > 0;
        $reachedMaxEmpireLevel = $nextEmpireLevel == 6;
        $canUpgradeEmpireWithEnergy = !$reachedMaxEmpireLevel && $energyLevel >= $nextEmpireLevel;
        $canUpgradeEmpireWithCulture = !$reachedMaxEmpireLevel && $cultureLevel >= $nextEmpireLevel; 
        $canUpgradeEmpire = $canUpgradeEmpireWithEnergy || $canUpgradeEmpireWithCulture;

        if ($canUtilizeColony) {
            $this->gamestate->nextState("chooseEmpireAction");
            return;
        } else if ($canUpgradeEmpire) {
            if ($canUpgradeEmpireWithEnergy && $canUpgradeEmpireWithCulture) {
                $this->gamestate->nextState("chooseEmpireAction");
            } else if ($canUpgradeEmpireWithEnergy) {
                $this->upgradeEmpire('energy');
                $this->gamestate->nextState("nextFollower");
            } else if ($canUpgradeEmpireWithCulture) {
                $this->upgradeEmpire('culture');
                $this->gamestate->nextState("nextFollower");
            }
            return;
        }

        $this->gamestate->nextState("nextFollower");
    }
    
    public function actDecideEmpireAction(?int $planetId, string $type): void {
        if ($planetId !== null) {
            $playerId = (int)$this->getActivePlayerId();
            $this->executePlanetAction($playerId, $planetId);
        } else {
            $this->upgradeEmpire($type);
            $this->gamestate->nextState("nextFollower");
        }
    }
}
