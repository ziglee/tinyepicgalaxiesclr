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
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);

namespace Bga\Games\tinyepicgalaxiesclr;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");
require_once("constants.inc.php");

class Game extends \Table
{
    use UtilTrait;
    use ActionTrait;
    use StateTrait;
    use ArgsTrait;

    private \Deck $missionCards;
    private \Deck $planetCards;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            FREE_REROLL_USED => 11,
            LAST_TURN => 12,
            DIE_FACE_ACTIVATED => 13,
            PLAYER_ID_ACTIVATING_DIE => 14,
            FOLLOWERS_COUNT => 15,
            TURN_OWNER_ID => 16,
            BIRKOMIUS_TRIGGERED => 17,
            BISSCHOP_TRIGGERED => 18,
            NIBIRU_TRIGGERED => 19,
        ]);

        $this->missionCards = $this->getNew("module.common.deck");
        $this->missionCards->init("mission_cards");

        $this->planetCards = $this->getNew("module.common.deck");
        $this->planetCards->init("planet_cards");
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName()
    {
        return "tinyepicgalaxiesclr";
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        //$this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.
        $this->setGameStateInitialValue(FREE_REROLL_USED, 0);
        $this->setGameStateInitialValue(LAST_TURN, 0);
        $this->setGameStateInitialValue(DIE_FACE_ACTIVATED, 0);
        $this->setGameStateInitialValue(PLAYER_ID_ACTIVATING_DIE, 0);
        $this->setGameStateInitialValue(FOLLOWERS_COUNT, 0);
        $this->setGameStateInitialValue(TURN_OWNER_ID, 0);
        $this->setGameStateInitialValue(BIRKOMIUS_TRIGGERED, 0);
        $this->setGameStateInitialValue(BISSCHOP_TRIGGERED, 0);
        $this->setGameStateInitialValue(NIBIRU_TRIGGERED, 0);

        $playerCount = count($players);

        // Create mission cards
        $missionCards = [];
        $missions = [];
        if ($playerCount >= 3) {
            $missions = allMissions;
        } else {
            $missions = duelMissions;
        }
        foreach ($missions as $mission) {
            $missionCards[] = ['type' => $mission, 'type_arg' => 1, 'nbr' => 1];
        }
        $this->missionCards->createCards($missionCards, 'deck');
        $this->missionCards->shuffle('deck');

        // Create planet cards
        $planetCards = [];
        foreach (PLANETS_BY_TYPE as $planet_type => $planets) {
            foreach ($planets as $planet_id => $planet) {
                $planetCards[] = ['type' => $planet_type, 'type_arg' => $planet_id, 'nbr' => 1];
            }
        }
        $this->planetCards->createCards($planetCards, 'deck');
        $this->planetCards->shuffle('deck');

        // Count the number of plantes to deal on center row
        //$player_list = $this->getObjectListFromDB("SELECT player_id id FROM player", true);
        $deal_amount = $playerCount + 2;
        if ($playerCount == 5) {
            $deal_amount = 6;
        }
        $this->planetCards->pickCardsForLocation($deal_amount, 'deck', 'centerrow');

        // Draw missions cards
        foreach ($players as $player_id => $player) {
            $this->missionCards->pickCards(2, 'deck', $player_id);
        }

        // Init game statistics.
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.
        $this->initStat('table', 'turnsNumber', 0);
        $this->initStat('player', 'turnsNumber', 0);

        // Setup the initial game situation here.
        foreach ($players as $player_id => $player) {
            $this->DbQuery("INSERT INTO ships (player_id) VALUES ('$player_id'), ('$player_id')");
        }

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas()
    {
        $stateName = $this->gamestate->state()['name']; 
        $isEnd = $stateName === 'endScore' || $stateName === 'gameEnd';

        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        $players = $this->getPlayersCustomCollection();
        $result["players"] = $players;

        // Gather all information about current game situation (visible by player $current_player_id).
        $result['free_reroll_used'] = $this->getGameStateValue(FREE_REROLL_USED);
        $result["dice"] = $this->getDice();
        $result["ships"] = $this->getCollectionFromDb(
            "SELECT `ship_id` `id`, `player_id`, `planet_id`, `track_progress` FROM `ships` ORDER BY `ship_id`"
        );
  
        // Missions in player hand
        $missionCards = array_values($this->missionCards->getPlayerHand($current_player_id));
        $missionsCount = count($missionCards);
        if ($missionsCount == 1) {
            $result['mission'] = $missionCards[0];
        } else {
            $result['missions'] = $missionCards;
        }

        // Colonized planets in players area
        $colonizedPlanetsByPlayerId = [];
        foreach ($players as $playerId => $player) {
            $colonizedPlanetsByPlayerId[$playerId] = $this->getPlanetsFromDb($this->planetCards->getCardsInLocation('colony', $playerId));
        }
        $result['colonizedplanets'] = $colonizedPlanetsByPlayerId;
  
        // Planets on the center table row
        $result['centerrow'] = $this->getPlanetsFromDb($this->planetCards->getCardsInLocation('centerrow'));

        if ($isEnd) {
            // TODO
        } else {
            $result['lastTurn'] = $this->getGameStateValue(LAST_TURN) > 0;
        }

        return $result;
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        $highestScore = $this->getPlayersMaxScore();
        return (100 * $highestScore) / 21;
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default:
                {
                    $this->gamestate->nextState("zombiePass");
                    break;
                }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
//       if ($from_version <= 1404301345)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
//
//       if ($from_version <= 1405061421)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
    }
}
