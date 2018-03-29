<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương                                      |
|   Date :   09-Jul-2017                                 |
|   Description: Frontaccounting Payroll & Hrm Module    |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

include_once($path_to_root."/modules/FrontHrm/includes/ui/employee_ui.inc");
include_once($path_to_root."/modules/FrontHrm/includes/ui/department_ui.inc");
include_once($path_to_root."/modules/FrontHrm/includes/ui/salary_scale_ui.inc");
include_once($path_to_root."/modules/FrontHrm/includes/ui/overtime_ui.inc");
include_once($path_to_root."/modules/FrontHrm/includes/ui/payslip_ui.inc");

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