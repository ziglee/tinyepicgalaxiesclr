<?php

const FREE_REROLL_USED = 'FREE_REROLL_USED';
const LAST_TURN = 'LAST_TURN';

/*
 * State constants
 */
const ST_BGA_GAME_SETUP = 1;

const ST_DEAL_MISSIONS = 10;
const ST_MULTIPLAYER_CHOOSE_MISSION = 21;
const ST_PRIVATE_CHOOSE_MISSION = 22;

const ST_PLAYER_CHOOSE_ACTION = 30;
const ST_PLAYER_CONVERT_DIE = 31;
const ST_PLAYER_MOVE_SHIP = 32;
const ST_PLAYER_ADVANCE_ECONOMY = 33;
const ST_PLAYER_ADVANCE_DIPLOMACY = 34;
const ST_PLAYER_EMPIRE_ACTION = 35;

const ST_NEXT_PLAYER = 88;
const ST_AFTER_ACTION_CHECK = 89; 

const ST_END_SCORE = 98;

const ST_END_GAME = 99;

/*
 * Dice faces
 */
const DICE_FACE_MOVE_SHIP = 1;
const DICE_FACE_ENERGY = 2;
const DICE_FACE_EMPIRE = 3;
const DICE_FACE_CULTURE = 4;
const DICE_FACE_ECONOMY = 5;
const DICE_FACE_DIPLOMACY = 6;

/*
 * Missions
 */
 const MISSION_CHARGER = "charger";
 const MISSION_CONQUEROR = "conqueror";
 const MISSION_ELDER = "elder";
 const MISSION_EQUALIZER = "equalizer";
 const MISSION_EXPLORER = "explorer";
 const MISSION_HERMIT = "hermit";
 const MISSION_HOARDER = "hoarder";
 const MISSION_INDUSTRIALIST = "industrialist";
 const MISSION_NOBLE = "noble";
 const MISSION_ORBITER = "orbiter";
 const MISSION_SEEKER = "seeker";
 const MISSION_TRADER = "trader";
 
 const allMissions = [
     MISSION_CHARGER,
     MISSION_CONQUEROR,
     MISSION_ELDER,
     MISSION_EQUALIZER,
     MISSION_EXPLORER,
     MISSION_HERMIT,
     MISSION_HOARDER,
     MISSION_INDUSTRIALIST,
     MISSION_NOBLE,
     MISSION_ORBITER,
     MISSION_SEEKER,
     MISSION_TRADER,
 ];
 
 const duelMissions = [
     MISSION_ELDER,
     MISSION_EQUALIZER,
     MISSION_EXPLORER,
     MISSION_HOARDER,
     MISSION_INDUSTRIALIST,
     MISSION_ORBITER,
 ];

/*
 * Planets
 */
const PLANET_TYPE_CULTURE = "culture";
const PLANET_TYPE_ENERGY = "energy";

const PLANET_TRACK_ECONOMY = "economy";
const PLANET_TRACK_DIPLOMACY = "diplomacy";

const PLANET_ANDELLOUXIAN6 = 1;
const PLANET_AUGHMOORE = 2;
const PLANET_BIRKOMIUS = 3;
const PLANET_BISSCHOP = 4;
const PLANET_BRUMBAUGH = 5;
const PLANET_BSW101 = 6;
const PLANET_CLJ0517 = 7;
const PLANET_DREWKAIDEN = 8;
const PLANET_GLEAMZANIER = 9;
const PLANET_GORT = 10;
const PLANET_GYORE = 11;
const PLANET_HELIOS = 12;
const PLANET_HOEFKER = 13;
const PLANET_JAC110912 = 14;

class PlanetInfo {
    public string $name;
    public string $trackType;
    public int $trackLength;
    public int $pointsWorth;

    function __construct($name, $trackType, $trackLength, $pointsWorth) {
        $this->name = $name;
        $this->trackType = $trackType;
        $this->trackLength = $trackLength;
        $this->pointsWorth = $pointsWorth;
    }
}

const CULTURE_PLANETS = array(
    PLANET_ANDELLOUXIAN6 => new PlanetInfo("ANDELLOUXIAN-6", PLANET_TRACK_ECONOMY, 4, 5),
    PLANET_BISSCHOP => new PlanetInfo("BISSCHOP", PLANET_TRACK_ECONOMY, 1, 1),
    PLANET_DREWKAIDEN => new PlanetInfo("DREWKAIDEN", PLANET_TRACK_ECONOMY, 1, 1),
    PLANET_GYORE => new PlanetInfo("GYORE", PLANET_TRACK_ECONOMY, 5, 7),
    PLANET_HELIOS => new PlanetInfo("HELIOS", PLANET_TRACK_DIPLOMACY, 2, 2),
    PLANET_HOEFKER => new PlanetInfo("HOEFKER", PLANET_TRACK_ECONOMY, 2, 2),
    PLANET_JAC110912 => new PlanetInfo("JAC-110912", PLANET_TRACK_ECONOMY, 4, 5),
);
const ENERGY_PLANETS = array(
    PLANET_AUGHMOORE => new PlanetInfo("AUGHMOORE", PLANET_TRACK_DIPLOMACY, 5, 7),
    PLANET_BIRKOMIUS => new PlanetInfo("BIRKOMIUS", PLANET_TRACK_DIPLOMACY, 1, 1),
    PLANET_BRUMBAUGH => new PlanetInfo("BRUMBAUGH", PLANET_TRACK_DIPLOMACY, 3, 3),
    PLANET_BSW101 => new PlanetInfo("BSW-10-1", PLANET_TRACK_DIPLOMACY, 4, 5),
    PLANET_CLJ0517 => new PlanetInfo("CLJ-0517", PLANET_TRACK_ECONOMY, 2, 2),
    PLANET_GLEAMZANIER => new PlanetInfo("GLEAM-ZANIER", PLANET_TRACK_DIPLOMACY, 4, 5),
    PLANET_GORT => new PlanetInfo("GORT", PLANET_TRACK_ECONOMY, 5, 7),
);
const PLANETS_BY_TYPE = array(
    PLANET_TYPE_CULTURE => CULTURE_PLANETS,
    PLANET_TYPE_ENERGY => ENERGY_PLANETS,
);

?>
