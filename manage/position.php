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

page(_($help_context = 'Manage Job Positions'));
simple_page_mode(true);

if($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') {

	if(empty(trim($_POST['name']))) {
		display_error(_('Name field cannot be empty.'));
		set_focus('name');
	}
	elseif(!check_num('amount', 0)) {
		display_error(_('Amount field value must be a positive number.'));
		set_focus('amount');
	}
	elseif(isset($_POST['AccountId']) && !is_expenses_account($_POST['AccountId'])) {
		display_error(_('Salary Basic Account must be an expenses account.'));
		set_focus('AccountId');
	}
	else {

		begin_transaction();
		$id = $selected_id == -1 ? false : $selected_id;
		write_position($id, $_POST['name'], $_POST['payBasis']);

		if($selected_id == -1) {
			$new = true;
			$added_position = db_insert_id();
		}
		else {
			$new = false;
			$added_position = $selected_id;
		}
		
		set_basic_salary($_POST['AccountId'], input_num('amount'), $added_position, $new);

		$employees = db_query(get_employees());
		if($id && db_num_rows($employees) > 0) {
			foreach ($employees as $staff) {
				if($staff['personal_salary'] == 1 && $staff['position_id'] == $added_position)
					update_personal_basic_account($staff['emp_id'], $_POST['AccountId']);
			}
		}

		commit_transaction();
		
    	if ($selected_id != -1)
			display_notification(_('Selected job position has been updated'));
    	else
			display_notification(_('New job position has been added'));
		
		$Mode = 'RESET';
	}
}

if ($Mode == 'Delete') {

	if(position_used($selected_id))
		display_error( _('This job position cannot be deleted.'));
	else {
		delete_position($selected_id);
		display_notification(_('Selected job position has been deleted'));
	}
	$Mode = 'RESET';
}

if($Mode == 'RESET') {
	$selected_id = -1;
	$_POST['name'] = $_POST['amount'] = '';
}

//--------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE);
$th = array(_('Id'), _('Name'), _('Salary amount'), _('Pay basis'), '', '');
inactive_control_column($th);
table_header($th);

$result = db_query(get_position(false, check_value('show_inactive')));
$k = 0;
while ($myrow = db_fetch($result)) {
	alt_table_row_color($k);
	$pay_basis = $myrow['pay_basis'] == 0 ? _('Monthly') : _('Daily');

	label_cell($myrow['position_id']);
	label_cell($myrow['position_name']);
	amount_cell($myrow['pay_amount']);
	label_cell($pay_basis);
	inactive_control_cell($myrow['position_id'], $myrow['inactive'], 'position', 'position_id');
	edit_button_cell('Edit'.$myrow['position_id'], _('Edit'));
	delete_button_cell('Delete'.$myrow['position_id'], _('Delete'));
	end_row();
}
inactive_control_row($th);
end_table(1);

start_table(TABLESTYLE2);

if($selected_id != -1) {
	
 	if ($Mode == 'Edit') {
		
		$myrow = get_position($selected_id);
		$_POST['name']  = $myrow['position_name'];
		$_POST['AccountId']  = $myrow['pay_rule_id'];
		$_POST['amount']  = price_format($myrow['pay_amount']);
		$_POST['payBasis']  = $myrow['pay_basis'];
 	}
 	hidden('selected_id', $selected_id);
}

text_row_ex(_('Position Name').':', 'name', 37, 50);
if(empty($USE_DEPT_ACC))
    gl_all_accounts_list_row(_('Salary Basic Account:'), 'AccountId', null, true);
else
	hidden('AccountId');
amount_row(_('Salary Basic Amount').':', 'amount', null, null, null, null, true);
label_row(_('Pay Basis').':', radio(_('Monthly salary'), 'payBasis', 0, 1).'&nbsp;&nbsp;'.radio(_('Daily wage'), 'payBasis', 1));

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();
end_page();