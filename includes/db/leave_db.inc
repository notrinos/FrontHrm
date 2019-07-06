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

function db_has_leave_type() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."leave_type");
}

//--------------------------------------------------------------------------

function write_leave($id = false, $leave_name, $leave_code, $pay_rate) {

	if($id == -1)
		$sql = "INSERT INTO ".TB_PREF."leave_type(leave_name, leave_code, pay_rate) VALUES (".db_escape($leave_name).", ".db_escape($leave_code).", ".db_escape($pay_rate).")";
	else
		$sql = "UPDATE ".TB_PREF."leave_type SET leave_name = ".db_escape($leave_name).", leave_code = ".db_escape($leave_code).", pay_rate = ".db_escape($pay_rate)." WHERE leave_id = ".db_escape($id);
	
	db_query($sql, _('Could not write leave data'));
}

//--------------------------------------------------------------------------

function get_leave_type($id = false, $all = false) {
	
	$sql = "SELECT * FROM ".TB_PREF."leave_type";
	$where = array();
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "leave_id=".db_escape($id);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	
	$sql .= " ORDER BY leave_id";

	$result = db_query($sql, _('Could not get leave data.'));
	
	if($id)
		return db_fetch($result);
	else
		return $sql;
}

//--------------------------------------------------------------------------

function delete_leave_type($id) {
	$sql = "DELETE FROM ".TB_PREF."leave_type WHERE leave_id = ".db_escape($id);
	db_query($sql, _('Could not delete leave type.'));
}

//--------------------------------------------------------------------------

function leave_type_used($id) {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."leave WHERE leave_id = ".db_escape($id));
}

//--------------------------------------------------------------------------

function get_leave($emp_id=false, $leave_id=false, $from=false, $to=false) {

	$sql = "SELECT * FROM ".TB_PREF."leave";
	$where = array();

	if($emp_id)
		$where[] = "emp_id = ".db_escape($emp_id);
	if($leave_id)
		$where[] = "leave_id = ".db_escape($leave_id);
	if($from)
		$where[] = "date >= '".date2sql($from)."'";
	if($to)
		$where[] = "date <= '".date2sql($to)."'";

	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	
	$result = db_query($sql, _('could not get leave data.'));

	return $result;
}

//--------------------------------------------------------------------------

function get_employee_leave($emp_id, $leave_id, $from, $to) {

	$sql = "SELECT COUNT(*) FROM ".TB_PREF."leave WHERE emp_id = ".db_escape($emp_id)." AND leave_id = ".db_escape($leave_id)." AND date BETWEEN '".date2sql($from)."' AND '".date2sql($to)."'";
	$result = db_query($sql, _('could not get leave data'));

	return db_fetch($result)[0];
}