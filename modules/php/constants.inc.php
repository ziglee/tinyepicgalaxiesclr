<?php

const FREE_REROLL_USED = 'FREE_REROLL_USED';
const LAST_TURN = 'LAST_TURN';
const DIE_FACE_ACTIVATED = 'DIE_FACE_ACTIVATED';
const PLAYER_ID_ACTIVATING_DIE = 'PLAYER_ID_ACTIVATING_DIE';
const FOLLOWERS_COUNT = 'FOLLOWERS_COUNT';
const TURN_OWNER_ID = 'TURN_OWNER_ID';
const BIRKOMIUS_TRIGGERED = 'BIRKOMIUS_TRIGGERED';
const BISSCHOP_TRIGGERED = 'BISSCHOP_TRIGGERED';
const NIBIRU_TRIGGERED = 'NIBIRU_TRIGGERED';
const LATORRES_TRIGGERED = 'LATORRES_TRIGGERED';
const CLJ0517_TRIGGERED = 'CLJ0517_TRIGGERED';

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
const ST_PLAYER_DECIDE_FOLLOW = 36;
const ST_PLAYER_CHOOSE_EMPIRE_ACTION = 37;

const ST_PLAYER_PLANET_ANDELLOUXIAN = 50;
const ST_PLAYER_PLANET_HELIOS = 51;
const ST_PLAYER_PLANET_JORG = 52;
const ST_PLAYER_PLANET_BRUMBAUGH = 53;
const ST_PLAYER_PLANET_KWIDOW = 54;
const ST_PLAYER_PLANET_LATORRES = 55;
const ST_PLAYER_PLANET_CLJ0517 = 56;

const ST_NEXT_PLAYER = 88;
const ST_AFTER_ACTION_CHECK = 89; 
const ST_NEXT_FOLLOWER = 90;
const ST_AFTER_FOLLOW_CHECK = 91; 

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
const PLANET_JAKKS = 15;
const PLANET_JORG = 16;
const PLANET_KWIDOW = 17;
const PLANET_LATORRES = 18;
const PLANET_LEANDRA = 19;
const PLANET_LUREENA = 20;
const PLANET_MAIA = 21;
const PLANET_MARED = 22;
const PLANET_MJ120210 = 23;
const PLANET_NAGATO = 24;
const PLANET_NAKAGAWAKOZI = 25;
const PLANET_NIBIRU = 26;
const PLANET_OMICRONFENZI = 27;
const PLANET_PADRAIGIN3110 = 28;
const PLANET_PEMBERTONIAMAJOR = 29;
const PLANET_PIEDES = 30;
const PLANET_SARGUS36 = 31;
const PLANET_SHOUHUA = 32;
const PLANET_TERRABETTIA = 33;
const PLANET_TIFNOD = 34;
const PLANET_UMBRAFORUM = 35;
const PLANET_VICIKS156 = 36;
const PLANET_VIZCARRA = 37;
const PLANET_WALSFEO = 38;
const PLANET_ZALAX = 39;
const PLANET_ZAVODNICK = 40;

class PlanetInfo {
    public string $name;
    public string $trackType;
    public int $trackLength;
    public int $pointsWorth;
    public string $text;

    function __construct($name, $trackType, $trackLength, $pointsWorth, $text) {
        $this->name = $name;
        $this->trackType = $trackType;
        $this->trackLength = $trackLength;
        $this->pointsWorth = $pointsWorth;
        $this->text = $text;
    }
}

