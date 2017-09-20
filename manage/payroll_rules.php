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

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_db.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_ui.inc");

//--------------------------------------------------------------------------

$selected_id = get_post('SalaryScaleId', '');

function handle_submit(&$selected_id) {
	global $Ajax;
		
	if ($selected_id) {	

		$payrule = array();
		foreach($_POST as $p => $val) {
            
			if (substr($p, 0, 7) == 'Payroll') {

				$a = substr($p, 7);

				if($val == 1 || payroll_rule_used($selected_id, $a))
					$payrule[] = (int)$a;
			}
		}
		if(payroll_rule_exist($selected_id) && count($payrule) > 0)
			update_payroll_rule($selected_id, $payrule);
		else if(count($payrule) == 0)
			reset_payroll($selected_id);
		else
			add_payroll_rule($selected_id, $payrule);
		
		$Ajax->activate('_page_body');
		display_notification(_("Accounts have been updated, some accounts might not have been deleted because Salary Structure using them."));
	} 
	else {
		display_warning(_("Select accounts first."));		
		set_focus('SalaryScaleId');
	}
}

//----------------------------------------------------------------------------

function payroll_rule_settings($selected_id) {
	
	$new = true;
	foreach($_POST as $p => $val) {
        
		if (substr($p, 0, 7) == 'Payroll')
			$_POST[$p] = '';
	}
	
	if ($selected_id) {
        
		$payroll_structure = get_payroll_structure($selected_id);

		if($payroll_structure) {
            
			$new = false;
            
			foreach($payroll_structure['payroll_rule'] as $rule_code) {

				$_POST['Payroll'.$rule_code] = 1;
			}
		}
		$_POST['SalaryScaleId'] = $selected_id;
	}
	
	start_table(TABLESTYLE2);
	$th = array(_("Select Payroll Rules"),'');
	table_header($th);
	
	$rules = get_payroll_rules();
	
	while($rule = db_fetch($rules)) {

		check_row($rule["account_code"].' - '.$rule["account_name"], 'Payroll'.$rule["account_code"], null);
	}
    end_table(1);
    
	div_start('controls');
    
	if($new)
        submit_center('submit', _("Save"), true, _("Save payroll structure"), 'default');
	else {
		submit_center_first('submit', _("Update"), _('Update payroll rules data'), 'default');
		submit_return('select', $selected_id, _("Select this salary scale and return to document entry."));
		submit_center_last('delete', _("Delete"), _('Delete payroll rules if have been never used'), true);
    }
	div_end();
}

//----------------------------------------------------------------------------

if (isset($_POST['submit']))
    handle_submit($selected_id);

//----------------------------------------------------------------------------

if (isset($_POST['delete'])) {

	reset_payroll($selected_id);
	display_notification(_("Selected payroll rules have been deleted."));
	$_POST['SalaryScaleId'] = '';
	$selected_id = '';
	$Ajax->activate('_page_body');
}

//----------------------------------------------------------------------------

page(_($help_context = "Manage Payroll Rule"), false, false, '', $js); 

start_form();

if (db_has_salary_scale()) {
    
	start_table(TABLESTYLE_NOBORDER);
	start_row();
    
	salaryscale_list_cells(null, 'SalaryScaleId', null, _('Select salary scale'), true, check_value('show_inactive'));
	check_cells(_("Show inactive:"), 'show_inactive', null, true);
    
	end_row();
	end_table(1);	
	if (get_post('_show_inactive_update')) {
		$Ajax->activate('SalaryScaleId');
		set_focus('SalaryScaleId');
	}

	payroll_rule_settings($selected_id);
} 
else {
	hidden('SalaryScaleId');
	display_note(_('Define Salary Scales first.'));
}


hidden('popup', @$_REQUEST['popup']);

end_form();
end_page();
