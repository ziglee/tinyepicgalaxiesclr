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
        $die = $this->getDieById($dieId);
        if ($die['used'] || $die['converter']) {
            throw new \BgaUserException('You cannot convert a used die');
        }
        if ($die['face'] == $newFace) {
            throw new \BgaUserException('You must select a different face');
        }

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

    private function advanceShip(int $shipId, string $planetTrackType) : bool {
        $player_id = (int)$this->getActivePlayerId();
        $ship = $this->getShipById($shipId);

        $planetId = $ship['planet_id'];
        if (is_null($planetId)) {
            throw new \BgaUserException('You cannot move ship that is not on orbit');
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
        $hasColonizedPlanet = ($planet->info->trackLength + 1) == $currentProgress;
        if ($hasColonizedPlanet) {
            $ships = $this->getShipsByPlanet($planetId);
            foreach (array_keys($ships) as $thisShipId) { 
                $this->updateShipLocation($thisShipId, null, null);
                $updatedShip = $this->getShipById($thisShipId);
                $this->notifyAllPlayers("shipUpdated", "", [
                    "ship" => $updatedShip,
                ]);
            }

            $this->planetCards->moveCard($planetId, "colony", $player_id);
            $this->notifyAllPlayers("planetColonized", clienttranslate('${player_name} colonized planet ${planet_name}'), [
                "player_id" => $player_id,
                "player_name" => $this->getActivePlayerName(),
                "planet_name" => $planet->info->name,
                "planet_id" => $planetId,
            ]);
            $draftedPlanets = $this->planetCards->pickCardsForLocation(1, 'deck', 'centerrow');
            $this->notifyAllPlayers("draftedPlanet", "", [
                "planet" => new \PlanetCard($draftedPlanets[0]),
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

        return $hasColonizedPlanet;
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
            case PLANET_ANDELLOUXIAN6:
                $ships = $this->getPlayerShips($playerId);
                $hasShipInOrbit = false;
                foreach ($ships as $shipId => $ship) {
                    if (!is_null($ship['track_progress'])) {
                        $hasShipInOrbit = true;
                        break;
                    }
                }
                if ($hasShipInOrbit) {
                    $this->gamestate->nextState("planetAndellouxian");
                } else {
                    // TODO should not allow this move
                    $this->gamestate->nextState("nextFollower");
                }
                break;
            case PLANET_AUGHMOORE:
                $shipsInGalaxyCount = $this->getPlayerShipsInGalaxyCount($playerId);
                $this->acquireCulture($playerId, $shipsInGalaxyCount);
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_BRUMBAUGH:
                $energyLevel = $playerObj['energy_level'];
                if ($energyLevel < 2) {
                    // TODO should not allow this move
                    $this->gamestate->nextState("nextFollower");
                } else {
                    $this->gamestate->nextState("planetBrumbaugh");
                }
                break;
            case PLANET_BIRKOMIUS:
                $turnOwnerId = $this->getGameStateValue(TURN_OWNER_ID);
                if ($turnOwnerId == $playerId) {
                    $this->setGameStateValue(BIRKOMIUS_TRIGGERED, 1);
                }
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_BISSCHOP:
                $turnOwnerId = $this->getGameStateValue(TURN_OWNER_ID);
                if ($turnOwnerId == $playerId) {
                    $this->setGameStateValue(BISSCHOP_TRIGGERED, 1);
                }
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_CLJ0517:
                if ($this->getGameStateValue(CLJ0517_TRIGGERED) == 1) {
                    $this->notifyAllPlayers("message", \clienttranslate('CLJ-0517 causes no effect because it can only be performed once in a turn.'), []);
                    $this->gamestate->nextState("nextFollower");
                    break;
                }

                $playersIds = array_filter(
                    $this->getPlayersIds(), 
                    function($loopPlayerId) {
                        $playerId = intval(self::getActivePlayerId());
                        return $loopPlayerId != $playerId && $this->getPlayerObject($loopPlayerId)['culture_level'] > 0;
                    }
                );

                if (count($playersIds) == 0) {
                    $this->gamestate->nextState("nextFollower");
                } else if (count($playersIds) == 1) {
                    $this->notifyAllPlayers("cultureLevelUpdated", '', [
                        "player_id" => $playersIds[0],
                        "culture_level" => $this->incrementPlayerCulture($playersIds[0], -1),
                    ]);
                    $this->notifyAllPlayers("cultureLevelUpdated", '', [
                        "player_id" => $playerId,
                        "culture_level" => $this->incrementPlayerCulture($playerId, 1),
                    ]);
                    $this->setGameStateValue(CLJ0517_TRIGGERED, 1);
                    $this->gamestate->nextState("nextFollower");
                } else {
                    $this->gamestate->nextState("planetClj0517");
                }

                break;
            case PLANET_DREWKAIDEN:
                $this->gamestate->nextState("advanceDiplomacy");
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
            case PLANET_GYORE:
                if ($this->isAllRolledDiceUsed()) {
                    // TODO should not allow this move
                    $this->gamestate->nextState("nextFollower");
                } else {
                    $this->gamestate->nextState("planetGyore");
                }
                break;
            case PLANET_HELIOS:
                $this->gamestate->nextState("planetHelios");
                break;
            case PLANET_HOEFKER:
                $energyLevel = $playerObj['energy_level'];
                if ($energyLevel > 0) {
                    $this->incrementPlayerEnergy($playerId, -1);
                    $this->acquireCulture($playerId, 2);

                    $this->notifyAllPlayers("energyLevelUpdated", '', [
                        "player_id" => $playerId,
                        "player_name" => $playerObj['player_name'],
                        "energy_level" => $energyLevel - 1,
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
            case PLANET_JORG:
                $cultureLevel = $playerObj['culture_level'];
                if ($cultureLevel < 2) {
                    // TODO should not allow this move
                    $this->gamestate->nextState("nextFollower");
                } else {
                    $this->gamestate->nextState("planetJorg");
                }
                break;
            case PLANET_KWIDOW:
                $this->gamestate->nextState("planetKwidow");
                break;
            case PLANET_LATORRES:
                if ($this->getGameStateValue(LATORRES_TRIGGERED) == 1) {
                    $this->notifyAllPlayers("message", \clienttranslate('LA-TORRES causes no effect because it can only be performed once in a turn.'), []);
                    $this->gamestate->nextState("nextFollower");
                    break;
                }

                $playersIds = array_filter(
                    $this->getPlayersIds(), 
                    function($loopPlayerId) {
                        $playerId = intval(self::getActivePlayerId());
                        return $loopPlayerId != $playerId && $this->getPlayerObject($loopPlayerId)['energy_level'] > 0;
                    }
                );

                if (count($playersIds) == 0) {
                    $this->gamestate->nextState("nextFollower");
                } else if (count($playersIds) == 1) {
                    $this->notifyAllPlayers("energyLevelUpdated", '', [
                        "player_id" => $playersIds[0],
                        "energy_level" => $this->incrementPlayerEnergy($playersIds[0], -1),
                    ]);
                    $this->notifyAllPlayers("energyLevelUpdated", '', [
                        "player_id" => $playerId,
                        "energy_level" => $this->incrementPlayerEnergy($playerId, 1),
                    ]);
                    $this->setGameStateValue(LATORRES_TRIGGERED, 1);
                    $this->gamestate->nextState("nextFollower");
                } else {
                    $this->gamestate->nextState("planetLatorres");
                }

                break;
            case PLANET_LEANDRA:
                $this->gamestate->nextState("advanceEconomy");
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
            case PLANET_PADRAIGIN3110:
                $ships = $this->getPlayerShips($playerId);
                $shipsInDiplomacyOrbit = array_filter(
                    $this->getPlayerShips($playerId), 
                    function($ship) {
                        $shipTrackProgress = $ship['track_progress'];
                        $shipPlanetId = $ship['planet_id'];
                        if (!is_null($shipTrackProgress) && !is_null($shipPlanetId)) {
                            $planetCard = $this->planetCards->getCard($shipPlanetId);
                            $planetDb = new \PlanetCard($planetCard);
                            if ($planetDb->info->trackType == PLANET_TRACK_DIPLOMACY) {
                                return true;
                            }
                        }
                        return false;
                    }
                );

                $cultureLevel = $playerObj['culture_level'];
                if (count($shipsInDiplomacyOrbit) == 0 || $cultureLevel < 2) {
                    // TODO should not allow this move
                    $this->gamestate->nextState("nextFollower");
                } else if (count($shipsInDiplomacyOrbit) == 1) {
                    $shipId = array_values($shipsInDiplomacyOrbit)[0]['id'];
                    $hasColonizedPlanet = $this->advanceShip($shipId, PLANET_TRACK_DIPLOMACY);
                    if (!$hasColonizedPlanet) {
                        $this->advanceShip($shipId, PLANET_TRACK_DIPLOMACY);
                    }
                    $this->notifyAllPlayers("cultureLevelUpdated", '', [
                        "player_id" => $playerId,
                        "culture_level" => $this->incrementPlayerCulture($playerId, -2),
                    ]);
                    $this->gamestate->nextState("nextFollower");
                } else {
                    $this->gamestate->nextState("planetPadraigin3110");
                }

                break;
            case PLANET_SHOUHUA:
                $this->gamestate->nextState("planetShouhua");
                break;
            case PLANET_TIFNOD:
                $this->gamestate->nextState("planetTifnod");
                break;
            case PLANET_VICIKS156:
                $this->acquireEnergy($playerId, 1);
                $this->gamestate->nextState("nextFollower");
                break;
            case PLANET_VIZCARRA:
                $this->gamestate->nextState("planetVizcarra");
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

        $turnOwnerId = $this->getGameStateValue(TURN_OWNER_ID);
        if ($this->getGameStateValue(BIRKOMIUS_TRIGGERED) == 1) {
            $this->acquireCulture($turnOwnerId, 1);
        }
        if ($this->getGameStateValue(BISSCHOP_TRIGGERED) == 1) {
            $this->acquireEnergy($turnOwnerId, 1);
        }

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

    public function actPlanetAdellouxian(int $shipId, int $energy, int $culture): void {
        $playerId = (int)$this->getActivePlayerId();

        $ship = $this->getShipById($shipId);
        if ($ship['player_id'] != $playerId) {
            throw new \BgaUserException('You cannot move ships of other players');
        }
        $trackProgress = $ship['track_progress'];
        if (is_null($trackProgress)) { 
            throw new \BgaUserException('You must select a ship in orbit');
        }
        if ($trackProgress < ($energy + $culture)) {
            throw new \BgaUserException('The amount of energy plus culture must be equal to the ship orbit number');
        }

        if ($energy > 0) {
            $this->acquireEnergy($playerId, $energy);
        }
        if ($culture > 0) {
            $this->acquireCulture($playerId, $culture);
        }
        
        $this->updateShipLocation($shipId, null, null);
        $this->notifyAllPlayers("shipUpdated", "", [
            "player_id" => $playerId,
            "player_name" => $this->getActivePlayerName(),
            "ship" => $this->getShipById($shipId),
        ]);

        $this->gamestate->nextState("");
    }

    public function actPlanetBrumbaugh(#[IntArrayParam] array $shipsIds) {
        if (count($shipsIds) > 2) { 
            throw new \BgaUserException('You must select maximum 2 ships to move'); 
        }

        $playerId = (int)$this->getActivePlayerId();
        $playerObj = $this->getPlayerObject($playerId);
        if ($playerObj['energy_level'] < 2) {
            throw new \BgaUserException('You do not have enough energy for this move');
        }
        
        foreach ($shipsIds as $shipId) {
            $ship = $this->getShipById($shipId);
            if ($ship['player_id'] != $playerId && !is_null($ship['planet_id']) && $ship['track_progress'] > 0) {
                $this->DbQuery("UPDATE ships SET track_progress = GREATEST(0, track_progress - 1) WHERE ship_id = $shipId");
                $this->notifyAllPlayers("shipUpdated", "", [
                    "ship" => $this->getShipById($shipId),
                ]);
            }
        }

        $energy_level = $this->incrementPlayerEnergy($playerId, -2);
        $this->notifyAllPlayers("energyLevelUpdated", '', [
            "player_id" => $playerId,
            "energy_level" => $energy_level,
        ]);

        $this->gamestate->nextState("");
    }

    public function actPlanetHelios(int $planetId) {
        $occupiedPlanetsIds = $this->getObjectListFromDB("SELECT DISTINCT(planet_id) FROM ships WHERE planet_id IS NOT NULL", true);
        foreach ($occupiedPlanetsIds as $occupiedPlanetId) {
            if ($occupiedPlanetId == $planetId) {
                throw new \BgaUserException('You cannot select a planet that is occupied');
            }
        }

        $this->planetCards->insertCardOnExtremePosition($planetId, "deck", false);
        $draftedPlanets = $this->planetCards->pickCardsForLocation(1, 'deck', 'centerrow');
        $this->notifyAllPlayers("draftedPlanet", "", [
            "planet" => new \PlanetCard($draftedPlanets[0]),
        ]);
        $this->notifyAllPlayers("movePlanetToBottomOfDeck", "", [
            "planetId" => $planetId,
        ]);
        $this->gamestate->nextState("");
    }

    public function actPlanetJorg(int $shipId) {
        $playerId = (int)$this->getActivePlayerId();
        $playerObj = $this->getPlayerObject($playerId);
        if ($playerObj['culture_level'] < 2) {
            throw new \BgaUserException('You do not have enough culture for this move');
        }

        $ship = $this->getShipById($shipId);
        if ($ship['player_id'] == $playerId) {
            throw new \BgaUserException('You cannot selection your own ship');
        }

        $this->DbQuery("UPDATE ships SET track_progress = GREATEST(0, track_progress - 2) WHERE ship_id = $shipId");
        $this->notifyAllPlayers("shipUpdated", "", [
            "ship" => $this->getShipById($shipId),
        ]);

        $culture_level = $this->incrementPlayerCulture($playerId, -2);
        $this->notifyAllPlayers("cultureLevelUpdated", '', [
            "player_id" => $playerId,
            "culture_level" => $culture_level,
        ]);

        $this->gamestate->nextState("");
    }

    public function actPlanetKwidow(int $shipId) {
        $playerId = (int)$this->getActivePlayerId();
        
        $ship = $this->getShipById($shipId);
        if ($ship['player_id'] == $playerId) {
            throw new \BgaUserException('You cannot selection your own ship');
        }

        if ($ship['player_id'] != $playerId && !is_null($ship['planet_id']) && $ship['track_progress'] > 0) {
            $this->DbQuery("UPDATE ships SET track_progress = GREATEST(0, track_progress - 1) WHERE ship_id = $shipId");
            $this->notifyAllPlayers("shipUpdated", "", [
                "ship" => $this->getShipById($shipId),
            ]);
        }

        $this->gamestate->nextState("");
    }

    public function actPlanetLatorres(int $selectedPlayerId) {
        $playerId = (int)$this->getActivePlayerId();

        if ($selectedPlayerId == $playerId) {
            throw new \BgaUserException('You cannot select yourself');
        }

        $this->notifyAllPlayers("energyLevelUpdated", '', [
            "player_id" => $selectedPlayerId,
            "energy_level" => $this->incrementPlayerEnergy($selectedPlayerId, -1),
        ]);
        $this->notifyAllPlayers("energyLevelUpdated", '', [
            "player_id" => $playerId,
            "energy_level" => $this->incrementPlayerEnergy($playerId, 1),
        ]);
        $this->setGameStateValue(LATORRES_TRIGGERED, 1);
        
        $this->gamestate->nextState("");
    }

    public function actPlanetClj0517(int $selectedPlayerId) {
        $playerId = (int)$this->getActivePlayerId();

        if ($selectedPlayerId == $playerId) {
            throw new \BgaUserException('You cannot select yourself');
        }

        $this->notifyAllPlayers("cultureLevelUpdated", '', [
            "player_id" => $selectedPlayerId,
            "culture_level" => $this->incrementPlayerCulture($selectedPlayerId, -1),
        ]);
        $this->notifyAllPlayers("cultureLevelUpdated", '', [
            "player_id" => $playerId,
            "culture_level" => $this->incrementPlayerCulture($playerId, 1),
        ]);
        $this->setGameStateValue(CLJ0517_TRIGGERED, 1);
        
        $this->gamestate->nextState("");
    }

    public function actPlanetPadraigin3110(int $shipId) {
        $playerId = (int)$this->getActivePlayerId();

        $hasColonizedPlanet = $this->advanceShip($shipId, PLANET_TRACK_DIPLOMACY);
        if (!$hasColonizedPlanet) {
            $this->advanceShip($shipId, PLANET_TRACK_DIPLOMACY);
        }
        $this->notifyAllPlayers("cultureLevelUpdated", '', [
            "player_id" => $playerId,
            "culture_level" => $this->incrementPlayerCulture($playerId, -2),
        ]);
        $this->gamestate->nextState("");
    }

    public function actPlanetTifnod(int $shipId) {
        $playerId = (int)$this->getActivePlayerId();
        
        $ship = $this->getShipById($shipId);
        if ($ship['player_id'] == $playerId) {
            throw new \BgaUserException('You cannot selection your own ship');
        }

        $planetCard = $this->planetCards->getCard($ship['planet_id']);
        $planetDb = new \PlanetCard($planetCard);
        if ($planetDb->info->trackType != PLANET_TRACK_DIPLOMACY) {
            throw new \BgaUserException('You cannot move ship that is not on a diplomacy orbit');
        }

        $this->DbQuery("UPDATE ships SET track_progress = GREATEST(0, track_progress - 1) WHERE ship_id = $shipId");
        $this->notifyAllPlayers("shipUpdated", "", [
            "ship" => $this->getShipById($shipId),
        ]);
        $this->gamestate->nextState("");
    }

    public function actPlanetVizcarra(int $shipId) {
        $playerId = (int)$this->getActivePlayerId();
        
        $ship = $this->getShipById($shipId);
        if ($ship['player_id'] == $playerId) {
            throw new \BgaUserException('You cannot selection your own ship');
        }

        $planetCard = $this->planetCards->getCard($ship['planet_id']);
        $planetDb = new \PlanetCard($planetCard);
        if ($planetDb->info->trackType != PLANET_TRACK_ECONOMY) {
            throw new \BgaUserException('You cannot move ship that is not on a economy orbit');
        }

        $this->DbQuery("UPDATE ships SET track_progress = GREATEST(0, track_progress - 1) WHERE ship_id = $shipId");
        $this->notifyAllPlayers("shipUpdated", "", [
            "ship" => $this->getShipById($shipId),
        ]);
        $this->gamestate->nextState("");
    }

    public function actPlanetShouhua(int $shipId) {
        $ship = $this->getShipById($shipId);
        $planetCard = $this->planetCards->getCard($ship['planet_id']);
        $planetDb = new \PlanetCard($planetCard);
        $this->advanceShip($shipId, $planetDb->info->trackType);
        $this->gamestate->nextState("");
    }
}
