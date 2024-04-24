<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2021-2024 Petr Macek                                      |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | https://github.com/xmacan/                                              |
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/


function plugin_evidence_initialize_database() {

	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false);
	$data['columns'][] = array('name' => 'organization', 'type' => 'varchar(200)', 'NULL' => false);
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'evidence organizations';
	api_plugin_db_table_create ('evidence', 'plugin_evidence_organizations', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false,'auto_increment' => true);
	$data['columns'][] = array('name' => 'org_id', 'type' => 'int(11)', 'NULL' => false);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'oid', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'result', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'method', 'type' => 'enum("get","walk","info","table")', 'default' => 'get', 'NULL' => false);
	$data['columns'][] = array('name' => 'table_items', 'type' => 'varchar(100)', 'default' => null, 'NULL' => true);
	$data['columns'][] = array('name' => 'mandatory', 'type' => 'enum("yes","no")', 'default' => 'yes', 'NULL' => false);

	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'evidence steps';
	api_plugin_db_table_create ('evidence', 'plugin_evidence_steps', $data);

	$data = array();
	$data['columns'][] = array('name' => 'host_id', 'type' => 'int(11)', 'NULL' => false);
	$data['columns'][] = array('name' => 'data', 'type' => 'text', 'NULL' => true);
	$data['columns'][] = array('name' => 'last_check', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'host_id';

	$data['type'] = 'InnoDB';
	$data['comment'] = 'evidence history';
	api_plugin_db_table_create ('evidence', 'plugin_evidence_history', $data);

	// vendor specific
	// 3Com/H3C
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (25506,'3Com/H3C/HPE','','','info')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (11,'3Com/H3C/HPE','','','info')");

	// Aruba/HPE
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (14823,'Serial numbers','1.3.6.1.4.1.14823.2.3.3.1.2.1.1.4','.*','walk')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (14823,'version','1.3.6.1.4.1.14823.2.3.3.1.1.4.0','.*','get')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (14823,'hw model','1.3.6.1.4.1.14823.2.3.3.1.2.1.1.6','.*','walk')");
	// Aruba instant AP cluster
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items)
		VALUES (14823,'APs','.1.3.6.1.4.1.14823.2.3.3.1.2.1.1','.*','table','1-mac,2-name,3-ip,4-serial,6-model')");
	// Aruba ap uptime is problem for history - so optional
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items,mandatory)
		VALUES (14823,'APs_uptime','.1.3.6.1.4.1.14823.2.3.3.1.2.1.1','.*','table','1-mac,2-name,9-uptime','no')");

	// Cisco
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items)
		VALUES (9,'switch','.1.3.6.1.4.1.9.9.500.1.2.1.1','.*','table','3-role,4-priority,7-mac,8-swimage')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items)
		VALUES (5771,'switch','.1.3.6.1.4.1.9.9.500.1.2.1.1','.*','table','3-role,4-priority,7-mac,8-swimage')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items)
		VALUES (5842,'switch','.1.3.6.1.4.1.9.9.500.1.2.1.1','.*','table','3-role,4-priority,7-mac,8-swimage')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items)
		VALUES (53683,'switch','.1.3.6.1.4.1.9.9.500.1.2.1.1','.*','table','3-role,4-priority,7-mac,8-swimage')");
	// Cisco - mac on ports
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (9,'Port mac addr','1.3.6.1.4.1.9.9.500.1.2.1.1.7','.*','walk')");
	// Cisco - chassis
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items) VALUES (9,'chassis','.1.3.6.1.4.1.9.5.1.2','.*','table','16-chassis_model,17-chassis_sn,19-chassis_sn_string')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items) VALUES (9,'chassis','.1.3.6.1.4.1.9.3.6','.*','table','1-chassis_type,2-chassis_ver,3-chassis_id,5-chassis_romsysver')");

	// Fortinet
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (12356,'serial','1.3.6.1.4.1.12356.100.1.1.1.0','.*','get')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (12356,'version','1.3.6.1.4.1.12356.101.4.1.1.0','.*','get')");

	// Mikrotik
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (14988,'serial','1.3.6.1.4.1.14988.1.1.7.3.0','.*','get')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (14988,'SW version','1.3.6.1.4.1.14988.1.1.4.4.0','.*','get')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (14988,'Firmware version','1.3.6.1.4.1.14988.1.1.7.4.0','.*','get')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (14988,'SW version','1.3.6.1.4.1.14988.1.1.17.1.1.4.1','.*','get')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (14988,'hw','SNMPv2-SMI::mib-2.47.1.1.1.1.2.65536','([a-zA-Z0-9_-]){1,20}$','get')");

	// QNAP
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method,table_items)
		VALUES (24681,'hw disks','1.3.6.1.4.1.24681.1.3.11.1','.*','table','2-name,5-type,3-temp,7-smart')");

	// Synology
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (8072,'Info - Synology has OrgID 6574, but uses 8072','','','info')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (8072,'serial','SNMPv2-SMI::enterprises.6574.1.5.2.0','.*','get')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (8072,'version','SNMPv2-SMI::enterprises.6574.1.5.3.0','.*','get')");
	db_execute ("INSERT INTO plugin_evidence_steps (org_id,description,oid,result,method)
		VALUES (8072,'hw model','SNMPv2-SMI::enterprises.6574.1.5.1.0','.*','get')");
}


function plugin_evidence_upgrade_database() {
	global $config;

	$info = parse_ini_file($config['base_path'] . '/plugins/evidence/INFO', true);
	$info = $info['info'];

	$current = $info['version'];
	$oldv    = db_fetch_cell('SELECT version FROM plugin_config WHERE directory = "evidence"');

	if (!cacti_version_compare($oldv, $current, '=')) {

		/*
		if (cacti_version_compare($oldv, '0.4', '<=')) {
		}
		*/
	}
}



