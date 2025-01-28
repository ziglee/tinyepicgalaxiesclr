<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * tinyepicgalaxiesclr implementation : © Cássio Landim Ribeiro <ziglee@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * tinyepicgalaxiesclr game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

require_once("modules/php/constants.inc.php");

$basicGameStates = [
    // The initial state. Please do not modify.
    ST_BGA_GAME_SETUP => [
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => [ "" => ST_DEAL_MISSIONS ]
    ],
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    ST_END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],
];

$playerActionsGameStates = [
    ST_MULTIPLAYER_CHOOSE_MISSION => [
        "name" => "multiChooseMission",
        "description" => clienttranslate('Other players must choose a secret mission'),
        "descriptionmyturn" => '',
        "type" => "multipleactiveplayer",
        "initialprivate" => ST_PRIVATE_CHOOSE_MISSION,
        "action" => "stChooseMission",
        "possibleactions" => [],
        "transitions" => [
            "start" => ST_NEXT_PLAYER,
        ],
    ],

    ST_PRIVATE_CHOOSE_MISSION => [
        "name" => "privateChooseMission",
        "descriptionmyturn" => clienttranslate('${you} must choose one secret mission'),
        "type" => "private",
        "args" => "argPrivateChooseMission",
        "possibleactions" => [
            "actChooseMission",
        ],
        "transitions" => [],
    ],

    ST_PLAYER_CHOOSE_ACTION => [
        "name" => "chooseAction",
        "description" => clienttranslate('${actplayer} must select one or more dice'), 
        "descriptionmyturn" => clienttranslate('${you} must select one or more dice'),
        "type" => "activeplayer",
        "args" => "argChooseAction",
        "possibleactions" => [
            "actActivateDie", 
            "actRerollDice", 
            "actSelectConverterDice",
            "actPass",
        ],
        "transitions" => [
            "chooseAnotherAction" => ST_PLAYER_CHOOSE_ACTION, 
            "afterActionCheck" => ST_AFTER_ACTION_CHECK,  
            "selectNewDieFace" => ST_PLAYER_CONVERT_DIE,
            "moveShip" => ST_PLAYER_MOVE_SHIP,
            "advanceEconomy" => ST_PLAYER_ADVANCE_ECONOMY,
            "advanceDiplomacy" => ST_PLAYER_ADVANCE_DIPLOMACY,
            "chooseEmpireAction" => ST_PLAYER_CHOOSE_EMPIRE_ACTION,
            "nextFollower" => ST_NEXT_FOLLOWER,
            "pass" => ST_NEXT_PLAYER
        ]
    ],

    ST_PLAYER_CONVERT_DIE => [
        "name" => "convertDie",
        "description" => clienttranslate('${actplayer} must select die to convert and its new face'), 
        "descriptionmyturn" => clienttranslate('${you} must select die to convert and its new face'),
        "type" => "activeplayer",
        "args" => "argConvertDie",
        "possibleactions" => [
            "actConvertDie", 
        ],
        "transitions" => [
            "" => ST_PLAYER_CHOOSE_ACTION, 
        ]
    ],

    ST_PLAYER_MOVE_SHIP => [
        "name" => "moveShip",
        "description" => clienttranslate('${actplayer} must choose a ship to move'), 
        "descriptionmyturn" => clienttranslate('${you} must choose a ship to move'),
        "type" => "activeplayer",
        "args" => "argMoveShip",
        "possibleactions" => [
            "actMoveShip", 
        ],
        "transitions" => [
            "advanceDiplomacy" => ST_PLAYER_ADVANCE_DIPLOMACY,
            "advanceEconomy" => ST_PLAYER_ADVANCE_ECONOMY,
            "planetAndellouxian" => ST_PLAYER_PLANET_ANDELLOUXIAN,
            "planetBrumbaugh" => ST_PLAYER_PLANET_BRUMBAUGH,
            "planetClj0517" => ST_PLAYER_PLANET_CLJ0517,
            "planetGyore" => ST_PLAYER_CONVERT_DIE,
            "planetHelios" => ST_PLAYER_PLANET_HELIOS,
            "planetJorg" => ST_PLAYER_PLANET_JORG,
            "planetKwidow" => ST_PLAYER_PLANET_KWIDOW,
            "planetLatorres" => ST_PLAYER_PLANET_LATORRES,
            "nextFollower" => ST_NEXT_FOLLOWER,
        ]
    ],

    ST_PLAYER_CHOOSE_EMPIRE_ACTION => [
        "name" => "chooseEmpireAction",
        "description" => clienttranslate('${actplayer} must select an empire action'), 
        "descriptionmyturn" => clienttranslate('${you} must decide between upgrading your empire or using a colonized planet'),
        "type" => "activeplayer",
        "args" => "argChooseEmpireAction",
        "possibleactions" => [
            "actDecideEmpireAction", 
        ],
        "transitions" => [
            "advanceDiplomacy" => ST_PLAYER_ADVANCE_DIPLOMACY,
            "advanceEconomy" => ST_PLAYER_ADVANCE_ECONOMY,
            "planetAndellouxian" => ST_PLAYER_PLANET_ANDELLOUXIAN,
            "planetBrumbaugh" => ST_PLAYER_PLANET_BRUMBAUGH,
            "planetClj0517" => ST_PLAYER_PLANET_CLJ0517,
            "planetGyore" => ST_PLAYER_CONVERT_DIE,
            "planetHelios" => ST_PLAYER_PLANET_HELIOS,
            "planetJorg" => ST_PLAYER_PLANET_JORG,
            "planetKwidow" => ST_PLAYER_PLANET_KWIDOW,
            "planetLatorres" => ST_PLAYER_PLANET_LATORRES,
            "nextFollower" => ST_NEXT_FOLLOWER, 
        ]
    ],

    ST_PLAYER_ADVANCE_ECONOMY => [
        "name" => "advanceEconomy",
        "description" => clienttranslate('${actplayer} must choose a ship on orbit of economy type'), 
        "descriptionmyturn" => clienttranslate('${you} must choose a ship on orbit of economy type'),
        "type" => "activeplayer",
        "args" => "argAdvanceEconomy",
        "possibleactions" => [
            "actAdvanceEconomy", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],

    ST_PLAYER_ADVANCE_DIPLOMACY => [
        "name" => "advanceDiplomacy",
        "description" => clienttranslate('${actplayer} must choose a ship on orbit of diplomacy type'), 
        "descriptionmyturn" => clienttranslate('${you} must choose a ship on orbit of diplomacy type'),
        "type" => "activeplayer",
        "args" => "argAdvanceDiplomacy",
        "possibleactions" => [
            "actAdvanceDiplomacy", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],

    ST_PLAYER_DECIDE_FOLLOW => [
        "name" => "decideFollow",
        "description" => clienttranslate('${actplayer} must decide about following the last action'), 
        "descriptionmyturn" => clienttranslate('${you} must decide about following the last action'),
        "type" => "activeplayer",
        "args" => "argDecideFollow",
        "possibleactions" => [
            "actDecideFollow", 
        ],
        "transitions" => [
            "moveShip" => ST_PLAYER_MOVE_SHIP,
            "advanceEconomy" => ST_PLAYER_ADVANCE_ECONOMY,
            "advanceDiplomacy" => ST_PLAYER_ADVANCE_DIPLOMACY,
            "nextFollower" => ST_NEXT_FOLLOWER, 
        ]
    ],
];

$playerPlanetActionsGameStates = [
    ST_PLAYER_PLANET_ANDELLOUXIAN => [
        "name" => "planetAndellouxian",
        "description" => clienttranslate('${actplayer} must move 1 of his/her ships to his/her galaxy'), 
        "descriptionmyturn" => clienttranslate('${you} chose 1 of your ships to move to your galaxy'),
        "type" => "activeplayer",
        "possibleactions" => [
            "actPlanetAdellouxian", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],
    ST_PLAYER_PLANET_BRUMBAUGH => [
        "name" => "planetBrumbaugh",
        "description" => clienttranslate('${actplayer} must select 2 enemy ships to regress -1'), 
        "descriptionmyturn" => clienttranslate('${you} must select 2 enemy ships to regress -1'),
        "type" => "activeplayer",
        "args" => "argPlanetBrumbaugh",
        "possibleactions" => [
            "actPlanetBrumbaugh", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],
    ST_PLAYER_PLANET_HELIOS => [
        "name" => "planetHelios",
        "description" => clienttranslate('${actplayer} must place an un-occupied planet from the center row into the bottom of the planet deck'), 
        "descriptionmyturn" => clienttranslate('${you} must place an un-occupied planet from the center row into the bottom of the planet deck'),
        "type" => "activeplayer",
        "args" => "argPlanetHelios",
        "possibleactions" => [
            "actPlanetHelios", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],
    ST_PLAYER_PLANET_JORG => [
        "name" => "planetJorg",
        "description" => clienttranslate('${actplayer} must select an enemy ship to regress -2'), 
        "descriptionmyturn" => clienttranslate('${you} must select an enemy ship to regress -2'),
        "type" => "activeplayer",
        "args" => "argPlanetJorg",
        "possibleactions" => [
            "actPlanetJorg", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],
    ST_PLAYER_PLANET_KWIDOW => [
        "name" => "planetKwidow",
        "description" => clienttranslate('${actplayer} must regress an enemy ship -1'), 
        "descriptionmyturn" => clienttranslate('${you} must regress an enemy ship -1'),
        "type" => "activeplayer",
        "args" => "argPlanetBrumbaugh",
        "possibleactions" => [
            "actPlanetKwidow", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],
    ST_PLAYER_PLANET_LATORRES => [
        "name" => "planetLatorres",
        "description" => clienttranslate('${actplayer} must steal 1 energy from another player'), 
        "descriptionmyturn" => clienttranslate('${you} must select a player to steal 1 energy from'),
        "type" => "activeplayer",
        "args" => "argPlanetLatorres",
        "possibleactions" => [
            "actPlanetLatorres", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],
    ST_PLAYER_PLANET_CLJ0517 => [
        "name" => "planetClj0517",
        "description" => clienttranslate('${actplayer} must steal 1 culture from another player'), 
        "descriptionmyturn" => clienttranslate('${you} must select a player to steal 1 culture from'),
        "type" => "activeplayer",
        "args" => "argPlanetLatorres",
        "possibleactions" => [
            "actPlanetClj0517", 
        ],
        "transitions" => [
            "" => ST_NEXT_FOLLOWER, 
        ]
    ],
];

$gameGameStates = [
    ST_DEAL_MISSIONS => [
        "name" => "dealMissions",
        "description" => "",
        "type" => "game",
        "action" => "stDealMissions",
        "transitions" => [
            "" => ST_MULTIPLAYER_CHOOSE_MISSION,
        ],
    ],

    ST_NEXT_PLAYER => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => [
            "nextPlayer" => ST_PLAYER_CHOOSE_ACTION,
            "endScore" => ST_END_SCORE 
        ]
    ],

    ST_NEXT_FOLLOWER => [
        "name" => "nextFollower",
        "description" => '',
        "type" => "game",
        "action" => "stNextFollower",
        "transitions" => [
            "decideFollow" => ST_PLAYER_DECIDE_FOLLOW,
            "autoSkip" => ST_NEXT_FOLLOWER,
            "afterActionCheck" => ST_AFTER_ACTION_CHECK,
        ]
    ],

    ST_AFTER_ACTION_CHECK => [
        "name" => "afterActionCheck",
        "description" => '',
        "type" => "game",
        "action" => "stAfterActionCheck",
        "updateGameProgression" => true,
        "transitions" => [
            "chooseAction" => ST_PLAYER_CHOOSE_ACTION,
            "nextPlayer" => ST_NEXT_PLAYER,
            "endScore" => ST_END_SCORE 
        ]
    ],

    ST_END_SCORE => [
        "name" => "endScore",
        "description" => "",
        "type" => "game",
        "action" => "stEndScore",
        "transitions" => [
            "endGame" => ST_END_GAME,
        ],
    ],
];

$machinestates = $basicGameStates + $playerActionsGameStates + $playerPlanetActionsGameStates + $gameGameStates;
