<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
 |                                                                         |
 | Licensed under the Apache License, Version 2.0 (the "License");         |
 | you may not use this file except in compliance with the License.        |
 | You may obtain a copy of the License at                                 |
 |                                                                         |
 | http://www.apache.org/licenses/LICENSE-2.0                              |
 |                                                                         |
 | Unless required by applicable law or agreed to in writing, software     |
 | distributed under the License is distributed on an "AS IS" BASIS,       |
 | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.|
 | See the License for the specific language governing permissions and     |
 | limitations under the License.                                          |
 +-------------------------------------------------------------------------+
*/

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_disku_groups', $_SERVER['argv']);
}

function ss_disku_groups($cmd = 'index', $arg1 = '', $arg2 = '') {

	if ($cmd == 'index') {
		$return_arr = ss_disku_groups_getnames();

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_disku_groups_getnames();
		$arr = ss_disku_groups_getinfo($arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_disku_groups_getvalue($index, $arg);
	}
}

function ss_disku_groups_getvalue($primaryKey, $column) {
	$return_arr = array();

	if ($column != 'users') {
		if ($column == 'size') {
			$column = 'SUM(size)';
		} elseif ($column == 'files') {
			$column = 'SUM(files)';
		} elseif ($column == 'dirs') {
			$column = 'SUM(directories)';
		}

		$arr = db_fetch_cell_prepared("SELECT
			$column
			FROM disku_groups_totals
			WHERE delme = 0
			AND groupid = ?",
			array($primaryKey));
	} else {
		$arr = db_fetch_cell_prepared("SELECT COUNT(DISTINCT et.userid)
			FROM disku_groups_members AS gm
			INNER JOIN disku_extension_totals AS et
			ON gm.userid = et.userid
			WHERE et.delme = 0
			AND gm.groupid = ?",
			array($primaryKey));
	}

	if ($arr == '') {
		$arr = 0;
	}

	return $arr;
}

function ss_disku_groups_getnames() {
	$return_arr = array();

	$arr = db_fetch_assoc('SELECT DISTINCT groupid AS primaryKey
		FROM disku_groups_totals
		ORDER BY primaryKey');

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['primaryKey'];
	}

	return $return_arr;
}

function ss_disku_groups_getinfo($info_requested) {
	$return_arr = array();

	if ($info_requested == 'groupid') {
		$arr = db_fetch_assoc('SELECT DISTINCT
			groupid AS qry_index,
			groupid AS qry_value
			FROM disku_groups_totals
			ORDER BY qry_index');
	} elseif ($info_requested == 'groupName') {
		$arr = db_fetch_assoc('SELECT DISTINCT
			groupid AS qry_index,
			`group` AS qry_value
			FROM disku_groups_totals
			ORDER BY qry_index');
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = trim(addslashes($arr[$i]['qry_value']));
	}

	return $return_arr;
}

