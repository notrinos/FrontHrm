<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương                                      |
|   Date :   09-07-2017                                  |
|   Description: Frontaccounting Payroll & Hrm Module    |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

function db_has_overtime() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."overtime");
}

//--------------------------------------------------------------------------

function write_overtime($id = false, $name, $rate) {

	if(!$id) {
		$sql = "INSERT INTO ".TB_PREF."overtime(overtime_name, overtime_rate) VALUES (".db_escape($name).", ".db_escape($rate).")";
	}
	else {
		$sql = "UPDATE ".TB_PREF."overtime SET 
		overtime_name = ".db_escape($name).", overtime_rate = ".db_escape($rate)." WHERE overtime_id = ".db_escape($id);
	}
	
	db_query($sql, 'Could not write overtime data');
}

//--------------------------------------------------------------------------

function get_overtime($id = false, $all = false) {
	
	$sql = "SELECT * FROM ".TB_PREF."overtime";
	$where = array();
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "overtime_id=".db_escape($id);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	else
		$sql .= ' WHERE 1';

	$result = db_query($sql, "Could not get overtime data.");
	
	if($id)
		return db_fetch($result);
	else
		return $sql;
}

//--------------------------------------------------------------------------

function delete_overtime($id) {
	$sql = "DELETE FROM ".TB_PREF."overtime WHERE overtime_id = ".db_escape($id);
	db_query($sql, 'Could not delete overtime.');
}

//--------------------------------------------------------------------------

function overtime_used($id) {
	return false;
}