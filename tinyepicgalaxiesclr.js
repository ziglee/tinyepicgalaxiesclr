/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * tinyepicgalaxiesclr implementation : © Cássio Landim Ribeiro <ziglee@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * tinyepicgalaxiesclr.js
 *
 * tinyepicgalaxiesclr user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.tinyepicgalaxiesclr", ebg.core.gamegui, {
        constructor: function(){
            console.log('tinyepicgalaxiesclr constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log("Starting game setup", gamedatas);

            // Example to add a div on the game area
            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                <div id="missions-to-pick"></div>
                <div class="whiteblock" id="planet-cards-row">
                    <strong>Planet cards row</strong>
                </div>
                <div id="activation-bay"></div>
                <div id="player-tables"></div>
            `);
            
            // Setting up player boards
            Object.values(gamedatas.players).forEach(player => {
                // example of setting up players boards
                // this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                //     <div id="player-counter-${player.id}">A player counter</div>
                // `);

                document.getElementById('player-tables').insertAdjacentHTML('beforeend', `
                    <div class="whiteblock" id="player-table-${player.id}">
                        <strong style="color:#${player.color};">${player.name}</strong>
                        <div class="galaxy-mat" id="galaxy-mat-${player.id}"></div>
                        <div class="colonized-planets-row" id="colonized-planets-row-${player.id}">
                            <strong>Colonized planets row</strong>
                        </div>
                    </div>
                `);
            });
            
            // TODO: Set up your game interface here, according to "gamedatas"
            
            // Missions to pick
            for (let i in gamedatas.missions) {
                const mission = gamedatas.missions[i];
                document.getElementById('missions-to-pick').insertAdjacentHTML('beforeend', `
                    <div id="mission-to-pick-${mission.id}">
                        <div>${mission.type}</div>
                    </div>
                `);
            }
            
            // Planets on center row
            for (let i in gamedatas.centerrow) {
                const planet = gamedatas.centerrow[i];
                document.getElementById('planet-cards-row').insertAdjacentHTML('beforeend', `
                    <div id="planet-${planet.id}">
                        <div>${planet.info.name} ${planet.type} ${planet.info.trackType}</div>
                    </div>
                `);
            }

            // Colonized planets in player's area
            for (let i in gamedatas.colonizedplanets) {
                const planet = gamedatas.colonizedplanets[i];
            }
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName, args );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName, args );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                 case 'privateChooseMission':    
                    const missions = args.missions; // returned by the argPrivateChooseMission
                    missions.forEach(
                        mission => this.addActionButton(`actChooseMission${mission.id}-btn`, _('Choose mission ${mission}').replace('${mission}', mission.type), () => this.onChooseMissionClick(mission.id)) 
                    );
                    break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        onChooseMissionClick: function( missionId )
        {
            console.log( 'onChooseMissionClick', missionId );

            this.bgaPerformAction("actChooseMission", { 
                selectedMissionId: missionId,
            }).then(() =>  {
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });        
        },    

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your tinyepicgalaxiesclr.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            // automatically listen to the notifications, based on the `notif_xxx` function on this class.
            this.bgaSetupPromiseNotifications();
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});
