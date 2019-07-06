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

$page_security = 'SA_EMPL';
$path_to_root  = '../../..';

include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

//--------------------------------------------------------------------------

function can_process() {

	if(!is_date($_POST['from_date'])) {
		display_error(_('The entered date is invalid.'));
		set_focus('from_date');
		return false;
	}
	elseif(!is_date($_POST['to_date'])) {
		display_error(_('The entered date is invalid.'));
		set_focus('to_date');
		return false;
	}
	elseif(date_comp($_POST['from_date'], Today()) > 0) {

		display_error(_('Cannot make attendance for the date in the future.'));
		set_focus('from_date');
		return false;
	}
	elseif(date_comp($_POST['to_date'], Today()) > 0) {

		display_error(_('Cannot make attendance for the date in the future.'));
		set_focus('to_date');
		return false;
	}
	
	foreach(db_query(get_employees(false, false, get_post('DeptId'))) as $emp) {

		$emp_id = $emp['emp_id'];
		$err = _('Attendance input data must be greater than 0, less than 24 hours and formatted in <b>HH:MM</b> or <b>Integer</b>, example - 02:25 , 2:25, 8, 23:59 ...');
		
		if(strlen($_POST[$emp_id.'-0']) != 0 && (!preg_match("/^(?(?=\d{2})(?:2[0-3]|[01][0-9])|[0-9]):[0-5][0-9]$/", $_POST[$emp_id.'-0']) && (!is_numeric($_POST[$emp_id.'-0']) || $_POST[$emp_id.'-0'] >= 24 || $_POST[$emp_id.'-0'] <= 0)) && empty($_POST[$emp_id.'-leave'])) {

			display_error($err);
			set_focus($emp_id.'-0');
			return false;
		}
		foreach(db_query(get_overtime()) as $ot) {

			$ot_id = $ot['overtime_id'];
			
			if(strlen($_POST[$emp_id.'-'.$ot_id]) != 0 && (!preg_match("/^(?(?=\d{2})(?:2[0-3]|[01][0-9])|[0-9]):[0-5][0-9]$/", $_POST[$emp_id.'-'.$ot_id]) && (!is_numeric($_POST[$emp_id.'-'.$ot_id]) || $_POST[$emp_id.'-'.$ot_id] >= 24 || $_POST[$emp_id.'-0'] <= 0)) && empty($_POST[$emp_id.'-leave'])) {
				
				display_error($err);
				set_focus($emp_id.'-'.$ot_id);
				return false;
			}
		}
	}
	return true;
}

function write_attendance_range($emp_id, $time_type, $value=0, $rate, $from, $to, $leave=false) {

	$from = date2sql($from);
	$to = date2sql($to);
	$begin = new DateTime($from);
	$end = new DateTime($to);
	$end = $end->modify('+1 day');
	$interval = DateInterval::createFromDateString('1 day');
	$period = new DatePeriod($begin, $interval, $end);

	foreach ($period as $dt) {
		$day = $dt->format("Y-m-d");
		$day = sql2date($day);
		write_attendance($emp_id, $time_type, $value, $rate, $day, $leave);
	}
}

function check_paid_in_range($emp_id, $from, $to) {

	$from = date2sql($from);
	$to = date2sql($to);
	$begin = new DateTime($from);
	$end = new DateTime($to);
	$end = $end->modify('+1 day');
	$interval = DateInterval::createFromDateString('1 day');
	$period = new DatePeriod($begin, $interval, $end);

	foreach ($period as $dt) {
		$day = $dt->format("Y-m-d");
		$day = sql2date($day);
		if(check_date_paid($emp_id, $day))
			return true;
	}
	return false;
}

//--------------------------------------------------------------------------

page(_($help_context = 'Employees Attendance'), false, false, '', $js);

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
date_cells(_('From').':', 'from_date', _('Attendance date begin'));
date_cells(_('To').':', 'to_date', _('Aattendance date end'));
department_list_cells(_('For department').':', 'DeptId', null, _('All departments'), true);
submit_cells('bulk', _('Bulk'), '', _('Record all as regular work'), true);
end_row();
end_table(1);

