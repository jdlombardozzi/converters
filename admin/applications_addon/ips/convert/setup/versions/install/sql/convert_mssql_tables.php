<?php
$TABLE[] = "CREATE TABLE conv_link (
 link_id	 BIGINT NOT NULL IDENTITY,
 ipb_id		 INT NOT NULL default '0',
 foreign_id	 VARCHAR(255) NOT NULL default '0',
 type		 VARCHAR(32) default NULL,
 duplicate	 INT NOT NULL default '0',
 app 		INT NOT NULL default '0',
 PRIMARY KEY (link_id) );";

$TABLE[] = "CREATE TABLE conv_apps (
	 app_id 	INT NOT NULL IDENTITY,
	 sw 		VARCHAR(32) default NULL,
	 app_key	VARCHAR(32) default NULL,
	 name		VARCHAR(255) default NULL,
	 login		INT NOT NULL default '0',
	 parent 	INT NOT NULL default '0',
	 db_driver	VARCHAR(32) default NULL,
	 db_host	VARCHAR(128) default NULL,
	 db_user	VARCHAR(128) default NULL,
	 db_pass	VARCHAR(128) default NULL,
	 db_db		VARCHAR(128) default NULL,
	 db_prefix	VARCHAR(32) default NULL,
	 db_charset	VARCHAR(32) default NULL,
	PRIMARY KEY	(app_id),
)";

$TABLE[] = "CREATE INDEX foreign_id ON conv_link (foreign_id, type, app);";
$TABLE[] = "CREATE INDEX type ON conv_link (type);";
$TABLE[] = "CREATE INDEX name ON conv_apps (name);";
?>