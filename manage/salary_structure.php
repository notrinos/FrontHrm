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
$path_to_root = '../../..';

include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

//--------------------------------------------------------------------------

page(_($help_context = 'Manage Salary Structure'), false, false, '', $js); 

$selected_id = get_post('position_id','');

function can_process($selected_id) {
    
	if(!$selected_id) {
        
		display_error(_('Select job position'));
		set_focus('position_id');
		return false;
	} 

	foreach($_POST as $p=>$val) {

		if(substr($p, 0, 7) == 'Account') {

			if(input_num('Debit'.$val) && input_num('Credit'.$val)) {
				display_error(_('Only one amount(Earning or Deduction) is allowed per rule'));
				set_focus('Debit'.$val);
				return false;
			}
		}
	}
	return true;
}

//--------------------------------------------------------------------------

function handle_submit(&$selected_id) {
	global $Ajax;

	if(!can_process($selected_id))
		return;

	$payroll_rules = array();
	foreach($_POST as $p=>$val) {
		if(substr($p, 0, 7) == 'Account') {

			if(input_num('Debit'.$val) > 0) {
				$type = DEBIT;
				$amount = @input_num('Debit'.$val);
			}
            else {
				$type = CREDIT;
				$amount = @input_num("Credit".$val);
			}

			if($amount > 0)
				$payroll_rules[] = array(
					'position_id' => $selected_id,
					'grade_id' => get_post('_tabs_sel'),
					'pay_rule_id' => $val,
					'pay_amount' => $amount,
					'type' => $type
				);
		}
	}
	
	delete_salary_structure($selected_id, get_post('_tabs_sel'));
	add_salary_structure($payroll_rules);
	
	display_notification(_('Salary structure has been updated.'));
	$Ajax->activate('_page_body');
}

//--------------------------------------------------------------------------

if (isset($_POST['submit']))
	handle_submit($selected_id);

if (isset($_POST['delete'])) {
	delete_salary_structure($selected_id);
	display_notification(_('Selected structure has been deleted.'));
	$_POST['position_id'] = $selected_id = '';
	$Ajax->activate('_page_body');
}

//--------------------------------------------------------------------------

function payroll_rules_settings($selected_id, $grade_id=0) {
    global $USE_DEPT_ACC;

	$new = true;
	$rules = array();
    $basic_salary = '';
	$payroll_structure = get_payroll_structure($selected_id);
	$pay_basis = get_position($selected_id)['pay_basis'];

	foreach(get_salary_structure($selected_id, $grade_id) as $row) {
		if($row['is_basic'] == 1)
            $basic_salary = $row;
	}

	if($payroll_structure) {

		foreach($payroll_structure['payroll_rule'] as $code) {
			$ac = get_gl_account($code);
			$pay_element = get_payroll_elements(false, $code);
			$rules[] = array(
				'account_input' => 'Account'.$code,
				'debit_input' 	=> 'Debit'.$code,
				'credit_input'	=> 'Credit'.$code,
				'account_code'	=> $code,
				'account_name'	=> $ac['account_name'],
				'element_name'  => $pay_element['element_name'],
            );
			$_POST['Debit'.$code] = price_format(0);
			$_POST['Credit'.$code] = price_format(0);
		}

		$rsStr = get_salary_structure($selected_id, $grade_id);
        
		if(db_num_rows($rsStr) > 0) {
            $new = false;

			while($rowStr = db_fetch($rsStr)) {
                
				if($rowStr['type'] == DEBIT)
					$_POST['Debit'.$rowStr['pay_rule_id']] = price_format($rowStr['pay_amount']);
                else 
					$_POST['Credit'.$rowStr['pay_rule_id']] = price_format($rowStr['pay_amount']);
			}
		}

		br();
		start_table(TABLESTYLE2);
		if($pay_basis == MONTHLY_SALARY)
		    $th = array(_('Pay Element'), _('Monthly Earnings'),_('Monthly Deductions'));
		if($pay_basis == DAILY_WAGE)
			$th = array(_('Pay Element'), _('Daily Earnings'),_('Daily Deductions'));

		table_header($th);
        start_row("class='inquirybg'");
        label_cell(_('Basic Salary'));
        amount_cell(@$basic_salary['pay_amount']);
        amount_cell('0');
        end_row();

		foreach($rules as $rule) {			
			start_row();
			hidden($rule['account_input'],$rule['account_code']);
			label_cell($rule['element_name']);
			amount_cells(null, $rule['debit_input']);
			amount_cells(null, $rule['credit_input']);
			end_row();
		}
		end_table(1);

		div_start('controls');
        
        if($new)
            submit_center('submit', _('Save salary structure'), true, '', 'default');
        else {
            submit_center_first('submit', _('Update'), _('Update salary structure data'), 'default');
            submit_center_last('delete', _("Delete"), _('Delete salary structure if have been never used'), false, ICON_DELETE);
        }
		div_end();
    }
    else
		display_error(_('Payroll rules not defined for this job position'));
	br();
}

//--------------------------------------------------------------------------
 
start_form();

if(db_has_position()) {
	start_table(TABLESTYLE2);
	position_list_row(_('Job Position').':', 'position_id', null, _('Select Job Position'), true);
	end_table();
} 
else {
	hidden('position_id');
	display_note(_('Before you can run this function Job Positions must be defined and add Payroll Rules to them.'));
}

$tabs = array(0 => array(_('Basic'), 999));
$grades = get_company_pref('payroll_grades');
for($i=1; $i<=$grades; $i++)
	$tabs[$i] = array(_('Grade ').$i, 999);

tabbed_content_start('tabs', $tabs);

if($selected_id) {
	if(grade_exist(get_post('_tabs_sel'), $selected_id) || get_post('_tabs_sel') == 0)
		payroll_rules_settings($selected_id, get_post('_tabs_sel'));
	else
		display_note(_('Please define grade amount for the selected job position first.'), 1, 1);
}

tabbed_content_end();

hidden('popup', @$_REQUEST['popup']);
end_form(1);
end_page();