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


function plugin_evidence_poller_bottom() {
	global $config;

	include_once($config['library_path'] . '/poller.php');

	$command_string = trim(read_config_option('path_php_binary'));

	if (trim($command_string) == '') {
		$command_string = 'php';
	}

	$extra_args = ' -q ' . $config['base_path'] . '/plugins/evidence/poller_evidence.php --host-id=all';

	exec_background($command_string, $extra_args);
}



function plugin_evidence_device_remove($device_id) {

	db_execute_prepared('DELETE FROM plugin_evidence_history WHERE host_id = ?', array($device_id));
}




function evidence_show_tab () {
	global $config;

	if (api_user_realm_auth('evidence.php')) {
		$cp = false;
		if (basename($_SERVER['PHP_SELF']) == 'evidence.php') {
			$cp = true;
		}

		print '<a href="' . $config['url_path'] . 'plugins/evidence/evidence_tab.php"><img src="' . $config['url_path'] . 'plugins/evidence/images/tab_evidence' . ($cp ? '_down': '') . '.gif" alt="evidence" align="absmiddle" border="0"></a>';
	}
}







function plugin_evidence_device_edit_top_links (){
	print "<br/><span class='linkMarker'>* </span><a id='evidence_info' data-evidence_id='" . get_request_var('id') . "' href=''>" . __('evidence') . "</a>";
}


function plugin_evidence_host_edit_bottom () {
	global $config;
	print get_md5_include_js($config['base_path'] . '/plugins/evidence/evidence.js');
}

function evidence_import_enterprise_numbers() {
	global $config;

	$i = 0;

	$file = fopen($config['base_path'] . '/plugins/evidence/data/enterprise-numbers.sql','r');

	if ($file) {

		while(!feof($file)) {
			$line = fgets($file);
			db_execute($line);
			$i++;
		}
	} else {
		return false;
	}

	fclose ($file);

	return $i;

}

function plugin_evidence_get_allowed_devices($user_id, $array = false) {

	$x  = 0;
	$us = read_user_setting('hide_disabled', false, false, $user_id);

	if ($us == 'on') {
		set_user_setting('hide_disabled', '', $user_id);
	}

	$allowed = get_allowed_devices('', 'null', -1, $x, $user_id);

	if ($us == 'on') {
		set_user_setting('hide_disabled', 'on', $user_id);
	}

	if (cacti_count($allowed)) {
		if ($array) {
			return(array_column($allowed, 'id'));
		}
		return implode(',', array_column($allowed, 'id'));
	} else {
		return false;
	}
}

function plugin_evidence_find_organization ($h) {

	cacti_oid_numeric_format();

	$sys_object_id = @cacti_snmp_get($h['hostname'], $h['snmp_community'],
		'.1.3.6.1.2.1.1.2.0', $h['snmp_version'],
		$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
		$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
		$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout'],0);

	if (!isset($sys_object_id) || $sys_object_id == 'U') {
		return false;
	}

	preg_match('/^([a-zA-Z0-9\.: ]+)\.1\.3\.6\.1\.4\.1\.([0-9]+)[a-zA-Z0-9\. ]*$/', $sys_object_id, $match);
	return $match[2];
}

function plugin_evidence_get_data($h) {
	global $config;

	$return = array(
		'result' => true,
		'error'  => '',
		'data'   => array()
	);

//!! nemel bych tady hlidat i ID, jestli ma prava?

	/* for requests from gui */
	if ($h['snmp_version'] == 0){
		$return['result'] = false;
		$return['error'] = 'No snmp version';
		return $return;
	}

	// gathering data from entity mib
	$indexes = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.1',
		$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], 
		$h['snmp_auth_protocol'], $h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], 
		$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);

	if (cacti_sizeof($indexes) > 0) {

		// index uz mam
		$data_descr = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.2', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'],$h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_name = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.7', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_hardware_rev = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.8', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_firmware_rev = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.9', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_software_rev = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.10', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_serial_num = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.11', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_mfg_name = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.12', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_model_name = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.13', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_alias = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.14', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_asset_id = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.15', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_mfg_date = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.17', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		$data_uuid = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.19', 
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'], 
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'], 
			$h['snmp_port'], $h['snmp_timeout']);

		foreach ($indexes as $key => $val) {
			$date = '';

			if (isset($data_mfg_date[$key])) {
				$data_mfg_date[$key]['value'] = str_replace(' ' ,'', $data_mfg_date[$key]['value']);
				$man_year = hexdec(substr($data_mfg_date[$key]['value'], 0, 4));
				$man_month = str_pad(hexdec(substr($data_mfg_date[$key]['value'], 4, 2)), 2, '0', STR_PAD_LEFT);
				$man_day = str_pad(hexdec(substr($data_mfg_date[$key]['value'], 6, 2)), 2, '0', STR_PAD_LEFT);
				if ($man_year != 0) {
					$date = $man_year . '-' . $man_month . '-' . $man_day;
				}
			}

			$entity[$val['value']] = array (
				isset($data_descr[$key]['value']) ? $data_descr[$key]['value'] : '',
				isset($data_name[$key]['value']) ? $data_name[$key]['value'] : '',
				isset($data_hardware_rev[$key]['value']) ? $data_hardware_rev[$key]['value'] : '',
				isset($data_firmware_rev[$key]['value']) ? $data_firmware_rev[$key]['value'] : '',
				isset($data_software_rev[$key]['value']) ? $data_software_rev[$key]['value'] : '',
				isset($data_serial_num[$key]['value']) ? $data_serial_num[$key]['value'] : '',
				isset($data_mfg_name[$key]['value']) ? $data_mfg_name[$key]['value'] : '',
				isset($data_model_name[$key]['value']) ? $data_model_name[$key]['value'] : '',
				isset($data_alias[$key]['value']) ? $data_alias[$key]['value'] : '',
				isset($data_asset_id[$key]['value']) ? $data_asset_id[$key]['value'] : '',
				$date,
				isset($data_uuid[$key]['value']) ? $data_uuid[$key]['value'] : ''
				);
		}


	$return['data'] = $entity;
	}

	return $return;
}



