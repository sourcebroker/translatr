#
# Table structure for table 'tx_translatr_domain_model_label'
#
CREATE TABLE tx_translatr_domain_model_label (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	extension varchar(255) DEFAULT '' NOT NULL,
	ukey varchar(255) DEFAULT '' NOT NULL,
	text text NOT NULL,
	description text NOT NULL,
	ll_file varchar(255) DEFAULT '' NOT NULL,
	language varchar(31) DEFAULT '' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	UNIQUE KEY ukey (ukey,ll_file,language),
	KEY parent (pid),
);
