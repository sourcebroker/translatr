CREATE TABLE tx_translatr_domain_model_label
(
	modify        tinyint(4) DEFAULT '0' NOT NULL,

	extension     varchar(255) DEFAULT '' NOT NULL,
	ukey          varchar(255) DEFAULT '' NOT NULL,
	text          text                    NOT NULL,
	description   text                    NOT NULL,
	tags          text                    NOT NULL,
	ll_file       varchar(255) DEFAULT '' NOT NULL,
	ll_file_index varchar(100) DEFAULT '' NOT NULL,
	language      varchar(31)  DEFAULT '' NOT NULL,

	UNIQUE KEY ukey (ukey,language,ll_file_index),
);
