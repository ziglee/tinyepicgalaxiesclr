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

 function updateDice(dice) {
    dojo.empty('dice-buttons');
    for (let dieId = 1; dieId <= 7; dieId++) {
        const die = dice[dieId];
        dojo.attr('die-slot-' + (die.id), 'data-face', die.face);
        dojo.attr('die-slot-' + (die.id), 'data-used', die.used);
        dojo.removeClass('die-slot-' + (die.id), 'die-active');
    }
    dojo.empty('dice-face-selection-tray');
    for (let dieId = 1; dieId <= 7; dieId++) {
        const die = dice[dieId];
        if (die.used == '0' && die.face != '0') {
            document.getElementById('dice-face-selection-tray').insertAdjacentHTML('beforeend', `
                <div class="die-convert-slot die-face" id="die-convert-slot-${die.id}" data-face="${die.face}" data-used="0"></div>
            `);
        }
    }
 }

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
                <div id="missions-to-choose"></div>
                <div class="whiteblock" id="dice-tray">
                    <strong>Dice tray</strong>
                    <div class="die-slot die-face" id="die-slot-1" data-face="0" data-used="0"></div>
                    <div class="die-slot die-face" id="die-slot-2" data-face="0" data-used="0"></div>
                    <div class="die-slot die-face" id="die-slot-3" data-face="0" data-used="0"></div>
                    <div class="die-slot die-face" id="die-slot-4" data-face="0" data-used="0"></div>
                    <div class="die-slot die-face" id="die-slot-5" data-face="0" data-used="0"></div>
                    <div class="die-slot die-face" id="die-slot-6" data-face="0" data-used="0"></div>
                    <div class="die-slot die-face" id="die-slot-7" data-face="0" data-used="0"></div>
                    <div id="dice-buttons"></div>
                </div>
                <div class="whiteblock" id="dice-face-selection" style="display: none;">
                    <strong>Choose die to convert</strong>
                    <div id="dice-face-selection-tray" style="display: flex;"></div>
                    <strong>Choose new face</strong>
                    <div id="dice-face-selection-options">
                        <div class="die-newface die-face" data-face="1"></div>
                        <div class="die-newface die-face" data-face="2"></div>
                        <div class="die-newface die-face" data-face="3"></div>
                        <div class="die-newface die-face" data-face="4"></div>
                        <div class="die-newface die-face" data-face="5"></div>
                        <div class="die-newface die-face" data-face="6"></div>
                    </div>
                </div>
                <div class="whiteblock" id="planet-cards-row">
                </div>
                <div id="activation-bay"></div>
                <div id="player-tables"></div>
            `);
            
            // Set up your game interface here, according to "gamedatas"
            updateDice(gamedatas.dice);
            document.querySelectorAll('.die-slot').forEach(die => {
                die.addEventListener('click', e => this.onDieClick(e));
            });
            document.querySelectorAll('.die-convert-slot').forEach(die => {
                die.addEventListener('click', e => this.onDieToConvertClick(e));
            });
            document.querySelectorAll('.die-newface').forEach(die => {
                die.addEventListener('click', e => this.onDieNewFaceClick(e));
            });

            // Missions to choose
            if (gamedatas.missions) {
                for (let i in gamedatas.missions) {
                    const mission = gamedatas.missions[i];
                    document.getElementById('missions-to-choose').insertAdjacentHTML('beforeend', `
                        <div class="mission-card-to-choose" id="missioncardtochoose-${mission.id}">
                            <div>${mission.type}</div>
                        </div>
                    `);
                }
                document.querySelectorAll('.mission-card-to-choose').forEach(mission => mission.addEventListener('click', e => this.onMissionCartToChooseClick(e)));
            }
            
            // Planets on center row
            for (let i in gamedatas.centerrow) {
                const planet = gamedatas.centerrow[i];
                document.getElementById('planet-cards-row').insertAdjacentHTML('beforeend', `
                    <div class="planet-card" id="planet-${planet.id}">
                        <div>${planet.info.name} ${planet.type} (Points ${planet.info.pointsWorth})</div>
                        <div class="planet-track" id="planet-track-${planet.id}"></div>
                    </div>
                `);
                document.getElementById(`planet-track-${planet.id}`).insertAdjacentHTML('beforeend', `
                    <div class="planet-track-start" id="planet-track-${planet.id}-slot-start">start</div>
                `);
                for (let trackSlot = 1; trackSlot <= planet.info.trackLength; trackSlot++) {
                document.getElementById(`planet-track-${planet.id}`).insertAdjacentHTML('beforeend', `
                    <div class="planet-track-slot" id="planet-track-${planet.id}-slot-${trackSlot}">${trackSlot}</div>
                `);
                }
                document.getElementById(`planet-track-${planet.id}`).insertAdjacentHTML('beforeend', `
                    <div class="planet-track-end" id="planet-track-${planet.id}-slot-end">${planet.info.trackType}</div>
                `);
            }

            // Setting up player boards
            Object.values(gamedatas.players).forEach(player => {
                // example of setting up players boards
                // this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                //     <div id="player-counter-${player.id}">A player counter</div>
                // `);

                document.getElementById('player-tables').insertAdjacentHTML('beforeend', `
                    <div class="whiteblock" id="player-table-${player.id}">
                        <strong style="color:#${player.color};">${player.name}</strong>
                        <div class="galaxy-mat" id="galaxy-mat-${player.id}">
                            <div class="ship-hangar" id="ships-hangar-${player.id}">
                                Hangar
                            </div>
                            <div class="empire-track" id="empire-track-${player.id}">
                                Empire
                                <div class="empire-track-slot" id="empire-track-${player.id}-slot-1">0</div>
                                <div class="empire-track-slot" id="empire-track-${player.id}-slot-2">2</div>
                                <div class="empire-track-slot" id="empire-track-${player.id}-slot-3">3</div>
                                <div class="empire-track-slot" id="empire-track-${player.id}-slot-4">4</div>
                                <div class="empire-track-slot" id="empire-track-${player.id}-slot-5">5</div>
                                <div class="empire-track-slot" id="empire-track-${player.id}-slot-6">6</div>
                            </div>
                            <div class="energy-culture-track" id="energy-culture-track-${player.id}">
                                Energy/Culture
                                <div class="energy-culture-track-slot" id="energy-culture-track-${player.id}-slot-0">0</div>
                                <div class="energy-culture-track-slot" id="energy-culture-track-${player.id}-slot-1">1</div>
                                <div class="energy-culture-track-slot" id="energy-culture-track-${player.id}-slot-2">2</div>
                                <div class="energy-culture-track-slot" id="energy-culture-track-${player.id}-slot-3">3</div>
                                <div class="energy-culture-track-slot" id="energy-culture-track-${player.id}-slot-4">4</div>
                                <div class="energy-culture-track-slot" id="energy-culture-track-${player.id}-slot-5">5</div>
                                <div class="energy-culture-track-slot" id="energy-culture-track-${player.id}-slot-6">6</div>
                                <div class="energy-culture-track-slot" id="energy-culture-track-${player.id}-slot-7">7</div>
                            </div>
                        </div>
                        <div class="colonized-planets-row" id="colonized-planets-row-${player.id}">
                            <strong>Colonized planets row</strong>
                        </div>
                    </div>
                `);

                document.getElementById(`energy-culture-track-${player.id}-slot-${player.energy_level}`).insertAdjacentHTML(
                    'beforeend', 
                    `<div class="energy-token" data-color="${player.color}" id="energy-token-${player.id}">EN</div>`
                );
                document.getElementById(`energy-culture-track-${player.id}-slot-${player.culture_level}`).insertAdjacentHTML(
                    'beforeend', 
                    `<div class="culture-token" data-color="${player.color}" id="culture-token-${player.id}">CU</div>`
                );
                document.getElementById(`empire-track-${player.id}-slot-${player.empire_level}`).insertAdjacentHTML(
                    'beforeend', 
                    `<div class="empire-token" data-color="${player.color}" id="empire-token-${player.id}">EM</div>`
                );
            });

            // Colonized planets in player's area
            for (let i in gamedatas.colonizedplanets) {
                const planet = gamedatas.colonizedplanets[i];
            }

            Object.values(gamedatas.ships).forEach(ship => {
                if (ship.planet_id == null) {
                    document.getElementById(`ships-hangar-${ship.player_id}`).insertAdjacentHTML('beforeend', `
                        <div class="ships-hangar-slot" id="ship-${ship.id}">S-${ship.id}</div>
                    `);
                }
                console.log(ship);
            });
 
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
            case 'convertDie':
                dojo.style( 'dice-tray', 'display', 'none' );
                dojo.style( 'dice-face-selection', 'display', 'block' );
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
            case 'convertDie':
                dojo.style( 'dice-tray', 'display', 'flex' );
                dojo.style( 'dice-face-selection', 'display', 'none' );
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
                    case 'chooseAction':
                        this.addActionButton(`actPass-btn`, _('Pass'), () => this.onPassClick());
                        // this.addActionButton(`actChooseActionActivateDie-btn`, _('Activate die'), () => this.onChooseActionActivateDieClick());
                        // if (args.canFreeReroll || args.canReroll) {
                        //     this.addActionButton(`actChooseActionRerollDice-btn`, _('Reroll dice'), () => this.onChooseActionRerollDiceClick());
                        // }
                        // if (args.canConvert) {
                        //     this.addActionButton(`actChooseActionConvertDie-btn`, _('Convert die'), () => this.onChooseActionConvertDieClick());
                        // }
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
        onMissionCartToChooseClick: function( evt )
        {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            // The click does nothing when not active
            if (!this.isCurrentPlayerActive()) return;

            const missionId = evt.currentTarget.id.split('-')[1];
            this.onChooseMissionClick(missionId);
        },

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

        onDieClick: function( evt )
        {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            // The click does nothing when not active
            if (!this.isCurrentPlayerActive()) return;

            const used = dojo.attr(evt.currentTarget.id, 'data-used');
            if (used == '1') return;

            dojo.toggleClass(evt.currentTarget.id, 'die-active');

            const ids = dojo.query('.die-active').map(function(node) { 
                return node.id.split('-')[2]; 
            });
            dojo.empty('dice-buttons');
            if (ids.length == 1) {
                document.getElementById('dice-buttons').insertAdjacentHTML('beforeend', `
                    <a href="#" id="activate-die-btn" class="bgabutton bgabutton_blue"><span>Activate die</span></a>
                `);
                document.getElementById('dice-buttons').insertAdjacentHTML('beforeend', `
                    <a href="#" id="reroll-dice-btn" class="bgabutton bgabutton_blue"><span>Reroll die</span></a>
                `);
                document.getElementById('activate-die-btn').addEventListener('click', e => this.onActivateDieClick(ids[0]));
                document.getElementById('reroll-dice-btn').addEventListener('click', e => this.onRerollDiceClick(ids));
            } else if (ids.length == 2) {
                document.getElementById('dice-buttons').insertAdjacentHTML('beforeend', `
                    <a href="#" id="convert-dice-btn" class="bgabutton bgabutton_blue"><span>Convert die</span></a>
                `);
                document.getElementById('dice-buttons').insertAdjacentHTML('beforeend', `
                    <a href="#" id="reroll-dice-btn" class="bgabutton bgabutton_blue"><span>Reroll dice</span></a>
                `);
                document.getElementById('convert-dice-btn').addEventListener('click', e => this.onSelectConverterDiceClick(ids[0], ids[1]));
                document.getElementById('reroll-dice-btn').addEventListener('click', e => this.onRerollDiceClick(ids));
            } else if (ids.length > 2) {
                document.getElementById('dice-buttons').insertAdjacentHTML('beforeend', `
                    <a href="#" id="reroll-dice-btn" class="bgabutton bgabutton_blue"><span>Reroll dice</span></a>
                `);
                document.getElementById('reroll-dice-btn').addEventListener('click', e => this.onRerollDiceClick(ids));
            }
        },

        onDieToConvertClick: function(evt) {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            // The click does nothing when not active
            if (!this.isCurrentPlayerActive()) return;

            dojo.query('.die-convert-active').forEach(die => dojo.removeClass(die.id, 'die-convert-active'));
            dojo.toggleClass(evt.currentTarget.id, 'die-convert-active');
        },

        onDieNewFaceClick: function(evt) {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            // The click does nothing when not active
            if (!this.isCurrentPlayerActive()) return;

            const dieToConvert = dojo.query('.die-convert-active');
            if(dieToConvert.length == 0) return;
            
            const dieId = dieToConvert[0].id.split('-')[3];
            const newFace = evt.currentTarget.dataset.face;
            this.bgaPerformAction("actConvertDie", {
                dieId: dieId,
                newFace: newFace,
            }).then(() =>  {
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });
        },

        onActivateDieClick: function(dieId) {
            console.log('onActivateDieClick', dieId);
            this.bgaPerformAction("actActivateDie", {
                dieId: dieId,
            }).then(() =>  {
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });
        },

        onRerollDiceClick: function(ids) {
            console.log('onRerollDiceClick', ids);
            this.bgaPerformAction("actRerollDice", {
                ids: ids,
            }).then(() =>  {
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });
        },

        onSelectConverterDiceClick: function(die1id, die2id) {
            console.log('onSelectConverterDiceClick', die1id, die2id);
            this.bgaPerformAction("actSelectConverterDice", {
                die1id: die1id,
                die2id: die2id,
            }).then(() =>  {
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });
        },

        onPassClick: function() {
            this.bgaPerformAction("actPass", {}).then(() =>  {
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
        
        // From this point and below, you can write your game notifications handling methods
        
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

        notif_missionChoosed: async function( notif )
        {
            console.log('notif_missionChoosed', notif);
            dojo.destroy('missions-to-choose');
        },

        notif_diceUpdated: async function( notif )
        {
            console.log('notif_diceUpdated', notif);
            updateDice(notif.dice);
        },

        notif_freeRerollWasUsed: async function( notif )
        {
            console.log('notif_freeRerollWasUsed', notif);
        },

        notif_energyLevelChanged: async function( notif )
        {
            console.log('notif_energyLevelChanged', notif);
            const player_id = notif.player_id;
            const new_energy_level = notif.new_energy_level;
            const anim = this.slideToObject(`energy-token-${player_id}`, `energy-culture-track-${player_id}-slot-${new_energy_level}`);
            await this.bgaPlayDojoAnimation(anim);
        },
   });             
});
