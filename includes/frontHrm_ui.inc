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

define('ST_PAYSLIP', 889);

$Payable_act = get_company_pref('payroll_payable_act');
$Deduct_act = get_company_pref('payroll_deductleave_act');
$Overtime_act = get_company_pref('payroll_overtime_act');
$Work_days = get_company_pref('payroll_month_work_days');
$Work_hours = get_company_pref('payroll_work_hours');
$USE_DEPT_ACC = get_company_pref('payroll_dept_based');

/* Don't change this value after setting up company grades number */
$max_grade_number = 20;