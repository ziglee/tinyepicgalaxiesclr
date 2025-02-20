
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- tinyepicgalaxiesclr implementation : © Cássio Landim Ribeiro <ziglee@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

 ALTER TABLE `player` ADD `empire_level` INT UNSIGNED NOT NULL DEFAULT '1';
 ALTER TABLE `player` ADD `energy_level` INT UNSIGNED NOT NULL DEFAULT '2';
 ALTER TABLE `player` ADD `culture_level` INT UNSIGNED NOT NULL DEFAULT '1';
 ALTER TABLE `player` ADD `dice_count` INT UNSIGNED NOT NULL DEFAULT '4';
 ALTER TABLE `player` ADD `mission` varchar(16) DEFAULT NULL;
 ALTER TABLE `player` ADD `dice_to_add_next_turn` INT UNSIGNED NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `planet_cards` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mission_cards` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `dice` (
  `die_id` int(10) unsigned NOT NULL,
  `face` int(10) unsigned DEFAULT '0',
  `used` BOOL DEFAULT false,
  `converter` BOOL DEFAULT false,
  PRIMARY KEY (`die_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `dice` (`die_id`) VALUES (1), (2), (3), (4), (5), (6), (7);

CREATE TABLE IF NOT EXISTS `ships` (
  `ship_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL,
  `planet_id` int(10) unsigned DEFAULT NULL,
  `track_progress` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`ship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
