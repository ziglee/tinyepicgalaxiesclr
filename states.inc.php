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
            "executeAction" => ST_PLAYER_CHOOSE_ACTION, 
            "afterActionCheck" => ST_AFTER_ACTION_CHECK,  
            "selectNewDieFace" => ST_PLAYER_CONVERT_DIE,
            "moveShip" => ST_PLAYER_MOVE_SHIP,
            "advanceEconomy" => ST_PLAYER_ADVANCE_ECONOMY,
            "advanceDiplomacy" => ST_PLAYER_ADVANCE_DIPLOMACY,
            "chooseEmpireAction" => ST_PLAYER_CHOOSE_ACTION,
            "chooseHowToUpgradeEmpire" => ST_PLAYER_UPGRADE_EMPIRE,
            "pass" => ST_NEXT_PLAYER
        ]
    ],

    ST_PLAYER_CONVERT_DIE => [
        "name" => "convertDie",
        "description" => clienttranslate('${actplayer} select die to convert and its new face'), 
        "descriptionmyturn" => clienttranslate('${you} select die to convert and its new face'),
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
            "" => ST_AFTER_ACTION_CHECK, 
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
            "" => ST_AFTER_ACTION_CHECK, 
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
            "" => ST_AFTER_ACTION_CHECK, 
        ]
    ],

    ST_PLAYER_UPGRADE_EMPIRE => [
        "name" => "upgradeEmpire",
        "description" => clienttranslate('${actplayer} must select energy or culture to upgrade your empire'), 
        "descriptionmyturn" => clienttranslate('${you} must select energy or culture to upgrade your empire'),
        "type" => "activeplayer",
        "possibleactions" => [
            "actUpgradeEmpire", 
        ],
        "transitions" => [
            "" => ST_AFTER_ACTION_CHECK, 
        ]
    ]
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

$machinestates = $basicGameStates + $playerActionsGameStates + $gameGameStates;
