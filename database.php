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
	"drop table if exists {$CFG->dbprefix}iclicker_choices",
	"drop table if exists {$CFG->dbprefix}iclicker_pollchoices"
);


// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
	array( "{$CFG->dbprefix}iclicker_active",
	"create table {$CFG->dbprefix}iclicker_active (
		context_id INTEGER NOT NULL UNIQUE,
		poll_id INTEGER NOT NULL
	)"),
	
	array( "{$CFG->dbprefix}iclicker_polls",
	"create table {$CFG->dbprefix}iclicker_polls (
		poll_id INTEGER NOT NULL UNIQUE PRIMARY KEY AUTO_INCREMENT,
		context_id INTEGER NOT NULL,
		answer_id INTEGER,
		ordered INTEGER,
		active INTEGER,
		pending INTEGER,
		title TEXT,
		modified DATETIME NOT NULL,
		completed DATETIME
	)"),
	
	array( "{$CFG->dbprefix}iclicker_responses",
	"create table {$CFG->dbprefix}iclicker_responses (
		response_id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
		user_id INTEGER NOT NULL,
		poll_id INTEGER NOT NULL, 
		choice_id INTEGER NOT NULL,
		timestamp TIMESTAMP NOT NULL,
		UNIQUE KEY unique_index (poll_id, user_id)
	)"),
	
	array( "{$CFG->dbprefix}iclicker_choices",
	"create table {$CFG->dbprefix}iclicker_choices (
		choice_id INTEGER NOT NULL UNIQUE AUTO_INCREMENT PRIMARY KEY,
		choice_hash VARCHAR(41) NOT NULL,
		choice_value TEXT
	)"),
	
	array( "{$CFG->dbprefix}iclicker_pollchoices",
	"create table {$CFG->dbprefix}iclicker_pollchoices (
		poll_id INTEGER NOT NULL,
		choice_id INTEGER NOT NULL
	)"),
	
	array( "{$CFG->dbprefix}iclicker_order",
	"create table {$CFG->dbprefix}iclicker_order (
		poll_id INTEGER NOT NULL,
		choice_id INTEGER NOT NULL,
		position INTEGER NOT NULL
	)")
);