start_table(TABLESTYLE2);
$initial_cols = array('ID', _('Employee'), _('Regular time'));
$overtimes = db_query(get_overtime());
$remaining_cols = array();
$overtime_id    = array();
$k=0;
while($overtime = db_fetch($overtimes)) {
    $remaining_cols[$k] = $overtime['overtime_name'];
    $overtime_id[$k] = $overtime['overtime_id'];
    $k++;
}
$remaining_cols[] = _('Leave Type');

$th = array_merge($initial_cols, $remaining_cols);
$employees = db_query(get_employees(false, false, get_post('DeptId')));
$emp_ids = array();

$k = 0;
foreach ($employees as $emp) {
	$emp_ids[$k] = $emp['emp_id'];
	$k++;
}

if(isset($_POST['bulk'])) {
	foreach($emp_ids as $emp_id) {
		if(get_post($emp_id) == 1)
		    $_POST[$emp_id.'-0'] = $Work_hours;
		else
			$_POST[$emp_id.'-0'] = '';
	}
	$Ajax->activate('_page_body');
}

table_header($th);

foreach($employees as $employee) {
    
    start_row();
    label_cell($employee['emp_id'].checkbox(null, $employee['emp_id'], isset($_POST[$employee['emp_id']]) ? $_POST[$employee['emp_id']] : 1));
    label_cell($employee['name']);
    $name1 = $employee['emp_id'].'-0';
    text_cells(null, $name1, null, 10, 10);
    
    $i=0;
    while($i < count($remaining_cols) - 1) {
        $name2 = $employee['emp_id'].'-'.$overtime_id[$i];
        text_cells(null, $name2, null, 10, 10);
        $i++;
    }
    leave_types_list_cells(null, $employee['emp_id'].'-leave', null, _('Select Leave Type'), true);
    end_row();
}

end_table(1);
    
submit_center('addatt', _('Save attendance'), true, '', 'default');

//--------------------------------------------------------------------------

if(!db_has_employee())
	display_error(_('There are no employees for attendance.'));

if(isset($_POST['addatt'])) {
	
	if(!can_process())
		return;
    
    $att_items = 0;
    foreach($emp_ids as $emp_id) {
        
		if($_POST[$emp_id.'-0'] && check_paid_in_range($emp_id, $_POST['from_date'], $_POST['to_date'])) {
			
			display_error(_('The selected date range includes a date that has been approved, please select another date range.'));
            set_focus('from_date');
			exit();
		}
		elseif(!empty($_POST[$emp_id.'-leave'])) {
			$emp_leave = $_POST[$emp_id.'-leave'];
			$leave_rate = get_leave_type($emp_leave)['pay_rate'];
			$att_items ++;
			write_attendance_range($emp_id, 0, 0, $leave_rate, $_POST['from_date'], $_POST['to_date'], $emp_leave);
		}
		else {

			if(strlen($_POST[$emp_id.'-0']) > 0)
                $att_items ++;
			
			write_attendance_range($emp_id, 0, time_to_float($_POST[$emp_id.'-0']), 1, $_POST['from_date'], $_POST['to_date']);

			foreach($overtime_id as $ot) {
				$rate = get_overtime($ot)['overtime_rate'];
				if(strlen($_POST[$emp_id.'-'.$ot]) > 0)
					$att_items ++;
				write_attendance_range($emp_id, $ot, time_to_float($_POST[$emp_id.'-'.$ot]), $rate, $_POST['from_date'], $_POST['to_date']);
        	}
		}
    }
	if($att_items > 0)
		display_notification(_('Attendance has been saved.'));
	else
		display_notification(_('Nothing added'));
	$Ajax->activate('_page_body');
}

end_form();
end_page();