<?php

/*
 * State constants
 */
const ST_BGA_GAME_SETUP = 1;

const ST_DEAL_MISSIONS = 10;
const ST_MULTIPLAYER_CHOOSE_MISSION = 21;
const ST_PRIVATE_CHOOSE_MISSION = 22;

const ST_PLAYER_CHOOSE_ACTION = 30;

const ST_NEXT_PLAYER = 88;

const ST_END_SCORE = 98;

const ST_END_GAME = 99;

/*
 * Dice faces
 */
const DICE_FACE_ENERGY = 1;
const DICE_FACE_CULTURE = 2;
const DICE_FACE_DIPLOMACY = 3;
const DICE_FACE_ECONOMY = 4;
const DICE_FACE_MOVE_SHIP = 5;
const DICE_FACE_EMPIRE = 6;

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
    PLANET_ANDELLOUXIAN6 => new PlanetInfo("ANDELLOUXIAN-6", "economy", 4, 5),
    PLANET_BISSCHOP => new PlanetInfo("BISSCHOP", "economy", 1, 1),
    PLANET_DREWKAIDEN => new PlanetInfo("DREWKAIDEN", "economy", 1, 1),
    PLANET_GYORE => new PlanetInfo("GYORE", "economy", 5, 7),
    PLANET_HELIOS => new PlanetInfo("HELIOS", "diplomacy", 2, 2),
    PLANET_HOEFKER => new PlanetInfo("HOEFKER", "economy", 2, 2),
    PLANET_JAC110912 => new PlanetInfo("JAC-110912", "economy", 4, 5),
);
const ENERGY_PLANETS = array(
    PLANET_AUGHMOORE => new PlanetInfo("AUGHMOORE", "diplomacy", 5, 7),
    PLANET_BIRKOMIUS => new PlanetInfo("BIRKOMIUS", "diplomacy", 1, 1),
    PLANET_BRUMBAUGH => new PlanetInfo("BRUMBAUGH", "diplomacy", 3, 3),
    PLANET_BSW101 => new PlanetInfo("BSW-10-1", "diplomacy", 4, 5),
    PLANET_CLJ0517 => new PlanetInfo("CLJ-0517", "economy", 2, 2),
    PLANET_GLEAMZANIER => new PlanetInfo("GLEAM-ZANIER", "diplomacy", 4, 5),
    PLANET_GORT => new PlanetInfo("GORT", "economy", 5, 7),
);
const PLANETS_BY_TYPE = array(
    PLANET_TYPE_CULTURE => CULTURE_PLANETS,
    PLANET_TYPE_ENERGY => ENERGY_PLANETS,
);

?>
