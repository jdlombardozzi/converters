<?php
$TABLE[] = "CREATE TABLE conv_link (
	 link_id	int(10) unsigned NOT NULL auto_increment,
	 ipb_id		int(10) NOT NULL default '0',
	 foreign_id	varchar(255) NOT NULL default '0',
	 type		varchar(32) default NULL,
	 duplicate	tinyint(1) NOT NULL default '0',
	 app		int(10) NOT NULL default '0',
	 PRIMARY KEY	(link_id),
	 KEY `foreign_id` (`foreign_id`,`type`,`app`),
	 KEY `type` (`type`) )";
	 
$TABLE[] = "CREATE TABLE conv_link_topics (
	 link_id	int(10) unsigned NOT NULL auto_increment,
	 ipb_id		bigint(10) NOT NULL default '0',
	 foreign_id	bigint(10) NOT NULL default '0',
	 type		varchar(32) default NULL,
	 duplicate	tinyint(1) NOT NULL default '0',
	 app		int(10) NOT NULL default '0',
	 PRIMARY KEY	(link_id),
	 KEY `foreign_id` (`foreign_id`),
	 KEY `type` (`type`) )";
	 
$TABLE[] = "CREATE TABLE conv_link_posts (
	 link_id	int(10) unsigned NOT NULL auto_increment,
	 ipb_id		bigint(10) NOT NULL default '0',
	 foreign_id	bigint(10) NOT NULL default '0',
	 type		varchar(32) default NULL,
	 duplicate	tinyint(1) NOT NULL default '0',
	 app		int(10) NOT NULL default '0',
	 PRIMARY KEY	(link_id),
	 KEY `foreign_id` (`foreign_id`),
	 KEY `type` (`type`) )";
	 
$TABLE[] = "CREATE TABLE conv_link_pms (
	 link_id	int(10) unsigned NOT NULL auto_increment,
	 ipb_id		bigint(10) NOT NULL default '0',
	 foreign_id	bigint(10) NOT NULL default '0',
	 type		varchar(32) default NULL,
	 duplicate	tinyint(1) NOT NULL default '0',
	 app		int(10) NOT NULL default '0',
	 PRIMARY KEY	(link_id),
	 KEY `foreign_id` (`foreign_id`),
	 KEY `type` (`type`) )";

$TABLE[] = "CREATE TABLE conv_apps (
	 app_id 	int(10) NOT NULL auto_increment,
	 sw			varchar(32) NOT NULL default '',
	 app_key	varchar(32) NOT NULL default '',
	 name		varchar(255) NOT NULL default '',
	 login		tinyint(1) NOT NULL default '0',
	 parent 	int(10) NOT NULL default '0',
	 db_driver	varchar(32) NOT NULL default '',
	 db_host	varchar(128) NOT NULL default '',
	 db_user	varchar(128) NOT NULL default '',
	 db_pass	varchar(128) NOT NULL default '',
	 db_db		varchar(128) NOT NULL default '',
	 db_prefix	varchar(32) NOT NULL default '',
	 db_charset	varchar(32) NOT NULL default '',
	PRIMARY KEY	(app_id),
	KEY name(name)
)";

?>