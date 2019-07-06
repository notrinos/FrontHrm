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

$path_to_root = '../../..';
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");

$page_security = isset($_GET['NewPayment']) || @($_SESSION['pay_items']->trans_type == ST_BANKPAYMENT) ? 'SA_PAYMENT' : 'SA_DEPOSIT';

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');
include_once($path_to_root . '/reporting/includes/reporting.inc');
include_once($path_to_root . "/admin/db/attachments_db.inc");

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
add_js_file('payalloc.js');
add_js_ufile($path_to_root.'/modules/FrontHrm/js/emp_payalloc.js');

if (isset($_GET['NewPayment'])) {
	$_SESSION['page_title'] = _($help_context = "Employee Payment Entry");
	create_cart(ST_BANKPAYMENT, 0);
}
if(isset($_GET['NewAdvance'])) {
	$_POST['NewAdvance'] = $_GET['NewAdvance'];
	$_SESSION['page_title'] = _($help_context = "Employee Advance Entry");
	create_cart(ST_BANKPAYMENT, 0);
}
elseif(isset($_GET['NewDeposit'])) {
	$_SESSION['page_title'] = _($help_context = "Employee Deposit Entry");
	create_cart(ST_BANKDEPOSIT, 0);
}
elseif(isset($_GET['ModifyPayment'])) {
	$_SESSION['page_title'] = _($help_context = "Modify Bank Account Entry")." #".$_GET['trans_no'];
	create_cart(ST_BANKPAYMENT, $_GET['trans_no']);
}
elseif(isset($_GET['ModifyDeposit'])) {
	$_SESSION['page_title'] = _($help_context = "Modify Bank Deposit Entry")." #".$_GET['trans_no'];
	create_cart(ST_BANKDEPOSIT, $_GET['trans_no']);
}

page($_SESSION['page_title'], false, false, '', $js);

if (isset($_GET['PayslipNo'])) {

	if(has_payment_advice($_GET['PayslipNo'])) {

		display_error(_('Payment advice already exists'));
		hyperlink_params($path_to_root.'/modules/FrontHrm/manage/employee_bank_entry.php',_('Payment Advices'), 'NewPayment=yes');
		display_footer_exit();
	}
	elseif(get_payslip(false, $_GET['PayslipNo'])['payslip_no'] == null) {
		display_error(_('Payslip number does not exist'));
		hyperlink_params($path_to_root.'/modules/FrontHrm/manage/employee_bank_entry.php',_('Payment Advices'), 'NewPayment=yes');
		display_footer_exit();
	}
    else {
		$payslip = get_payslip(false, $_GET['PayslipNo']);
		$_POST['PaySlipNo'] = $payslip['payslip_no'];
	}

	create_cart(ST_BANKPAYMENT, 0, $payslip);
}

//--------------------------------------------------------------------------------------------------

check_db_has_bank_accounts(_('There are no bank accounts defined in the system.'));

if (isset($_GET['ModifyDeposit']) || isset($_GET['ModifyPayment']))
	check_is_editable($_SESSION['pay_items']->trans_type, $_SESSION['pay_items']->order_id);

//--------------------------------------------------------------------------------------------------

if (list_updated('PersonDetailID')) {
	$br = get_branch(get_post('PersonDetailID'));
	$_POST['person_id'] = $br['debtor_no'];
	$Ajax->activate('person_id');
}

//--------------------------------------------------------------------------------------------------

function line_start_focus() {
	global $Ajax;
	$Ajax->activate('items_table');
	$Ajax->activate('footer');
	set_focus('_code_id_edit');
}

//--------------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_BANKPAYMENT;
	$payslip_no = get_payslip_from_advice($trans_no)['payslip_no'];

   	display_notification_centered(sprintf(_('Employee Payment Advice #%d has been entered'), $trans_no));
    
    if($payslip_no)
   	    display_note(hrm_print_link($payslip_no, _('Print this Payslip'), true, ST_PAYSLIP, false, '', '', 0));br();

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Postings for this Payment")));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another &Payment"), 'NewPayment=yes');

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter A &Deposit"), 'NewDeposit=yes');

	hyperlink_params($path_to_root.'/admin/attachments.php', _('Add an Attachment'), "filterType=$trans_type&trans_no=$trans_no");

	display_footer_exit();
}

