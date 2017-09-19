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

include_once($path_to_root."/modules/FrontHrm/includes/ui/employee_ui.inc");
include_once($path_to_root."/modules/FrontHrm/includes/ui/department_ui.inc");
include_once($path_to_root."/modules/FrontHrm/includes/ui/salary_scale_ui.inc");
include_once($path_to_root."/modules/FrontHrm/includes/ui/overtime_ui.inc");
include_once($path_to_root."/modules/FrontHrm/includes/ui/payslip_ui.inc");

define('DEBIT', 1);
define('CREDIT', 0);

define('PT_EMPLOYEE', 6);

define ('WORK_DAYS', 26);
define ('WORK_HOURS', 8);

define('GL_BASIC_SALARY', 5410);
define('GL_DEDUCT_LEAVE', 5410);
define('GL_OVERTIME', 5420);

define ('AC_PAYABLE', 2100);

define ('ST_PAYSLIP', 15);
