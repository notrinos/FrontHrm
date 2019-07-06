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
	
page(_($help_context = 'Manage Pay Elements'));
simple_page_mode(false);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') {

	$input_error = 0;
    
    if(empty(trim($_POST['element_name']))) {
		$input_error = 1;
		display_error(_('Element Name cannot be empty.'));
		set_focus('element_name');
	}
	elseif(payroll_account_exist($_POST['AccountId']) && $Mode=='ADD_ITEM') {
        $input_error = 1;
        display_error(_('Selected account has already exists.'));
        set_focus('AccountId');
    }
	if($input_error != 1) {

		if($selected_id == '') {
			add_pay_element($_POST['element_name'], $_POST['AccountId']);
			display_notification(_('Pay element has been added.'));
		}
		else {
			update_pay_element($selected_id, $_POST['element_name']);
			display_notification(_('The selected pay element has been updated.'));
		}
    	
		$Mode = 'RESET';
	}
}

//--------------------------------------------------------------------------

if($Mode == 'Delete') {

	if(payroll_account_used($selected_id)) {
		display_error(_('Cannot delete this account because payroll rules have been created using it.'));
	}
	else {
		delete_payroll_account($selected_id);
		display_notification(_('Selected account has been deleted'));
	}
	$Mode = 'RESET';
}

if($Mode == 'RESET') {
	$selected_id = '';
	$_POST['AccountId'] = '';
	$_POST['element_name'] = '';
}

//--------------------------------------------------------------------------

$result = get_payroll_elements();

start_form();
start_table(TABLESTYLE2);
$th = array(_('Element'), _('Account Code'), _('Account Name'), '', '');

table_header($th);

$k = 0; 
while($myrow = db_fetch($result)) {

	alt_table_row_color($k);

	label_cell($myrow['element_name']);
	label_cell($myrow['account_code'], "align='center'");
	label_cell($myrow['account_name']);
	edit_button_cell('Edit'.$myrow['element_id'], _('Edit'));
 	delete_button_cell('Delete'.$myrow['element_id'], _('Delete'));
    
	end_row();
}

end_table(1);

//--------------------------------------------------------------------------

start_table(TABLESTYLE_NOBORDER);

if($selected_id != -1) {
	
 	if($Mode == 'Edit') {
		$myrow = get_payroll_elements($selected_id);
		$_POST['element_name']  = $myrow['element_name'];
		$_POST['AccountId']  = $myrow['account_code'];
 	}
 	hidden('selected_id', $selected_id);
}

text_row_ex(_('Element Name:'), 'element_name', 37, 50);
gl_all_accounts_list_row(_('Select Account:'), 'AccountId', null, true);

end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();
end_page();