if (isset($_GET['UpdatedID'])) {
	$trans_no = $_GET['UpdatedID'];
	$trans_type = ST_BANKPAYMENT;

   	display_notification_centered(sprintf(_("Payment %d has been modified"), $trans_no));

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Postings for this Payment")));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another &Payment"), 'NewPayment=yes');

	hyperlink_params($_SERVER['PHP_SELF'], _('Enter A &Deposit'), 'NewDeposit=yes');

	display_footer_exit();
}

if (isset($_GET['AddedDep'])) {
	$trans_no = $_GET['AddedDep'];
	$trans_type = ST_BANKDEPOSIT;

   	display_notification_centered(sprintf(_("Deposit %d has been entered"), $trans_no));

	display_note(get_gl_view_str($trans_type, $trans_no, _('View the GL Postings for this Deposit')));

	hyperlink_params($_SERVER['PHP_SELF'], _('Enter Another Deposit'), 'NewDeposit=yes');

	hyperlink_params($_SERVER['PHP_SELF'], _('Enter A Payment'), 'NewPayment=yes');

	display_footer_exit();
}
if (isset($_GET['UpdatedDep'])) {
	$trans_no = $_GET['UpdatedDep'];
	$trans_type = ST_BANKDEPOSIT;

   	display_notification_centered(sprintf(_("Deposit %d has been modified"), $trans_no));

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Postings for this Deposit")));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another &Deposit"), 'NewDeposit=yes');

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter A &Payment"), 'NewPayment=yes');

	display_footer_exit();
}

//--------------------------------------------------------------------------------------------------

function create_cart($type, $trans_no, $payslip=array()) {
	global $Refs, $Payable_act;

	if (isset($_SESSION['pay_items']))
		unset ($_SESSION['pay_items']);

	$cart = new items_cart($type);
    $cart->order_id = $trans_no;

	if ($trans_no) {

		$bank_trans = db_fetch(get_bank_trans($type, $trans_no));
		$_POST['bank_account'] = $bank_trans['bank_act'];
		$_POST['PayType'] = $bank_trans['person_type_id'];
		$cart->reference = $bank_trans['ref'];

		if ($bank_trans['person_type_id'] == PT_MISC)
			$_POST['person_id'] = $bank_trans['person_id'];

		$cart->memo_ = get_comments_string($type, $trans_no);
		$cart->tran_date = sql2date($bank_trans['trans_date']);

		$cart->original_amount = $bank_trans['amount'];
		$result = get_gl_trans($type, $trans_no);
		if ($result) {
			while ($row = db_fetch($result)) {
				if (is_bank_account($row['account']))
					$ex_rate = $bank_trans['amount'] / $row['amount'];
				else
					$cart->add_gl_item( $row['account'], $row['dimension_id'], $row['dimension2_id'], $row['amount'], $row['memo_']);
				
			}
		}
		foreach($cart->gl_items as $line_no => $line)
			$cart->gl_items[$line_no]->amount *= $ex_rate;
	}
	else {
		$cart->reference = $Refs->get_next($cart->trans_type, null, $cart->tran_date);
		$cart->tran_date = new_doc_date();
		if (!is_date_in_fiscalyear($cart->tran_date))
			$cart->tran_date = end_fiscalyear();
	}

	if($payslip && count($payslip) > 0) {

		$cart->person_id = $payslip['emp_id'];
		$cart->payslip_no = $payslip['payslip_no'];
        $_POST['emp_id'] = $cart->person_id;
        $_POST['for_payslip'] = $cart->payslip_no;
        $cart->memo_ = _('Payment advice gl entry For Payslip #').$cart->payslip_no;

		$pay_amt = $payslip['payable_amount'];

		$bank = get_default_bank_account();

		$_POST['bank_account'] = $bank['id'];
	
		$cart->add_gl_item($Payable_act, 0, 0, $pay_amt, '');
	}

	$_POST['memo_'] = $cart->memo_;
	$_POST['ref'] = $cart->reference;
	$_POST['date_'] = $cart->tran_date;

	$_SESSION['pay_items'] = &$cart;
}

//-----------------------------------------------------------------------------------------------

