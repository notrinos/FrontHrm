<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương <trananhphuong83@gmail.com>          |
|   Date :   09-Jul-2017                                 |
|   Description: Frontaccounting Payroll & Hrm Module    |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

function db_has_overtime() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."overtime");
}

//--------------------------------------------------------------------------

function write_overtime($id = false, $name, $rate) {

	if(!$id)
		$sql = "INSERT INTO ".TB_PREF."overtime(overtime_name, overtime_rate) VALUES (".db_escape($name).", ".db_escape($rate).")";
	else
		$sql = "UPDATE ".TB_PREF."overtime SET overtime_name = ".db_escape($name).", overtime_rate = ".db_escape($rate)." WHERE overtime_id = ".db_escape($id);
	
	db_query($sql, _('Could not write overtime data'));
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
	
	$sql .= " ORDER BY overtime_id";

	$result = db_query($sql, _('Could not get overtime data.'));
	
	if($id)
		return db_fetch($result);
	else
		return $sql;
}

//--------------------------------------------------------------------------

function delete_overtime($id) {
	$sql = "DELETE FROM ".TB_PREF."overtime WHERE overtime_id = ".db_escape($id);
	db_query($sql, _('Could not delete overtime.'));
}

//--------------------------------------------------------------------------

function overtime_used($id) {
	return check_empty_result("SELECT overtime_id FROM ".TB_PREF."attendance WHERE overtime_id = ".db_escape($id));
}

//--------------------------------------------------------------------------

function get_workdays_no($from, $to, $emp_id) {
	$sql = "SELECT hours_no FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp_id)." AND overtime_id = 0 AND att_date BETWEEN '".date2sql($from)."' AND '".date2sql($to)."'";

	$result = db_query($sql, _('Could not get attendance data'));

    return db_num_rows($result);
}

//--------------------------------------------------------------------------

function get_leave_hours($from, $to, $emp_id) {
    global $Work_hours;

	$sql = "SELECT hours_no FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp_id)." AND overtime_id = 0 AND hours_no < 8 AND att_date BETWEEN '".date2sql($from)."' AND '".date2sql($to)."'";

	$result = db_query($sql, _('Could not get attendance data'));

    $leave = 0;
	while($row = db_fetch($result)) {
        $leave += ($Work_hours - $row['hours_no']);
	}
	return $leave;
}

//--------------------------------------------------------------------------

function get_ot_from_date_range($from, $to, $emp_id, $ot_id) {
	$sql = "SELECT hours_no, rate FROM ".TB_PREF."attendance WHERE emp_id = ".$emp_id." AND overtime_id = ".db_escape($ot_id)." AND att_date BETWEEN '".date2sql($from)."' AND '".date2sql($to)."'";

	return db_query($sql, _('Could not get attendance data'));
}