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
	return check_empty_result("SELECT overtime_id FROM ".TB_PREF."attendance WHERE overtime_id = ".db_escape($id));
}

//--------------------------------------------------------------------------

function get_workdays_no($from, $to, $emp_id) {
	$sql = "SELECT hours_no FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp_id)." AND overtime_id = 0 AND att_date BETWEEN '".$from."' AND '".$to."'";

	$result = db_query($sql, 'Could not get attendance');

    return db_num_rows($result);
}

//--------------------------------------------------------------------------

function get_leave_hours($from, $to, $emp_id) {
	$sql = "SELECT hours_no FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp_id)." AND overtime_id = 0 AND hours_no < 8 AND att_date BETWEEN '".$from."' AND '".$to."'";

	$result = db_query($sql, 'Could not get attendance');

    $leave = 0;
	while($row = db_fetch($result)) {
        $leave += (8-$row['hours_no']);
	}
	return $leave;
}

//--------------------------------------------------------------------------

function get_ot_from_date_range($from, $to, $emp_id, $ot_id) {
	$sql = "SELECT hours_no FROM ".TB_PREF."attendance WHERE emp_id = ".$emp_id." AND overtime_id = ".db_escape($ot_id)." AND att_date BETWEEN '".$from."' AND '".$to."'";

	$hours = db_query($sql, 'Could not get data from attendance');

    $result = 0;
	while($row = db_fetch($hours)) {
	    $result += $row['hours_no'];
	}
	return $result;
}