function check_trans() {
	global $Refs;

	$input_error = 0;

	if($_SESSION['pay_items']->count_gl_items() < 1) {
		display_error(_('You must enter at least one payment line.'));
		set_focus('code_id');
		$input_error = 1;
	}
	if($_SESSION['pay_items']->gl_items_total() == 0.0) {
		display_error(_('The total bank amount cannot be 0.'));
		set_focus('code_id');
		$input_error = 1;
	}

	$limit = get_bank_account_limit($_POST['bank_account'], $_POST['date_']);

	$amnt_chg = -$_SESSION['pay_items']->gl_items_total()-$_SESSION['pay_items']->original_amount;

	if($limit !== null && floatcmp($limit, -$amnt_chg) < 0) {
		display_error(sprintf(_("The total bank amount exceeds allowed limit (%s)."), price_format($limit-$_SESSION['pay_items']->original_amount)));
		set_focus('code_id');
		$input_error = 1;
	}
	if($trans = check_bank_account_history($amnt_chg, $_POST['bank_account'], $_POST['date_'])) {

		display_error(sprintf(_("The bank transaction would result in exceed of authorized overdraft limit for transaction: %s #%s on %s."),
			$systypes_array[$trans['type']], $trans['trans_no'], sql2date($trans['trans_date'])));
		set_focus('amount');
		$input_error = 1;
	}
	if(!check_reference($_POST['ref'], $_SESSION['pay_items']->trans_type, $_SESSION['pay_items']->order_id)) {
		set_focus('ref');
		$input_error = 1;
	}
	if(!is_date($_POST['date_'])) {
		display_error(_('The entered date for the payment is invalid.'));
		set_focus('date_');
		$input_error = 1;
	}
	elseif(!is_date_in_fiscalyear($_POST['date_'])) {
		display_error(_('The entered date is out of fiscal year or is closed for further data entry.'));
		set_focus('date_');
		$input_error = 1;
	} 
	if(!get_post('for_payslip') && !get_post('NewAdvance')) {
		display_error(_('You have to select a payslip.'));
		set_focus('for_payslip');
		$input_error = 1;
	}
	if(!get_post('emp_id')) {
		display_error(_('You have to select an employee.'));
		set_focus('emp_id');
		$input_error = 1;
	}
	if(!db_has_currency_rates(get_bank_account_currency($_POST['bank_account']), $_POST['date_'], true))
		$input_error = 1;

	if(isset($_POST['settled_amount']) && in_array(get_post('PayType'), array(PT_SUPPLIER, PT_CUSTOMER)) && (input_num('settled_amount') <= 0)) {
		display_error(_('Settled amount have to be positive number.'));
		set_focus('person_id');
		$input_error = 1;
	}
	if(isset($_POST['amount']) && $_POST['amount'] > $_SESSION['pay_items']->gl_items_total()) {
		display_error(_('Payment cannot be processed because the amount allocated is more than the total payslip amount'));
		set_focus('amount');
		$input_error = 1;
	}
	return $input_error;
}

if(isset($_POST['update_advances'])) {
	if(input_num('advance_amount') <= 0) {
		display_error(_('Pay amount have to be positive number.'));
		set_focus('advance_amount');
	}
	elseif(!get_post('emp_id')) {
		display_error(_('You have to select an employee.'));
		set_focus('emp_id');
	}
	else {
		$_SESSION['pay_items']->clear_items();
		$_SESSION['pay_items']->add_gl_item($Payable_act, 0, 0, input_num('advance_amount'), '');
		$Ajax->activate('_page_body');
	}
}

