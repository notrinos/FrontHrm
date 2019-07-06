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

$page_security = 'SA_HRSETUP';
$path_to_root  = '../../..';

include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

//--------------------------------------------------------------------------

page(_($help_context = 'Leave Types'));
simple_page_mode();

if($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') {

	if(empty(trim($_POST['pay_rate']))) {
		display_error(_('The Salary rate field cannot be empty.'));
		set_focus('pay_rate');
	}
    elseif(!is_numeric($_POST['pay_rate'])) {
		display_error(_('Salary rate must be a number.'));
		set_focus('pay_rate');
	}
	elseif(empty(trim($_POST['leave_name']))) {
		display_error(_('The Leave type name cannot be empty.'));
		set_focus('leave_name');
	}
	elseif(empty(trim($_POST['leave_code'])) || !preg_match("/^[a-zA-Z]+$/", $_POST['leave_code'])) {
		display_error(_('The Leave type code cannot be empty and only allows alphabet letters.'));
		set_focus('leave_code');
	}
	else {
    	write_leave($selected_id, $_POST['leave_name'], $_POST['leave_code'], input_num('pay_rate'));
		if($selected_id != -1)
			display_notification(_('Selected leave type has been updated'));
		else
			display_notification(_('New leave type has been added'));
		$Mode = 'RESET';
	}
}

//--------------------------------------------------------------------------

if($Mode == 'Delete') {

	if(leave_type_used($selected_id))
		display_error(_('This leave type cannot be deleted.'));
	else {
		delete_leave_type($selected_id);
		display_notification(_('Selected leave type has been deleted'));
	}
	$Mode = 'RESET';
}

if($Mode == 'RESET') {
	$selected_id = -1;
	$_POST['leave_name'] = '';
	$_POST['leave_code'] = '';
	$_POST['pay_rate'] = '';
}

//--------------------------------------------------------------------------

$result = db_query(get_leave_type(false, check_value('show_inactive')));

start_form();
start_table(TABLESTYLE);
$th = array(_('Id'), _('Leave Type Name'), _('Leave Type Code'), _('Salary Rate (%)'), "", "");
inactive_control_column($th);

table_header($th);
$k = 0;

while($myrow = db_fetch($result)) {
	alt_table_row_color($k);
	label_cell($myrow['leave_id']);
	label_cell($myrow['leave_name']);
	label_cell($myrow['leave_code']);
	percent_cell($myrow['pay_rate']);
	inactive_control_cell($myrow['leave_id'], $myrow['inactive'], 'leave_type', 'leave_id');
 	edit_button_cell('Edit'.$myrow['leave_id'], _('Edit'));
 	delete_button_cell('Delete'.$myrow['leave_id'], _('Delete'));
	end_row();
}

inactive_control_row($th);
end_table(1);

start_table(TABLESTYLE2);

if($selected_id != -1) {
	
 	if($Mode == 'Edit') {
		$myrow = get_leave_type($selected_id);
		$_POST['pay_rate'] = $myrow['pay_rate'];
		$_POST['leave_name']  = $myrow['leave_name'];
		$_POST['leave_code']  = $myrow['leave_code'];
	}
	hidden('selected_id', $selected_id);
}

percent_row(_('Salary rate:'), 'pay_rate', null, 20, 20);
text_row(_('Leave Type Name:'), 'leave_name', null, 30, 30);
text_row(_('Leave Type Code:'), 'leave_code', null, 5, 3);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();
end_page();