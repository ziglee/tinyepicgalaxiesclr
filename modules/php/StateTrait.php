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
                if ($playersMaxScore > 21) {
                    self::setGameStateValue(LAST_TURN, $player_id);

                    self::notifyAllPlayers('lastTurn', clienttranslate('Starting final turn!'), []);
                }
            }
            
            $this->setGameStateValue(FREE_REROLL_USED, 0);
            $this->resetDice();
    
            $player_id = intval($this->activeNextPlayer());
            self::giveExtraTime($player_id);
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
        if ($this->isAllRolledDiceUsed()) {
            $this->gamestate->nextState("nextPlayer");
            return;
        }
        $this->gamestate->nextState("chooseAction");
    }

    public function stEndScore() {
        // TODO

        $this->gamestate->nextState('endGame');
    }
}
