<?php

// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
	require_once "../../config.php";
	$CURRENT_FILE = __FILE__;
	require $CFG->dirroot."/admin/migrate-setup.php";
}

//// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
	"drop table if exists {$CFG->dbprefix}iclicker_active",
	"drop table if exists {$CFG->dbprefix}iclicker_polls",
	"drop table if exists {$CFG->dbprefix}iclicker_responses",
	"drop table if exists {$CFG->dbprefix}iclicker_choices"
);


// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
	array( "{$CFG->dbprefix}iclicker_active",
	"create table {$CFG->dbprefix}iclicker_active (
		poll_id INTEGER NOT NULL,
		owner_id INTEGER NOT NULL
	)"),
	
	array( "{$CFG->dbprefix}iclicker_polls",
	"create table {$CFG->dbprefix}iclicker_polls (
		poll_id INTEGER NOT NULL UNIQUE PRIMARY KEY AUTO_INCREMENT,
		owner_id INTEGER NOT NULL,
		active INTEGER NOT NULL,
		modified INTEGER NOT NULL,
		completed INTEGER
	)"),
	
	array( "{$CFG->dbprefix}iclicker_responses",
	"create table {$CFG->dbprefix}iclicker_responses (
		response_id INTEGER NOT NULL UNIQUE PRIMARY KEY AUTO_INCREMENT,
		user_id INTEGER NOT NULL,
		poll_id INTEGER NOT NULL, 
		choice_id INTEGER NOT NULL,
		timestamp INTEGER NOT NULL
	)"),
	
	array( "{$CFG->dbprefix}iclicker_choices",
	"create table {$CFG->dbprefix}iclicker_choices (
		choice_id INTEGER NOT NULL UNIQUE AUTO_INCREMENT PRIMARY KEY,
		choice_value TEXT
	)")
);
