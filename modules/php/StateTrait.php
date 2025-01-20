<?php

namespace Bga\Games\tinyepicgalaxiesclr;

trait StateTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////
    
    public function stDealMissions() {
        $playersIds = $this->getPlayersIds();

        foreach($playersIds as $playerId) {
            //$this->pickInitialDestinationCards($playerId);
        }
        
        $this->gamestate->nextState('');
    }

    function stChooseMission() { 
        $this->gamestate->setAllPlayersMultiactive();
        $this->gamestate->initializePrivateStateForAllActivePlayers(); 
    }

    public function stNextPlayer(): void {
        // // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();
        
        $lastTurn = intval(self::getGameStateValue(LAST_TURN));

        // check if it was last action from player who started last turn
        if ($lastTurn == $player_id) {
            $this->gamestate->nextState('endScore');
        } else {
            if ($lastTurn == 0) {
                // check if last turn is started
                $playersMaxScore = $this->getPlayersMaxScore();
                if ($playersMaxScore >= 21) {
                    self::setGameStateValue(LAST_TURN, $player_id);

                    self::notifyAllPlayers('lastTurn', clienttranslate('Starting final turn!'), []);
                }
            }
            
            $this->setGameStateValue(TURN_OWNER_ID, $player_id);
            $this->setGameStateValue(BIRKOMIUS_TRIGGERED, 0);
            $this->setGameStateValue(BISSCHOP_TRIGGERED, 0);
            $this->setGameStateValue(NIBIRU_TRIGGERED, 0);
            $this->setGameStateValue(FREE_REROLL_USED, 0);
            $this->resetDice();
    
            $player_id = intval($this->activeNextPlayer());
            self::giveExtraTime($player_id);

            $this->setGameStateValue(PLAYER_ID_ACTIVATING_DIE, $player_id);
            $this->resetPlayerAddDieNextTurn($player_id);

            $dice_count = $this->getPlayerDiceCount($player_id);
            $this->rollDice($dice_count);
    
            $this->notifyAllPlayers(
                "diceUpdated", 
                clienttranslate( '${player_name} rolled the dice' ), 
                array(
                    'player_id' => $player_id,
                    'player_name' => $this->getActivePlayerName(),
                    'dice' => $this->getDice(),
                )
            );

            $this->gamestate->nextState("nextPlayer");
        }
    }

    public function stAfterActionCheck(): void {
        $this->setGameStateValue(FOLLOWERS_COUNT, 0);
        if ($this->isAllRolledDiceUsed()) {
            $this->gamestate->nextState("nextPlayer");
            return;
        }
        $this->gamestate->nextState("chooseAction");
    }

    public function stNextFollower(): void {
        $playerId = intval($this->activeNextPlayer());

        $followersCount = $this->getGameStateValue(FOLLOWERS_COUNT);
        if ($followersCount == $this->getPlayersNumber() - 1) {
            $this->gamestate->nextState("afterActionCheck");
            return;
        }
        
        $this->setGameStateValue(FOLLOWERS_COUNT, $followersCount + 1);
        $playerObj = $this->getPlayerObject($playerId);
        $cultureLevel = $playerObj['culture_level'];
        $nibiruTriggered = $this->getGameStateValue(NIBIRU_TRIGGERED) == 1;

        if ((!$nibiruTriggered && $cultureLevel >= 1) || ($nibiruTriggered && $cultureLevel >= 2)) {
            $this->gamestate->nextState("decideFollow");
        } else {
            $this->gamestate->nextState("autoSkip");
        }
    }

    public function stEndScore() {
        // TODO

        $this->gamestate->nextState('endGame');
    }
}
