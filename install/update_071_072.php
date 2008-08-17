<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/// Update from 0.71 to 0.72
include_once (GLPI_ROOT . "/inc/setup.function.php");
include_once (GLPI_ROOT . "/inc/rulesengine.function.php");

function update071to072() {
	global $DB, $CFG_GLPI, $LANG, $LINK_ID_TABLE;

	// TO TRY for software update
	ini_set("max_execution_time", "0");

	if (!FieldExists("glpi_networking", "recursive")) {
		$query = "ALTER TABLE `glpi_networking` ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`;";
		$DB->query($query) or die("0.72 add recursive in glpi_networking" . $LANG["update"][90] . $DB->error());
	}	  	

	// Clean datetime fields
	$date_fields=array('glpi_docs.date_mod',
			'glpi_event_log.date',
			'glpi_monitors.date_mod',
			'glpi_networking.date_mod',
			'glpi_ocs_link.last_update',
			'glpi_peripherals.date_mod',
			'glpi_phones.date_mod',
			'glpi_printers.date_mod',
			'glpi_reservation_resa.begin',
			'glpi_reservation_resa.end',
			'glpi_tracking.closedate',
			'glpi_tracking_planning.begin',
			'glpi_tracking_planning.end',
			'glpi_users.last_login',
			'glpi_users.date_mod',
	);

	foreach ($date_fields as $tablefield){
		list($table,$field)=explode('.',$tablefield);
		if (FieldExists($table, $field)) {
			$query = "ALTER TABLE `$table` CHANGE `$field` `$field` DATETIME NULL;";
			$DB->query($query) or die("0.72 alter $field in $table" . $LANG["update"][90] . $DB->error());
		}
	}
	$date_fields[]="glpi_computers.date_mod";
	$date_fields[]="glpi_followups.date";
	$date_fields[]="glpi_history.date_mod";
	$date_fields[]="glpi_kbitems.date";
	$date_fields[]="glpi_kbitems.date_mod";
	$date_fields[]="glpi_ocs_config.date_mod";
	$date_fields[]="glpi_ocs_link.last_ocs_update";
	$date_fields[]="glpi_reminder.date";
	$date_fields[]="glpi_reminder.begin";
	$date_fields[]="glpi_reminder.end";
	$date_fields[]="glpi_reminder.date_mod";
	$date_fields[]="glpi_software.date_mod";
	$date_fields[]="glpi_tracking.date";
	$date_fields[]="glpi_tracking.date_mod";
	$date_fields[]="glpi_type_docs.date_mod";

	foreach ($date_fields as $tablefield){
		list($table,$field)=explode('.',$tablefield);
		if (FieldExists($table, $field)) {
			$query = "UPDATE `$table` SET `$field` = NULL WHERE `$field` ='0000-00-00 00:00:00';";
 			$DB->query($query) or die("0.72 update data of $field in $table" . $LANG["update"][90] . $DB->error());
		}
	}

	// Clean date fields
	$date_fields=array('glpi_infocoms.buy_date',
			'glpi_infocoms.use_date',
	);

	foreach ($date_fields as $tablefield){
		list($table,$field)=explode('.',$tablefield);
		if (FieldExists($table, $field)) {
			$query = "ALTER TABLE `$table` CHANGE `$field` `$field` DATE NULL;";
			$DB->query($query) or die("0.72 alter $field in $table" . $LANG["update"][90] . $DB->error());
		}
	}
	$date_fields[]="glpi_cartridges.date_in";
	$date_fields[]="glpi_cartridges.date_use";
	$date_fields[]="glpi_cartridges.date_out";
	$date_fields[]="glpi_consumables.date_in";
	$date_fields[]="glpi_consumables.date_out";
	$date_fields[]="glpi_contracts.begin_date";
	$date_fields[]="glpi_licenses.expire";

	foreach ($date_fields as $tablefield){
		list($table,$field)=explode('.',$tablefield);
		if (FieldExists($table, $field)) {
			$query = "UPDATE `$table` SET `$field` = NULL WHERE `$field` ='0000-00-00';";
 			$DB->query($query) or die("0.72 update data of $field in $table" . $LANG["update"][90] . $DB->error());
		}
	}
	
	// Software Updates
	// Move licenses to versions
	if (!TableExists("glpi_softwareversions") && TableExists('glpi_licenses')) {
		$query = "RENAME TABLE `glpi_licenses`  TO `glpi_softwareversions` ;";
		$DB->query($query) or die("0.72 rename licenses to version" . $LANG["update"][90] . $DB->error());
	}	  	
	if (!FieldExists("glpi_inst_software", "vID")) {
		$query="ALTER TABLE `glpi_inst_software` CHANGE `license` `vID` INT( 11 ) NOT NULL DEFAULT '0';";
		$DB->query($query) or die("0.72 alter inst_software rename license to vID" . $LANG["update"][90] . $DB->error());
	}
	// Create licenses
	if (!TableExists("glpi_softwarelicenses")){
		$query = "CREATE TABLE `glpi_softwarelicenses` (
				`ID` int(15) NOT NULL auto_increment,
				`sID` int(15) NOT NULL default '0',
				`number` int(15) NOT NULL default '0',
				`type` int(15) NOT NULL default '0',
				`name` varchar(255) NULL default NULL,
				`serial` varchar(255) NULL default NULL,
				`buy_version` int(15) NOT NULL default '0',
				`use_version` int(15) NOT NULL default '0',
				`expire` date default NULL,
				`oem_computer` int(11) NOT NULL default '0',
				`comments` text,
				PRIMARY KEY  (`ID`),
				KEY `sID` (`sID`),
				KEY `buy_version` (`buy_version`),
				KEY `use_version` (`use_version`),
				KEY `oem_computer` (`oem_computer`),
				KEY `serial` (`serial`),
				KEY `expire` (`expire`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$DB->query($query) or die("0.72 create glpi_softwarelicenses" . $LANG["update"][90] . $DB->error());

		// Update Infocoms to device_type 9999
		$query="UPDATE `glpi_infocoms` SET device_type=9999 WHERE device_type='".SOFTWARELICENSE_TYPE."';";
		$DB->query($query) or die("0.72 prepare infocoms for update softwares" . $LANG["update"][90] . $DB->error());

		// Foreach software
		$query_softs = " SELECT * FROM glpi_software 
				ORDER BY FK_entities;";

		if ($result_softs = $DB->query($query_softs)){
		  while ($soft = $DB->fetch_assoc($result_softs)){
			// Foreach lics
			$query_versions="SELECT glpi_softwareversions.*, glpi_infocoms.ID AS infocomID FROM glpi_softwareversions 
					LEFT JOIN glpi_infocoms ON (glpi_infocoms.device_type=9999 AND glpi_infocoms.FK_device=glpi_softwareversions.ID)
					WHERE sID=".$soft['ID']." 
					ORDER BY ID;";
			if ($result_vers = $DB->query($query_versions)){
				while ($vers = $DB->fetch_assoc($result_vers)){
					$install_count=0;
					$vers_ID=$vers['ID'];

					// init : count installations
					$query_count="SELECT COUNT(*) FROM glpi_inst_software WHERE vID=".$vers['ID'].";";
					if ($result_count=$DB->query($query_count)){
						$install_count=$DB->result($result_count,0,0);
					}

					// 1 - Is version already exists ?
					$query_search_version="SELECT * FROM glpi_softwareversions 
								WHERE sID=".$soft['ID']." 
									AND version='".$vers['version']."'
									AND ID < ".$vers['ID'].";";
					if ($result_searchvers = $DB->query($query_search_version)){
						// Version already exists : update inst_software
						if ($DB->numrows($result_searchvers)==1){
							$found_vers=$DB->fetch_assoc($result_searchvers);
							$vers_ID=$found_vers['ID'];
							
							$query="UPDATE glpi_inst_software 
								SET vID = ".$found_vers['ID']." 
								WHERE vID = ".$vers['ID'].";";
							$DB->query($query);
							
							$query="DELETE FROM glpi_softwareversions WHERE ID=".$vers['ID'];
							$DB->query($query);
						}
					}
					// 2 - Create glpi_licenses
					if ($vers['buy'] // Buy license
					|| (!empty($vers['serial'])&&!in_array($vers['serial'],array('free','global'))) // Non global / free serial
					|| !empty($vers['comments'])  // With comments
					|| !empty($vers['expire']) // with an expire date
					|| $vers['oem_computer'] > 0 // oem license
					|| !empty($vers['infocomID']) // with and infocoms
					){
						$found_lic=-1;
						// No infocoms try to find already exists license
						if (empty($vers['infocomID'])){
							$query_search_lic="SELECT ID 
								FROM  glpi_softwarelicenses 
								WHERE buy_version = $vers_ID
									AND serial = '".$vers['serial']."'
									AND oem_computer = '".$vers['oem_computer']."'
									AND comments = '".$vers['comments']."'
								";
							if (empty($vers['expire'])) {
								$query .= " AND expire IS NULL";
							} else {
								$query .= " AND expire = '".$vers['expire']."'";
							}
							if ($result_searchlic = $DB->query($query_search_lic)){
								if ($DB->numrows($result_searchlic)>0){
									$found_lic=$DB->result($result_searchlic,0,0);
								}
							}

						}
						if ($install_count==0){
							$install_count=1; // license exists so count 1 	
						}

						// Found license : merge with found one
						if ($found_lic>0){
							$query="UPDATE `glpi_softwarelicenses`
								SET `number` = number+1 
								WHERE ID=$found_lic";
							$DB->query($query);
						} else { // Create new license
							if (empty($vers['expire'])){
								$vers['expire']='NULL';
							} else {
								$vers['expire']="'".$vers['expire']."'";
							}
							$query="INSERT INTO `glpi_softwarelicenses` 
							(`sID` ,`number` ,`type` ,`name` ,`serial` ,`buy_version`, `use_version`, `expire`, `oem_computer` ,`comments`)
							VALUES 
							(".$soft['ID']." , $install_count, 0, '".$vers['serial']."', '".$vers['serial']."' , $vers_ID, $vers_ID, ".$vers['expire'].", '".$vers['oem_computer']."', '".$vers['comments']."');";
							
							if ($DB->query($query)) {
								$lic_ID=$DB->insert_id();
								// Update infocoms link
								if (!empty($vers['infocomID'])){
									$query="UPDATE glpi_infocoms 
										SET device_type=".SOFTWARELICENSE_TYPE.", FK_device=$lic_ID
										WHERE device_type=9999 AND FK_device=".$vers['ID'].";";
									$DB->query($query);
								}
							}
						}
						
					} // Create licence

					$DB->free_result($result_searchvers);

				} // Each liv
				$DB->free_result($result_vers);
			}
		  }
		}
	} // TableExists("glpi_softwarelicenses")
	
	// ALTER softwareversions
	if (FieldExists("glpi_softwareversions", "buy")) {
		$query="ALTER TABLE `glpi_softwareversions` DROP `serial`, DROP `expire`, DROP `oem`, DROP `oem_computer`, DROP `buy`, DROP `comments`;";
		$DB->query($query) or die("0.72 alter clean softwareversion table" . $LANG["update"][90] . $DB->error());
	}	
	if (FieldExists("glpi_softwareversions", "version")) {
		$query=" ALTER TABLE `glpi_softwareversions` CHANGE `version` `name` VARCHAR( 255 ) NULL DEFAULT NULL  ";
		$DB->query($query) or die("0.72 alter version to name in softwareversion table" . $LANG["update"][90] . $DB->error());
	}	
	if (!FieldExists("glpi_softwareversions", "comments")) {
		$query="ALTER TABLE `glpi_softwareversions` ADD `comments` TEXT NULL ;";
		$DB->query($query) or die("0.72 add comments to softwareversion table" . $LANG["update"][90] . $DB->error());
	}	

	if (!TableExists("glpi_dropdown_licensetypes")) {
		$query="CREATE TABLE `glpi_dropdown_licensetypes` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) collate utf8_unicode_ci default NULL,
			`comments` text collate utf8_unicode_ci,
			PRIMARY KEY  (`ID`),
			KEY `name` (`name`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$DB->query($query) or die("0.72 create glpi_dropdown_licensetypes table" . $LANG["update"][90] . $DB->error());
	}	

	if (!FieldExists("glpi_groups", "recursive")) {
		$query = "ALTER TABLE `glpi_groups` ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`;";
		$DB->query($query) or die("0.72 add recursive in glpi_groups" . $LANG["update"][90] . $DB->error());
	}	  	

	if (!FieldExists("glpi_auth_ldap", "ldap_field_title")) {
		$query = "ALTER TABLE `glpi_auth_ldap` ADD `ldap_field_title` VARCHAR( 255 ) NOT NULL ;";
		$DB->query($query) or die("0.72 add ldap_field_title in glpi_auth_ldap" . $LANG["update"][90] . $DB->error());
	}	  	

	//Add user title retrieval from LDAP 
	if (!TableExists("glpi_dropdown_user_titles")) {
		$query="CREATE TABLE `glpi_dropdown_user_titles` (
		`ID` int( 11 ) NOT NULL AUTO_INCREMENT ,
		`name` varchar( 255 ) COLLATE utf8_unicode_ci default NULL ,
		`comments` text COLLATE utf8_unicode_ci,
		PRIMARY KEY ( `ID` ) ,
		KEY `name` ( `name` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;";
		$DB->query($query) or die("0.72 create glpi_dropdown_user_titles table" . $LANG["update"][90] . $DB->error());
	}	

	if (!FieldExists("glpi_users", "title")) {
		$query = "ALTER TABLE `glpi_users` ADD `title` INT( 11 ) NOT NULL DEFAULT '0';";
		$DB->query($query) or die("0.72 add title in glpi_users" . $LANG["update"][90] . $DB->error());
	}	  	

	if (!isIndex("glpi_users", "title")) {
		$query = " ALTER TABLE `glpi_users` ADD INDEX `title` ( `title` ) ;";
		$DB->query($query) or die("0.72 add index on title in glpi_users" . $LANG["update"][90] . $DB->error());
	}	  	

	if (!FieldExists("glpi_auth_ldap", "ldap_field_type"))
	{ 
		$query = "ALTER TABLE `glpi_auth_ldap` ADD `ldap_field_type` VARCHAR( 255 ) NOT NULL ;";
		$DB->query($query) or die("0.72 add ldap_field_title in glpi_auth_ldap" . $LANG["update"][90] . $DB->error());
	}	  	
	
	//Add title criteria
	$result  = $DB->query("SELECT count(*) as cpt FROM glpi_rules_ldap_parameters WHERE value='title' AND rule_type=".RULE_AFFECT_RIGHTS);
	if (!$DB->result($result,0,"cpt"))
		$DB->query("INSERT INTO `glpi_rules_ldap_parameters` (`ID` ,`name` ,`value` ,`rule_type`) VALUES (NULL , '(LDAP) Title', 'title', '1');");

	//Add user type retrieval from LDAP 
	if (!TableExists("glpi_dropdown_user_types")) {
		$query="CREATE TABLE `glpi_dropdown_user_types` (
		`ID` int( 11 ) NOT NULL AUTO_INCREMENT ,
		`name` varchar( 255 ) COLLATE utf8_unicode_ci default NULL ,
		`comments` text COLLATE utf8_unicode_ci,
		PRIMARY KEY ( `ID` ) ,
		KEY `name` ( `name` )
		) ENGINE = MYISAM DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;";
		$DB->query($query) or die("0.72 create glpi_dropdown_user_types table" . $LANG["update"][90] . $DB->error());
	}	

	if (!FieldExists("glpi_users", "type")) {
		$query = "ALTER TABLE `glpi_users` ADD `type` INT( 11 ) NOT NULL DEFAULT '0';";
		$DB->query($query) or die("0.72 add type in glpi_users" . $LANG["update"][90] . $DB->error());
	}	  	

	if (!isIndex("glpi_users", "type")) {
		$query = " ALTER TABLE `glpi_users` ADD INDEX `type` ( `type` ) ;";
		$DB->query($query) or die("0.72 add index on type in glpi_users" . $LANG["update"][90] . $DB->error());
	}	  	

	if (!isIndex("glpi_users", "active")) {
		$query = " ALTER TABLE `glpi_users` ADD INDEX `active` ( `active` ) ;";
		$DB->query($query) or die("0.72 add index on active in glpi_users" . $LANG["update"][90] . $DB->error());
	}	  	

	if (!FieldExists("glpi_auth_ldap", "ldap_field_language")){ 
		$query = "ALTER TABLE `glpi_auth_ldap` ADD `ldap_field_language` VARCHAR( 255 ) NOT NULL ;";
		$DB->query($query) or die("0.72 add ldap_field_language in glpi_auth_ldap" . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_ocs_config", "tag_exclude")){ 
		$query = "ALTER TABLE `glpi_ocs_config` ADD `tag_exclude` VARCHAR( 255 ) NULL AFTER `tag_limit` ;";
		$DB->query($query) or die("0.72 add tag_exclude in glpi_ocs_config" . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_config", "cache_max_size")){ 
		$query = "ALTER TABLE `glpi_config` ADD `cache_max_size` INT( 11 ) NOT NULL DEFAULT '20' AFTER `use_cache` ;";
		$DB->query($query) or die("0.72 add cache_max_size in glpi_config" . $LANG["update"][90] . $DB->error());
	}

	if (!TableExists("glpi_dropdown_filesystems")) {
		$query="CREATE TABLE `glpi_dropdown_filesystems` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) collate utf8_unicode_ci default NULL,
			`comments` text collate utf8_unicode_ci,
			PRIMARY KEY  (`ID`),
			KEY `name` (`name`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$DB->query($query) or die("0.72 create glpi_dropdown_filesystems table" . $LANG["update"][90] . $DB->error());

		$fstype=array('ext','ext2','ext3','ext4','FAT','FAT32','VFAT','HFS','HPFS','HTFS','JFS','JFS2','NFS','NTFS','ReiserFS','SMBFS','UDF','UFS','XFS','ZFS');
		foreach ($fstype as $fs){
			$query= "INSERT INTO `glpi_dropdown_filesystems` (name) VALUES ('$fs');";
			$DB->query($query) or die("0.72 add filesystems type " . $LANG["update"][90] . $DB->error());
		}
	}	

	if (!TableExists("glpi_computerdisks")) {
		$query="CREATE TABLE `glpi_computerdisks` (
			`ID` int(11) NOT NULL auto_increment,
			`FK_computers` int(11) NOT NULL default 0,
			`name` varchar(255) collate utf8_unicode_ci default NULL,
			`device` varchar(255) collate utf8_unicode_ci default NULL,
			`mountpoint` varchar(255) collate utf8_unicode_ci default NULL,
			`FK_filesystems` int(11) NOT NULL default 0,
			`totalsize` int(11) NOT NULL default 0,
			`freesize` int(11) NOT NULL default 0,
			PRIMARY KEY  (`ID`),
			KEY `name` (`name`),
			KEY `FK_filesystems` (`FK_filesystems`),
			KEY `FK_computers` (`FK_computers`),
			KEY `device` (`device`),
			KEY `mountpoint` (`mountpoint`),
			KEY `totalsize` (`totalsize`),
			KEY `freesize` (`freesize`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$DB->query($query) or die("0.72 create glpi_computerfilesystems table" . $LANG["update"][90] . $DB->error());
	}	


	if (!FieldExists("glpi_ocs_config", "import_disk")){ 
		$query = "ALTER TABLE `glpi_ocs_config` ADD `import_disk` INT( 2 ) NOT NULL DEFAULT '0' AFTER `import_ip` ;";
		$DB->query($query) or die("0.72 add import_disk in glpi_ocs_config" . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_ocs_link", "import_disk")){ 
		$query = "ALTER TABLE `glpi_ocs_link` ADD `import_disk` LONGTEXT NULL AFTER `import_device` ;";
		$DB->query($query) or die("0.72 add import_device in glpi_ocs_link" . $LANG["update"][90] . $DB->error());
	}
	
	// Clean software ocs 
	if (FieldExists("glpi_ocs_config", "import_software_buy")){ 
		$query = " ALTER TABLE `glpi_ocs_config` DROP `import_software_buy` ;";
		$DB->query($query) or die("0.72 drop import_software_buy in glpi_ocs_config" . $LANG["update"][90] . $DB->error());
	}
	if (FieldExists("glpi_ocs_config", "import_software_licensetype")){ 
		$query = " ALTER TABLE `glpi_ocs_config` DROP `import_software_licensetype` ;";
		$DB->query($query) or die("0.72 drop import_software_licensetype in glpi_ocs_config" . $LANG["update"][90] . $DB->error());
	}

	//// Clean interface use for GFX card
	// Insert default values
	externalImportDropdown("glpi_dropdown_interface", "AGP");
	externalImportDropdown("glpi_dropdown_interface", "PCI");
	externalImportDropdown("glpi_dropdown_interface", "PCIe");
	externalImportDropdown("glpi_dropdown_interface", "PCI-X");	

	if (!FieldExists("glpi_device_gfxcard", "FK_interface")) {

		$query = "ALTER TABLE `glpi_device_gfxcard` ADD `FK_interface` INT NOT NULL DEFAULT '0' AFTER `interface` ";
		$DB->query($query) or die("0.72 alter glpi_device_gfxcard add new field interface " . $LANG["update"][90] . $DB->error());

		// Get all data from interface_old / Insert in glpi_dropdown_interface if needed
		$query="SELECT DISTINCT interface AS OLDNAME FROM glpi_device_gfxcard;";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_assoc($result)){
					$data = addslashes_deep($data);
					$newID=externalImportDropdown("glpi_dropdown_interface", $data['OLDNAME']);

					// Update datas
					$query2="UPDATE glpi_device_gfxcard SET FK_interface='$newID' WHERE interface='".$data['OLDNAME']."'";
					$DB->query($query2) or die("0.72 update glpi_device_gfxcard set new interface value " . $LANG["update"][90] . $DB->error());
				}
			}
		}
		
		$query = "ALTER TABLE `glpi_device_gfxcard` DROP `interface` ";
		$DB->query($query) or die("0.7 alter $table drop tmp enum field " . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_config","existing_auth_server_field_clean_domain")) {
		$query = "ALTER TABLE `glpi_config` ADD `existing_auth_server_field_clean_domain` SMALLINT NOT NULL DEFAULT '0' AFTER `existing_auth_server_field`;";

		$DB->query($query) or die("0.71 alter config add existing_auth_server_field_clean_domain " . $LANG["update"][90] . $DB->error());
	}

	if (FieldExists("glpi_profiles","contract_infocom")){
		$query = "ALTER TABLE `glpi_profiles` CHANGE `contract_infocom` `contract` CHAR( 1 ) NULL DEFAULT NULL ;";
		$DB->query($query) or die("0.71 alter profiles rename contract_infocom to contract " . $LANG["update"][90] . $DB->error());

		$query = "ALTER TABLE `glpi_profiles` ADD `infocom` CHAR( 1 ) NULL DEFAULT NULL AFTER `contract` ;";
		$DB->query($query) or die("0.71 alter profiles create infocom " . $LANG["update"][90] . $DB->error());

		$query = "UPDATE glpi_profiles SET `infocom`=`contract`;";
		$DB->query($query) or die("0.71 update data for infocom in profiles " . $LANG["update"][90] . $DB->error());
	}


		 



} // fin 0.72 #####################################################################################
?>
