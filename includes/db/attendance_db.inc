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

function check_attended($emp_id, $overtime_id, $att_date) {
    $sql = "SELECT * FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp_id)." AND overtime_id = ".db_escape($overtime_id)." AND att_date = '".date2sql($att_date)."'";
    
    return check_empty_result($sql);
}

//--------------------------------------------------------------------------

function write_attendance($emp_id, $overtime_id, $hours_no = null, $att_date) {
    
    if(isset($hours_no) && $hours_no != '') {

    	if(check_attended($emp_id, $overtime_id, $att_date))
    		$sql = "UPDATE ".TB_PREF."attendance SET hours_no = ".db_escape($hours_no)." WHERE att_date = '".date2sql($att_date)."' AND emp_id = ".db_escape($emp_id)." AND overtime_id = ".db_escape($overtime_id);
    	else
    		$sql = "INSERT INTO ".TB_PREF."attendance(emp_id, overtime_id, hours_no, att_date) VALUES(".db_escape($emp_id).",".db_escape($overtime_id).",".db_escape($hours_no).",'".date2sql($att_date)."')";
    
        db_query($sql, 'Could not add time clock');
    }
}

//--------------------------------------------------------------------------

function check_date_paid($person_id, $att_date) {
	return check_empty_result("SELECT gl.payslip_no, gl.person_id, p.payslip_no, p.from_date, p.to_date FROM ".TB_PREF."gl_trans gl, ".TB_PREF."payslip_detail p WHERE SUBSTRING(gl.person_id, 4) = ".db_escape($person_id)." AND gl.payslip_no = p.payslip_no AND '".date2sql($att_date)."' BETWEEN p.from_date AND p.to_date");
}

//--------------------------------------------------------------------------

function get_attendance($from, $to, $emp_id = 0, $department = 0, $ot_id = 0) {

    $from = DateTime::createFromFormat('Y-m-d', date2sql($from));
    $to = DateTime::createFromFormat('Y-m-d', date2sql($to));

	$sql = "SELECT emp.emp_id, CONCAT(emp_first_name,' ',emp_last_name)";

	if($emp_id == 0 && $department == 0) {
	    while(sql_date_comp($from->format('Y-m-d'),$to->format('Y-m-d')) <= 0) {
	        $sql .= ",MAX(CASE WHEN att.att_date = '".$from->format('Y-m-d')."' AND emp.emp_id = att.emp_id AND att.overtime_id = ".$ot_id." THEN CASE hours_no WHEN '0' THEN '<span style=color:red>x</span>' ELSE hours_no END END)";
	        $from->modify('+1 day');
	    }
	    $sql .= " FROM ".TB_PREF."employee emp, ".TB_PREF."attendance att WHERE !emp.inactive GROUP BY emp.emp_id";
	}
	else if($emp_id != 0) {
	    while(sql_date_comp($from->format('Y-m-d'),$to->format('Y-m-d')) <= 0) {
	        $sql .= ",MAX(CASE WHEN emp.emp_id = ".$emp_id." AND att.att_date = '".$from->format('Y-m-d')."' AND emp.emp_id = att.emp_id AND att.overtime_id = ".$ot_id." THEN hours_no END)";
	        $from->modify('+1 day');
	    }
	    $sql .= " FROM ".TB_PREF."employee emp, ".TB_PREF."attendance att WHERE emp.emp_id = '".$emp_id."' GROUP BY emp.emp_id";
	}
	else {
        while(sql_date_comp($from->format('Y-m-d'),$to->format('Y-m-d')) <= 0) {
            $sql .= ",MAX(CASE WHEN att.att_date = '".$from->format('Y-m-d')."' AND emp.emp_id = att.emp_id AND att.overtime_id = ".$ot_id." THEN hours_no END)";
            $from->modify('+1 day');
        }
        $sql .= " FROM ".TB_PREF."employee emp, ".TB_PREF."attendance att WHERE emp.department_id = ".$department." AND !emp.inactive GROUP BY emp.emp_id";
	}

	return $sql;
}