const CULTURE_PLANETS = array(
    //CHECKED PLANET_ANDELLOUXIAN6 => new PlanetInfo("ANDELLOUXIAN-6", PLANET_TRACK_ECONOMY, 4, 5, "Move 1 of your ships from a planet\'s orbit to your galaxy, then acquire energy and/or culture equal to that ship\'s orbit number."),
    //CHECKED PLANET_BISSCHOP => new PlanetInfo("BISSCHOP", PLANET_TRACK_ECONOMY, 1, 1, "On your turn after utilizing this colony, if you are followed then acquire 1 energy per follow."),
    //CHECKED PLANET_DREWKAIDEN => new PlanetInfo("DREWKAIDEN", PLANET_TRACK_ECONOMY, 1, 1, "Advance +1 diplomacy."),
    //CHECKED PLANET_GYORE => new PlanetInfo("GYORE", PLANET_TRACK_ECONOMY, 5, 7, "Set 1 inactive die to a face of your choice."),
    //CHECKED PLANET_HELIOS => new PlanetInfo("HELIOS", PLANET_TRACK_DIPLOMACY, 2, 2, "Place an un-occupied planet from the center row into the bottom of the planet deck and draw a new planet."),
    //CHECKED PLANET_HOEFKER => new PlanetInfo("HOEFKER", PLANET_TRACK_ECONOMY, 2, 2, "Spend 1 energy to acquire 2 culture."),
    //CHECKED PLANET_JAC110912 => new PlanetInfo("JAC-110912", PLANET_TRACK_ECONOMY, 4, 5, "Acquire 2 culture, all other players acquire 2 culture."),
    //CHECKED PLANET_JAKKS => new PlanetInfo("JAKKS", PLANET_TRACK_DIPLOMACY, 1, 1, "Acquire 1 culture."),
    //CHECKED PLANET_JORG => new PlanetInfo("JORG", PLANET_TRACK_DIPLOMACY, 3, 3, "Spend 2 culture to regress 1 enemy ship by -2."),
    //CHECKED PLANET_KWIDOW => new PlanetInfo("K-WIDOW", PLANET_TRACK_ECONOMY, 5, 7, "Regress an enemy ship -1."),
    //CHECKED PLANET_LATORRES => new PlanetInfo("LA-TORRES", PLANET_TRACK_DIPLOMACY, 1, 2, "Steal 1 energy from another player (only once during your turn)."),
    PLANET_LUREENA => new PlanetInfo("LUREENA", PLANET_TRACK_ECONOMY, 2, 2, "Upgrade your empire, you may spend a mix of energy and culture."),
    PLANET_MAIA => new PlanetInfo("MAIA", PLANET_TRACK_DIPLOMACY, 4, 5, "Discard 2 inactive dice, acquire 2 energy and 2 culture."),
    //CHECKED PLANET_NIBIRU => new PlanetInfo("NIBIRU", PLANET_TRACK_DIPLOMACY, 5, 7, "Enemmies must now pay 2 per follow this turn (only during your turn)."),
    //PLANET_OMICRONFENZI => new PlanetInfo("OMICRON-FENZI", PLANET_TRACK_DIPLOMACY, 3, 3, "Convert any number of energy into culture."),
    //PLANET_PADRAIGIN3110 => new PlanetInfo("PADRAIGIN-3110", PLANET_TRACK_ECONOMY, 3, 3, "Spend 2 culture to advance +2 diplomacy."),
    //PLANET_SARGUS36 => new PlanetInfo("SARGUS-36", PLANET_TRACK_DIPLOMACY, 4, 5, "Pay 1 energy to a player to utilize one of their colonized planets."),
    //PLANET_TIFNOD => new PlanetInfo("TIFNOD", PLANET_TRACK_ECONOMY, 1, 1, "Regress 1 enemy ship by -1."),
    //PLANET_UMBRAFORUM => new PlanetInfo("UMBRA-FORUM", PLANET_TRACK_ECONOMY, 3, 3, "Utilize the action of an un-colonized planet."),
    //PLANET_ZALAX => new PlanetInfo("ZALAX", PLANET_TRACK_DIPLOMACY, 2, 2, "Reroll any number of your inactive dice."),
);
const ENERGY_PLANETS = array(
    //CHECKED PLANET_AUGHMOORE => new PlanetInfo("AUGHMOORE", PLANET_TRACK_DIPLOMACY, 5, 7, "Acquire culture for every ship landed in your galaxy."),
    //CHECKED PLANET_BIRKOMIUS => new PlanetInfo("BIRKOMIUS", PLANET_TRACK_DIPLOMACY, 1, 1, "On your turn after utilizing this colony, if you are followed then acquire 1 culture per follow."),
    //CHECKED PLANET_BRUMBAUGH => new PlanetInfo("BRUMBAUGH", PLANET_TRACK_DIPLOMACY, 3, 3, "Spend 2 energy to regress 2 enemy ships by -1."),
    PLANET_BSW101 => new PlanetInfo("BSW-10-1", PLANET_TRACK_DIPLOMACY, 4, 5, "Regress one of your ships -1, then advance another one of your ships +1."),
    PLANET_CLJ0517 => new PlanetInfo("CLJ-0517", PLANET_TRACK_ECONOMY, 2, 2, "Steal 1 culture from another player (only once during your turn)."),
    //CHECKED PLANET_GLEAMZANIER => new PlanetInfo("GLEAM-ZANIER", PLANET_TRACK_DIPLOMACY, 4, 5, "Acquired 2 energy, all other players acquire 1 energy."),
    //PLANET_GORT => new PlanetInfo("GORT", PLANET_TRACK_ECONOMY, 5, 7, "Move 1 of your orbiting ships to an equal number of another planet\'s orbit (this may colonize the planet)."),
    //CHECKED PLANET_LEANDRA => new PlanetInfo("LEANDRA", PLANET_TRACK_DIPLOMACY, 1, 1, "Advance +1 economy."),
    //PLANET_MARED => new PlanetInfo("MARED", PLANET_TRACK_ECONOMY, 2, 2, "If your empire level is the lowest (or tied for lowest) upgrade your empire for 1 less resource."),
    //CHECKED PLANET_MJ120210 => new PlanetInfo("MJ-120210", PLANET_TRACK_DIPLOMACY, 2, 2, "Acquire 2 energy."),
    //PLANET_NAGATO => new PlanetInfo("NAGATO", PLANET_TRACK_ECONOMY, 3, 3, "Spend 1 culture to move 2 of your ships (only once per turn)."),
    //PLANET_NAKAGAWAKOZI => new PlanetInfo("NAKAGAWAKOZI", PLANET_TRACK_DIPLOMACY, 3, 3, "Spend 2 energy to advance +2 economy."),
    //PLANET_PEMBERTONIAMAJOR => new PlanetInfo("PEMBERTONIA-MAJOR", PLANET_TRACK_DIPLOMACY, 3, 3, "Convert any number of energy into culture."),
    //PLANET_PIEDES => new PlanetInfo("PIEDES", PLANET_TRACK_ECONOMY, 5, 7, "Repeat the action on an alread activated die."),
    //PLANET_SHOUHUA => new PlanetInfo("SHOUHUA", PLANET_TRACK_DIPLOMACY, 5, 7, "Advance a ship +1."),
    //PLANET_TERRABETTIA => new PlanetInfo("TERRA-BETTIA", PLANET_TRACK_DIPLOMACY, 5, 7, "Other players advance a ship +1, then you advance a ship +2."), 
    //CHECKED PLANET_VICIKS156 => new PlanetInfo("VICI-KS156", PLANET_TRACK_ECONOMY, 1, 1, "Acquire 1 energy."), 
    //PLANET_VIZCARRA => new PlanetInfo("VIZCARRA", PLANET_TRACK_DIPLOMACY, 1, 1, "Regress 1 enemy ship by -1 economy."), 
    //PLANET_WALSFEO => new PlanetInfo("WALSFEO", PLANET_TRACK_ECONOMY, 4, 5, "Pay 1 culture to a player to utilize one of their colonized planets."), 
    //PLANET_ZAVODNICK => new PlanetInfo("ZAVODNICK", PLANET_TRACK_ECONOMY, 4, 5, "Perform any 1 action; all other players may follow that action for free (only once per turn)."), 
);
const PLANETS_BY_TYPE = array(
    PLANET_TYPE_CULTURE => CULTURE_PLANETS,
    PLANET_TYPE_ENERGY => ENERGY_PLANETS,
);

?>