function plugin_evidence_get_mac ($h) {

	$return = array();

	$macs = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.2.2.1.6', 
		$h['snmp_version'],$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
		$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],$h['snmp_context'], 
		$h['snmp_port'], $h['snmp_timeout']);

	foreach ($macs as $mac) {
		if (strlen($mac['value']) > 1) {
			$mac = plugin_evidence_normalize_mac($mac['value']);
			if (!in_array($mac, $return)) {
				$return[] = $mac;
			}
		}
	}

	return $return;
}

function plugin_evidence_normalize_mac ($mac_address) {

        $mac_address = trim($mac_address);

        if (strlen($mac_address) > 10) {
                $max_address = str_replace(array('"', ' ', '-'), array('',  ':', ':'), $mac_address);
        } else { /* return is hex */
                $mac = '';

                for ($j = 0; $j < strlen($mac_address); $j++) {
                        $mac .= bin2hex($mac_address[$j]) . ':';
                }

                $mac_address = $mac;
        }

        return strtoupper($mac_address);
}



function plugin_evidence_get_data_specific ($h) {

//	include_once($config['library_path'] . '/snmp.php');

	$return = array(
		'result' => true,
		'error'  => '',
		'data'   => array()
	);

	$steps = db_fetch_assoc_prepared ('SELECT * FROM plugin_evidence_steps 
		WHERE org_id = ? AND mandatory = "yes" ORDER BY method',
		array($org_id));
	foreach ($steps as $step) {
		if (cacti_sizeof($step)) {
			if ($step['method'] == 'info') {
				$out .= 'Info: ' . $step['description'] . '<br/>';
			}
			if ($step['method'] == 'get') {
				$data = @cacti_snmp_get($h['hostname'], $h['snmp_community'],
					$step['oid'], $h['snmp_version'],
					$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
					$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
					$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);

				if (preg_match ('#' . $step['result'] . '#', $data, $matches) !== false) {
					$out .= ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
				} else {
					$out .= ucfirst($step['description']) . ': ' . $data . ' (cannot find specified regexp, so display all)<br/>';
				}
			}
			if ($step['method'] == 'walk') {
				$data = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],
						$step['oid'], $h['snmp_version'],
						$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
						$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
						$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);

				if (cacti_sizeof($data) > 0) {
					foreach ($data as $row) {
						if (preg_match ('#' . $step['result'] . '#', $row['value'], $matches) !== false) {
							if (strlen($matches[0]) > 0) {
								$out .= ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
							}
						} else {
							$out .= ucfirst($step['description']) . ': ' . $row['value'] . ' (cannot find specified regexp, so display all)<br/>';
						}
					}
				} else {
					$out .= "I don't know, how to get the information about " . $step['description'] . "<br/>";
				}
			}
			if ($step['method'] == 'table') {
				$ind_des = explode (',', $step['table_items']);
				foreach ($ind_des as $a) {
					list ($i,$d) = explode ('-', $a);
					$oid_suff[] = $i;
					$desc[] = $d;
				} 
				
				$out .= '<table class="cactiTable"><tr>';
				foreach ($desc as $d) {
					$out .= '<th>' . $d . ' </th>';
				}
				
				$out .= '</tr>';

				foreach ($oid_suff as $i) {

					$data[$i] = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],
						$step['oid'] . '.' . $i, $h['snmp_version'],
						$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
						$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
						$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);
					$last = $i;
				}

				// display columns as rows only
				for ($f = 0; $f < count($data[$last]);$f++) {
					$out .= "<tr>";

					foreach ($oid_suff as $i) {
						$out .= "<td>" . $data[$i][$f]['value'] . " </td>";
					}
					$out .= "</tr>";
				}

				$out .= '</table>';
			}
		} else {
			$out .= "I don't know, how to get the information about device<br/>";
		}
	}

	$out .= '<br/><br/>';

	return ($out);

}



