<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương <trananhphuong83@gmail.com>          |
|   Date :   09-Jul-2017                                 |
|   Description: NotrinosERP Payroll & Hrm Module        |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

function time_to_float($time) {
	if(strpos($time, ':') != false) {
		sscanf($time, "%d:%d", $hours, $minutes);
		$time_in_float = $hours + $minutes/60;
	}
	else
		$time_in_float = $time;
	
	return $time_in_float;
}

//--------------------------------------------------------------------------

function check_attended($emp_id, $overtime_id, $att_date) {
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp_id)." AND overtime_id = ".db_escape($overtime_id)." AND att_date = '".date2sql($att_date)."'";
	
	return check_empty_result($sql);
}

//--------------------------------------------------------------------------

function check_leave($emp_id, $date) {
	$sql = "SELECT COUNT(*) FROM `".TB_PREF."leave` WHERE emp_id = ".db_escape($emp_id)." AND date = '".date2sql($date)."'";
	
	return check_empty_result($sql);
}

//--------------------------------------------------------------------------

function write_attendance($emp_id, $overtime_id, $hours_no, $rate, $att_date, $leave_id=false) {

	if(!empty($leave_id)) {
		if(check_leave($emp_id, $att_date))
			$sql = "UPDATE `".TB_PREF."leave` SET leave_id = ".db_escape($leave_id).", pay_rate = ".db_escape($rate)." WHERE date = '".date2sql($att_date)."' AND emp_id = ".db_escape($emp_id);
		else
			$sql = "INSERT INTO `".TB_PREF."leave` (emp_id, leave_id, pay_rate, date) VALUES(".db_escape($emp_id).",".db_escape($leave_id).",".db_escape($rate).",'".date2sql($att_date)."')";

		$sql2 = "DELETE FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp_id)." AND att_date = '".date2sql($att_date)."'";
	}
	elseif(is_numeric($hours_no)) {

		if(check_attended($emp_id, $overtime_id, $att_date))
			$sql = "UPDATE ".TB_PREF."attendance SET hours_no = ".db_escape($hours_no).", rate = ".db_escape($rate)." WHERE att_date = '".date2sql($att_date)."' AND emp_id = ".db_escape($emp_id)." AND overtime_id = ".db_escape($overtime_id);
		else
			$sql = "INSERT INTO ".TB_PREF."attendance (emp_id, overtime_id, hours_no, rate, att_date) VALUES(".db_escape($emp_id).",".db_escape($overtime_id).",".db_escape($hours_no).",".db_escape($rate).",'".date2sql($att_date)."')";

		$sql2 = "DELETE FROM `".TB_PREF."leave` WHERE emp_id = ".db_escape($emp_id)." AND date = '".date2sql($att_date)."'";
	}

	if(!empty($sql))
		db_query($sql, 'Could not write attendance data');
	if(!empty($sql2))
		db_query($sql2, 'Could not write attendance data');
}

//--------------------------------------------------------------------------

function check_date_paid($person_id, $att_date) {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."payslip WHERE emp_id = ".db_escape($person_id)." AND '".date2sql($att_date)."' BETWEEN from_date AND to_date");
}

//--------------------------------------------------------------------------

function get_attendance($from, $to, $emp_id=0, $department=0, $ot_id=0) {

	$from = DateTime::createFromFormat('Y-m-d', date2sql($from));
	$to = DateTime::createFromFormat('Y-m-d', date2sql($to));

	$sql = "SELECT emp.emp_id, CONCAT(emp_first_name,' ',emp_last_name) ";

	while(sql_date_comp($from->format('Y-m-d'),$to->format('Y-m-d')) <= 0) {
		$sql .= ", IFNULL((SELECT hours_no FROM ".TB_PREF."attendance WHERE ".TB_PREF."attendance.emp_id = emp.emp_id AND att_date = '".$from->format('Y-m-d')."' AND overtime_id = ".db_escape($ot_id)."), (SELECT CASE WHEN lt.pay_rate >= '100' THEN CONCAT('<b style=color:green>',leave_code,'</b>') WHEN lt.pay_rate < '100' AND lt.pay_rate > '0' THEN CONCAT('<b style=color:orange>',leave_code,'</b>') WHEN lt.pay_rate <= '0' THEN CONCAT('<b style=color:red>',lt.leave_code,'</b>') END FROM ".TB_PREF."leave_type lt, `".TB_PREF."leave` l WHERE l.leave_id = lt.leave_id AND l.date = '".$from->format('Y-m-d')."' AND l.emp_id = emp.emp_id)) as '".$from->format('Y-m-d')."'";
		$from->modify('+1 day');
	}
	$sql .= " FROM ".TB_PREF."employee emp WHERE !emp.inactive";

	if(!empty($emp_id))
		$sql .= " AND emp.emp_id = ".db_escape($emp_id);
	if(!empty($department))
		$sql .= " AND emp.department_id = ".db_escape($department);

	$sql .= " GROUP BY emp.emp_id";

	return $sql;
}

function has_leave_data() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."leave");
}
