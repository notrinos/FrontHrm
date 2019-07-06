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

include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

define('RC_HRM', 8);

function employees($name, $type) {
	if($type == 'EMPLOYEE')
		return employee_list($name, null, _('No employee filter'), false, false, get_post('PARAM_1'));
}

$reports->register_controls('employees');

function departments($name, $type) {
	if($type == 'DEPARTMENT')
		return department_list($name, null, _('No department filter'), true);
}

$reports->register_controls('departments');

function gender($name, $type) {
	if($type == 'EMPGENDER')
	    return "<select name = '".$name."'><option value='-1'>"._('No gender filter')."</option><option value='1'>"._('Male')."</option><option value='0'>"._('Female')."</option><option value='2'>"._('Other')."</option></select>";
}

$reports->register_controls('gender');

$reports->addReportClass(_('Human Resource'), RC_HRM);
$reports->addReport(RC_HRM, '_employees', _('List of Employees'),
	array(	_('Gender') => 'EMPGENDER',
		    _('Department') => 'DEPARTMENT',
		    _('From') => 'EMPLOYEE',
			_('To') => 'EMPLOYEE',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'
));