function plugin_evidence_get_history($host_id) {

	$out = array();

	$data_his = db_fetch_assoc_prepared ('SELECT host_id,last_check, data FROM plugin_evidence_history
		WHERE host_id = ? ORDER BY last_check DESC', array($host_id));

	if (cacti_sizeof($data_his)) {
		foreach ($data_his as $row) {
			$out[$row['last_check']] = stripslashes($row['data']);
		}
	}

	return ($out);
}


function plugin_evidence_find() {

	if (read_config_option('snver_records') == 0) {
		print 'Store history is not allowed. Nothing to do ...';
		return false;
	}

	$find = get_filter_request_var('find', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z0-9_\-\.:]{3,})$/')));
	if (strlen($find) < 3) {
		print 'At least 3 chars...';
		return false;
	}

	$data = db_fetch_assoc ('SELECT id,description,data,last_check FROM host 
		LEFT JOIN plugin_evidence_history ON host.id = plugin_evidence_history.host_id 
		WHERE plugin_evidence_history.data LIKE "%' . $find . '%"');

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			print '<b>Host ' . $row['description'] . '(ID: ' . $row['id'] . ')<br/>';
			print 'Date ' . $row['last_check'] . '</b><br/>';
			print $row['data'] . '<br/><br/>';	
		}
	} else {
		print 'Not found';
	}
}


function plugin_evidence_get_info_optional($host_id) {
	global $config;

	include_once('./lib/snmp.php');

	$out = '';

	$host = db_fetch_row_prepared ('SELECT * FROM host WHERE id = ?', array($host_id));

	if (!$host) {
		return false;
	}

	if ($host['availability_method'] == 0 || $host['availability_method'] == 3) {
		//return ('No SNMP availability method');
		return false;
	}

	if (function_exists('snmp_set_oid_output_format')) {
		snmp_set_oid_output_format (SNMP_OID_OUTPUT_NUMERIC);
	}

	// find organization

	cacti_oid_numeric_format();

	$string = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
                '.1.3.6.1.2.1.1.2.0', $host['snmp_version'],
                $host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
                $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
                $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout'],1);

	if ($string == 'U') {  //!!! resit, at vracim i duvod a to i u zakladniho infa
		//return ('Cannot determine sysObjectID, is snmp configured correctly? Maybe host down');
		return false;
	} elseif (!$string) {
		return false;
	}

	preg_match('/^([a-zA-Z0-9\.: ]+)\.1\.3\.6\.1\.4\.1\.([0-9]+)[a-zA-Z0-9\. ]*$/',$string, $match);
	$id_org = $match[2]; 

	$out .= '<b>Vendor specific optional (not saved in history):</b><br/>';

	$steps = db_fetch_assoc_prepared ('SELECT * FROM plugin_evidence_steps WHERE org_id = ? AND mandatory="no" ORDER BY method',
		array($id_org));

	foreach ($steps as $step) {
		if (cacti_sizeof($step)) {
			if ($step['method'] == 'info') {
				$out .= 'Info: ' . $step['description'] . '<br/>';
			}
			elseif ($step['method'] == 'get') {
				$data = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
					$step['oid'], $host['snmp_version'],
					$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
					$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
					$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (preg_match ('#' . $step['result'] . '#', $data, $matches) !== false) {
					$out .= ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
				} else {
					$out .= ucfirst($step['description']) . ': ' . $data . ' (cannot find specified regexp, so display all)<br/>';
				}
			} elseif ($step['method'] == 'walk') {
				$data = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],
						$step['oid'], $host['snmp_version'],
						$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
						$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
						$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (cacti_sizeof($data) > 0) {
					foreach ($data as $row) {
						if (preg_match ('#' . $step['result'] . '#', $row['value'], $matches) !== false) {
							if (strlen($matches[0]) > 0) {
								$out .= ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
							}
						} else {
							$out .= ucfirst($step['description']) . ': ' . $row['value'] . ' (cannot find specified regexp, so display all)<br/>';
						}
					}
				} else {
					$out .= "I don't know, how to get the information about " . $step['description'] . "<br/>";
				}
			} elseif ($step['method'] == 'table') {
				$ind_des = explode (',', $step['table_items']);
				foreach ($ind_des as $a) {
					list ($i,$d) = explode ('-', $a);
					$oid_suff[] = $i;
					$desc[] = $d;
				}

				$out .= '<table class="cactiTable"><tr>';
				foreach ($desc as $d) {
					$out .= '<th>' . $d . ' </th>';
				}

				$out .= '</tr>';

				foreach ($oid_suff as $i) {

					$data[$i] = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],
						$step['oid'] . '.' . $i, $host['snmp_version'],
						$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
						$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
						$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);
					$last = $i;
				}

				// display columns as rows only
				for ($f = 0; $f < count($data[$last]);$f++) {
					$out .= "<tr>";

					foreach ($oid_suff as $i) {
						$out .= "<td>" . $data[$i][$f]['value'] . " </td>";
					}
					$out .= "</tr>";
				}

				$out .= '</table>';
			}
		} else {
			$out .= "I don't know, how to get the information about device<br/>";
		}
	}

	$out .= '<br/><br/>';

	return ($out);
}
