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

print_employees_list();

function get_employees_list($gender, $dep, $from, $to) {
    $dep = empty($dep) ? false : $dep;
	$sql = get_employees(false, false, $dep);

    if($gender != -1)
    	$sql .= " AND gender = ".db_escape($gender);
    if(!empty($from))
    	$sql .= " AND emp_id >= ".db_escape($from);
    if(!empty($to))
    	$sql .= " AND emp_id <= ".db_escape($to);

    return db_query($sql, _('could not get employee'));
}

function display_department_employees($dep_id, $dep_name, $gender, $from, $to, $rep) {

	$rep->Font('bold');
	$rep->TextCol(0, 1, $dep_id);
	$rep->TextCol(1, 4, $dep_name);
	$rep->Font();
	$rep->row -= 4;
	$rep->Line($rep->row);
	$rep->NewLine();

	$emps = get_employees_list($gender, $dep_id, $from, $to);
	while($emp = db_fetch($emps)) {
        $rep->TextCol(0, 1, $emp['emp_id']);
		$rep->TextCol(1, 2, $emp['name']);
		$rep->TextCol(2, 3, $emp['gender'] == 1 ? _('Male') : ($emp['gender'] == 0 ? _('Female') : 'Other'));
		$rep->TextCol(3, 4, $emp['emp_mobile']);
		$rep->TextCol(4, 5, $emp['emp_email']);
		$rep->DateCol(5, 6, $emp['emp_birthdate'], true);
		$rep->DateCol(6, 7, $emp['emp_hiredate'], true);
		$rep->TextCol(7, 8, get_position($emp['position_id'])['position_name']);
		$rep->NewLine();
    }
    $rep->NewLine();
}

function print_employees_list() {
	global $path_to_root;
    
    $gender = $_POST['PARAM_0'];
	$dep = $_POST['PARAM_1'] == 0 ? false : $_POST['PARAM_1'];
	$from = $_POST['PARAM_2'];
	$to = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');

	$cols = array(0, 40, 160, 210, 280, 360, 410, 480, 530);

	$headers = array(_('Id'), _('Employee name'), _('Gender'), _('Mobile'), _('Email'), _('Birth date'), _('Hired date'), _('Job Position'));
	
	$aligns = array('left',	'left',	'left',	'left', 'left', 'center', 'center', 'left');
	
	$params = array(0 => $comments);

	$rep = new FrontReport(_('Employees List'), "EmployeesList", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->NewPage();
    
    if($dep) {
    	$dep_result = get_departments($dep);
        display_department_employees($dep_result['dept_id'], $dep_result['dept_name'], $gender, $from, $to, $rep); 
    }
    else {
	    $dep_result = db_query(get_departments(), _('could not get department data'));
	    while ($dept = db_fetch($dep_result)) {   
            display_department_employees($dept['dept_id'], $dept['dept_name'], $gender, $from, $to, $rep);
	    }
	}
	$rep->Line($rep->row + 10);
	$rep->End();
}