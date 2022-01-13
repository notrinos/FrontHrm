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

include_once($path_to_root.'/modules/FrontHrm/includes/ui/employee_ui.inc');
include_once($path_to_root.'/modules/FrontHrm/includes/ui/department_ui.inc');
include_once($path_to_root.'/modules/FrontHrm/includes/ui/job_position_ui.inc');
include_once($path_to_root.'/modules/FrontHrm/includes/ui/overtime_ui.inc');
include_once($path_to_root.'/modules/FrontHrm/includes/ui/payslip_ui.inc');
include_once($path_to_root.'/modules/FrontHrm/includes/ui/employee_bank_ui.inc');
include_once($path_to_root.'/modules/FrontHrm/includes/ui/doc_ui.inc');
include_once($path_to_root.'/modules/FrontHrm/includes/ui/leave_ui.inc');

define('DEBIT', 1);
define('CREDIT', 0);

define('PT_EMPLOYEE', 6);
define('MONTHLY_SALARY', 0);
define('DAILY_WAGE', 1);

define('ST_PAYSLIP', '_payslip');

$Payable_act = get_company_pref('payroll_payable_act');
$Deduct_act = get_company_pref('payroll_deductleave_act');
$Overtime_act = get_company_pref('payroll_overtime_act');
$Work_days = get_company_pref('payroll_month_work_days');
$Work_hours = get_company_pref('payroll_work_hours');
$USE_DEPT_ACC = get_company_pref('payroll_dept_based');

/* Don't change this value after setting up company grades number */
$max_grade_number = 20;

function weekdays_list_row($label, $name, $selected_id=null, $submit_on_change=false) {
	$days = array(
		1=>_('Monday'),
		2=>_('Tuesday'),
		3=>_('Wednesday'),
		4=>_('Thursday'),
		5=>_('Friday'),
		6=>_('Saturday'),
		7=>_('Sunday')
	);

	echo "<tr><td class='label'>".$label."</td><td>";
	echo array_selector($name, $selected_id, $days, 
		array( 
			'select_submit'=> $submit_on_change,
			'class' => array('nosearch'),
			'async' => false )
		);
	echo "<td></tr>\n";
}