if (isset($_POST['Process']) && !check_trans()) {
	begin_transaction();

	$_SESSION['pay_items'] = &$_SESSION['pay_items'];
	$new = $_SESSION['pay_items']->order_id == 0;

	add_new_exchange_rate(get_bank_account_currency(get_post('bank_account')), get_post('date_'), input_num('_ex_rate'));

	$allocs = array();
	foreach ($_POST as $k => $v) {
		if(strlen($k) > 6 && substr($k, 0, 6) == 'amount' && $v > 0)
			$allocs[substr($k,6)] = $v;
	}

	if(@$_POST['amount'] >= $_SESSION['pay_items']->gl_items_total()) {
		add_employee_trans(0, ST_BANKPAYMENT, $_POST['for_payslip'], $_POST['date_'], $_POST['person_id'], 0);
		$id_counter = db_insert_id();
		add_employee_allocations($id_counter, $allocs);
		
		commit_transaction();
		display_notification(_('Employee advances have been allocated, no bank payment has been made.'));
		$Ajax->activate('_page_body');
	}
    else {
        
        if(input_num('amount') > 0) {
        	$old_amt = $_SESSION['pay_items']->gl_items_total();
        	$this_alloc = input_num('amount');
            $_SESSION['pay_items']->clear_items();
    	    $_SESSION['pay_items']->add_gl_item($Payable_act, 0, 0, $old_amt - $this_alloc, '');
        }

        $trans = write_employee_bank_transaction($_SESSION['pay_items']->trans_type, $_SESSION['pay_items']->order_id, $_POST['bank_account'], $_SESSION['pay_items'], $_POST['date_'], $_POST['PayType'], $_POST['person_id'], get_post('PersonDetailID'), $_POST['ref'], $_POST['memo_'], true, input_num('settled_amount', null), $_POST['for_payslip'], $_POST['emp_id']);

	    $trans_type = $trans[0];
   	    $trans_no = $trans[1];

   	    if(!empty($trans[2]) && count($allocs)) {
   	    	$trans_counter = $trans[2];
   	        add_employee_allocations($trans_counter, $allocs);
   	    }
   	    
	    new_doc_date($_POST['date_']);

	    $_SESSION['pay_items']->clear_items();
	    unset($_SESSION['pay_items']);

	    commit_transaction();

	    if($new)
		    meta_forward($_SERVER['PHP_SELF'], $trans_type==ST_BANKPAYMENT ? "AddedID=$trans_no" : "AddedDep=$trans_no");
	    else
		    meta_forward($_SERVER['PHP_SELF'], $trans_type==ST_BANKPAYMENT ? "UpdatedID=$trans_no" : "UpdatedDep=$trans_no");
    }
}

//-----------------------------------------------------------------------------------------------

function check_item_data() {

	if(!check_num('amount', 0)) {
		display_error( _('The amount entered is not a valid number or is less than zero.'));
		set_focus('amount');
		return false;
	}
	if(isset($_POST['_ex_rate']) && input_num('_ex_rate') <= 0) {
		display_error( _('The exchange rate cannot be zero or a negative number.'));
		set_focus('_ex_rate');
		return false;
	}

	return true;
}

//-----------------------------------------------------------------------------------------------

function handle_update_item() {

	$amount = ($_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ? 1:-1) * input_num('amount');

    if($_POST['UpdateItem'] != "" && check_item_data())
    	$_SESSION['pay_items']->update_gl_item($_POST['Index'], $_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount , $_POST['LineMemo']);
    
	line_start_focus();
}

function handle_delete_item($id) {
	$_SESSION['pay_items']->remove_gl_item($id);
	line_start_focus();
}

function handle_new_item() {
	if (!check_item_data())
		return;
	$amount = ($_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ? 1:-1) * input_num('amount');
	$_SESSION['pay_items']->add_gl_item($_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

$id = find_submit('Delete');
if($id != -1)
	handle_delete_item($id);
if(isset($_POST['AddItem']))
	handle_new_item();
if(isset($_POST['UpdateItem']))
	handle_update_item();
if(isset($_POST['CancelItemChanges']))
	line_start_focus();
if(isset($_POST['go'])) {
	display_quick_entries($_SESSION['pay_items'], $_POST['person_id'], input_num('totamount'), $_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ? QE_PAYMENT : QE_DEPOSIT);
	$_POST['totamount'] = price_format(0); $Ajax->activate('totamount');
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

start_form();

employee_bank_header($_SESSION['pay_items']);

if(isset($_GET['NewAdvance']) || !empty($_POST['NewAdvance'])) {
    submit_center('update_advances', _('Generate Payment Items'), true, _('Generate Payment GL Items'), true, 'default');
    br();
}

if(!empty($_POST['emp_id']) && empty($_POST['NewAdvance']))
    show_employee_advances($_POST['emp_id']);

start_table(TABLESTYLE2, "width='90%'", 10);
start_row();
echo "<td>";
if($_SESSION['pay_items']->count_gl_items() > 0)
    display_bank_gl_items($_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ? _("Payment Items"):_("Deposit Items"), $_SESSION['pay_items']);
gl_options_controls($_SESSION['pay_items']);
echo "</td>";
end_row();
end_table(1);

submit_center_first('Update', _("Update"), '', null);
if($_SESSION['pay_items']->count_gl_items() > 0)
    submit_center_last('Process', $_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ? _("Process Payment"):_("Process Deposit"), '', 'default');

end_form();
end_page();