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
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Give some extra time to the active player when he completed an action
        $this->giveExtraTime($player_id);
        
        $this->activeNextPlayer();

        // Go to another gamestate
        // Here, we would detect if the game is over, and in this case use "endGame" transition instead 
        $this->gamestate->nextState("nextPlayer");
    }

    function stDiceRoll() {
        $this->resetDice();

        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        $dice_count = $this->getPlayerDiceCount($player_id);
        $this->rollDice($dice_count);

        $this->gamestate->nextState("noDiceChoice"); 
    }

    public function stEndScore() {
        // TODO

        $this->gamestate->nextState('endGame');
    }
}
