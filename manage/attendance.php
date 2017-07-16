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

$page_security = 'SA_EMPL';
$path_to_root  = '../../..';

include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_db.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_ui.inc");

//--------------------------------------------------------------------------

function can_process() {
	
	foreach(db_query(get_employees()) as $emp) {
		
		if(strlen($_POST[$emp['emp_id'].'-0']) != 0 && !is_numeric($_POST[$emp['emp_id'].'-0']))
		{
			display_error(_("Overtime hours must be a number."));
			set_focus($emp['emp_id'].'-0');
			return false;
		}
		foreach(db_query(get_overtime()) as $ot) {
			
			if(strlen($_POST[$emp['emp_id'].'-'.$ot['overtime_id']]) != 0 && !is_numeric($_POST[$emp['emp_id'].'-'.$ot['overtime_id']])) {
				
				display_error(_("Overtime hours must be a number."));
				set_focus($emp['emp_id'].'-'.$ot['overtime_id']);
				return false;
			}
		}
	}
	return true;
}

//--------------------------------------------------------------------------

page(_($help_context = _("Employees Attendance")), false, false, "", $js);

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
date_cells(_("Attendance date:"), 'attend_date');
department_list_cells(_("For department:"), "DeptId", null, _("All departments"), true);
end_row();
end_table(1);

start_table(TABLESTYLE2);
$initial_cols = array("ID", _("Employee"), _("Regular time"));
$overtimes = db_query(get_overtime());
$remaining_cols = array();
$overtime_id    = array();
$k=0;
while($overtime = db_fetch($overtimes)) {
    $remaining_cols[$k] = $overtime['overtime_name'];
    $overtime_id[$k] = $overtime['overtime_id'];
    $k++;
}

$th = array_merge($initial_cols, $remaining_cols);
$employees = db_query(get_employees(false, false, get_post('DeptId')));

$emp_ids = array();

table_header($th);

$k=0;
while($employee = db_fetch($employees)) {
    
    start_row();
    label_cell($employee['emp_id']);
    label_cell($employee['name']);
    $name1 = $employee['emp_id'].'-0';
    text_cells(null, $name1, null, 10, 10);
    $emp_ids[$k] = $employee['emp_id'];
    
    $i=0;
    while($i < count($remaining_cols)) {
        $name2 = $employee['emp_id'].'-'.$overtime_id[$i];
        text_cells(null, $name2, null, 10, 10);
        $i++;
    }
    $k++;
    end_row();
}

end_table(1);
    
submit_center('addatt', _("Save attendance"), true, '', 'default');

//--------------------------------------------------------------------------

if(!db_has_employee())
	display_error(_("There are no employees for attendance."));

if(isset($_POST['addatt'])) {
	
	if(!can_process())
		return;
    
    foreach($emp_ids as $id) {
        
        add_time_att($id, 0, $_POST[$id.'-0'], $_POST['attend_date']);
        
        foreach($overtime_id as $ot) {
            if($_POST[$id.'-'.$ot]=='')
                $_POST[$id.'-'.$ot] = 0;
            add_time_att($id, $ot, $_POST[$id.'-'.$ot], $_POST['attend_date']);
        }
    }
    display_notification(_("Attendance has been saved"));
}

end_form();
end_page();
