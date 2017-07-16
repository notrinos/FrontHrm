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

function check_approved($emp_id, $overtime_id, $att_date) {
    $sql = "SELECT * FROM ".TB_PREF."attendance WHERE emp_id = ".$emp_id." AND overtime_id = ".$overtime_id." AND att_date = '".date2sql($att_date)."'";
    
    $result = db_query($sql, 'Could not get attendance data');
    return db_num_rows($result);
}

//--------------------------------------------------------------------------

function add_time_att($emp_id, $overtime_id, $hours_no=null, $att_date) {
    
    if(isset($hours_no) && $hours_no != '') {
        $check_approved = check_approved($emp_id, $overtime_id, $att_date);
        if($check_approved != 1) {
        
            $sql = "INSERT INTO ".TB_PREF."attendance(emp_id, overtime_id, hours_no, att_date) VALUES(".$emp_id.",".$overtime_id.",".$hours_no.",'".date2sql($att_date)."')";
    
            db_query($sql, 'Could not add time clock');
        }
        else {
            display_error('Data has already exist');
            set_focus($emp_id.'-'.$overtime_id);
            exit();
        }
    }
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