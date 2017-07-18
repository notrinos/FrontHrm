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

$page_security = 'SA_HRSETUP';
$path_to_root  = '../../..';

include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_db.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_ui.inc");

//--------------------------------------------------------------------------
	
page(_($help_context = "Manage Payroll Accounts"));
simple_page_mode(false);

if ($Mode=='ADD_ITEM') {

	$input_error = 0;
    
    if (payroll_account_exist($_POST['AccountId'])) {
        $input_error = 1;
        display_error("Selected account has been used");
        set_focus('AccountId');
    }
	if (strlen($_POST['AccountId']) == 0 || $_POST['AccountId'] == '') {
		$input_error = 1;
		display_warning(_("Select account first."));
		set_focus('AccountId');
	}
	if ($input_error !=1) {
    	add_payroll_account($_POST['AccountId']);
		display_notification(_("Account has been added."));
		$Mode = 'RESET';
	}
}

//--------------------------------------------------------------------------

if ($Mode == 'Delete') {

	if (payroll_account_used($selected_id)) {
		display_error(_("Cannot delete this account because payroll rules have been created using it."));
	}
	else {
		delete_payroll_account($selected_id);
		display_notification(_('Selected account has been deleted'));
	}
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
	$selected_id = $_POST['AccountId'] = '';

//--------------------------------------------------------------------------

$result = get_payroll_accounts();

start_form();
start_table(TABLESTYLE2);
$th = array(_('Account Code'), _('Account Name'), "");

table_header($th);

$k = 0; 
while ($myrow = db_fetch($result)) {

	alt_table_row_color($k);

	label_cell($myrow["account_code"]);
	label_cell($myrow["account_name"]);
 	delete_button_cell("Delete".$myrow["account_id"], _("Delete"));
    
	end_row();
}

end_table(1);

//--------------------------------------------------------------------------

start_table(TABLESTYLE_NOBORDER);

gl_all_accounts_list_cells(null, 'AccountId', null, false, false,
		_('Select account'), true, check_value('show_inactive'));
	check_cells(_("Show inactive:"), 'show_inactive', null, true);

end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();

end_page();