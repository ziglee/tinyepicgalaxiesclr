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
        // $player_id = (int)$this->getActivePlayerId();

        // if ($this->isAllRolledDiceUsed()) {
        //     $this->gamestate->nextState("rollDice");
        //     return;
        // }

        // // Give some extra time to the active player when he completed an action
        // $this->giveExtraTime($player_id);

        // TODO: check score
        //$this->gamestate->nextState("endScore");
        
        
        $this->setGameStateValue("free_roll_used", 0);
        $this->resetDice();

        $player_id = intval($this->activeNextPlayer());
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

        // Go to another gamestate
        // Here, we would detect if the game is over, and in this case use "endGame" transition instead 
        $this->gamestate->nextState("nextPlayer");
    }

    public function stAfterActionCheck(): void {
        if ($this->isAllRolledDiceUsed()) {
            $this->gamestate->nextState("nextPlayer");
            return;
        }

        // TODO check if all dice are used
        $this->gamestate->nextState("chooseAction");
    }

    public function stEndScore() {
        // TODO

        $this->gamestate->nextState('endGame');
    }
}
