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

function employee_bank_header(&$order) {
	global $Ajax, $Payable_act;

	$payment = $order->trans_type == ST_BANKPAYMENT;

	$customer_error = false;
	div_start('pmt_header');

	start_outer_table(TABLESTYLE2, "width='90%'"); // outer table

	table_section(1);
	
    date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);

	ref_row(_("Reference:"), 'ref', '', $order->reference, false, $order->trans_type, get_post('date_'));

	table_section(2, "33%");

	if (!isset($_POST['PayType'])) {
		if (isset($_GET['PayType']))
			$_POST['PayType'] = $_GET['PayType'];
		else
			$_POST['PayType'] = "";
	}
	if (!isset($_POST['person_id'])) {
		if (isset($_GET['PayPerson']))
			$_POST['person_id'] = $_GET['PayPerson'];
		else
			$_POST['person_id'] = "";
	}
	if (isset($_POST['_PayType_update'])) {
		$_POST['person_id'] = '';
		$Ajax->activate('pmt_header');
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('footer');
	}
    hidden('PayType');
    employee_list_row(_("Pay To:")."&nbsp;", 'emp_id', null, _("Select Employee"), true, false, false);

    switch ($_POST['PayType']) {
		case PT_MISC :
    		text_row_ex($payment ?_("To the Order of:"):_("Name:"), 'person_id', 40, 50);
    		break;
    }

	table_section(3, "33%");

	if (!$order->order_id && !get_post('bank_account')) {
		if ($_POST['PayType'] == PT_CUSTOMER)
			$_POST['bank_account'] = get_default_customer_bank_account($_POST['person_id']);
		elseif ($_POST['PayType'] == PT_SUPPLIER)	
			$_POST['bank_account'] = get_default_supplier_bank_account($_POST['person_id']);
		else
			unset($_POST['bank_account']);
	}		
    
    bank_accounts_list_row( $payment ? _("From:") : _("Into:"), 'bank_account', null, true);
    if ($payment)
		bank_balance_row($_POST['bank_account']);

	if(!isset($_GET['PaymentAdvice']) && !isset($_POST['PaymentAdvice']))
        employee_payslip_list_row(_("Pay Slip:"), 'for_payslip', null, get_post('emp_id'), _("Select payslip"), true);

	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	exchange_rate_display(get_company_currency(), $bank_currency, $_POST['date_']);

	if (list_updated('emp_id')) {

		$order->clear_items();
		unset($_POST['totamount']);
		$Ajax->activate('totamount');
		$Ajax->activate('for_payslip');
		$order->payslip_no = 0;	
	}

	if (list_updated('for_payslip') || list_updated('bank_account')) {

		$order->clear_items();
		$_POST['memo_'] = "";

		if(list_updated('for_payslip')) {
			unset($_POST['PaySlipNo']);
			$order->payslip_no = 0;	
			$order->payslip_trans_no = get_post('for_payslip');
		}
            
        if(!isset($order->payslip_trans_no))
            $payslip = null;
        else    
            $payslip = get_payslip(false, $order->payslip_trans_no);
                
		
		if($payslip) {
			$_POST['memo_'] = "Payment advice gl entry For Payslip #".$payslip['payslip_no'];

			if(list_updated('for_payslip')) {

				$order->payslip_trans_no = $payslip['trans_no'];
				$order->person_id = $payslip['emp_id'];
				//$Order->to_the_order_of = $payslip['to_the_order_of'];
				$order->payslip_no = $_POST['PaySlipNo'] = $payslip['payslip_no'];
			}
                
			$pay_amt = $payslip['payable_amount'];

			$bank_ac_code = get_bank_gl_account(get_post('bank_account'));
	
			$order->add_gl_item($Payable_act, 0, 0, $pay_amt, '');
		}
		$_SESSION['pay_items'] = $order;
		$Ajax->activate('items_table');
	}

	end_outer_table(1);

	div_end();
	if ($customer_error){
		end_form();
		end_page();
		exit;
	}
}

//--------------------------------------------------------------------------

function display_bank_gl_items($title, &$order) {
	global $path_to_root;

	$dim = get_company_pref('use_dimension');
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	display_heading($title);

    div_start('items_table');
	start_table(TABLESTYLE, "width='95%'");

	if ($dim == 2)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1", _("Dimension")." 2", _("Amount"), _("Memo"), "");
	elseif ($dim == 1)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension"), _("Amount"), _("Memo"), "");
	else
		$th = array(_("Account Code"), _("Account Description"), _("Amount"), _("Memo"), "");

	if (count($order->gl_items)) $th[] = '';

	table_header($th);
	$k = 0;

	$id = find_submit('Edit');
	foreach ($order->gl_items as $line => $item) {
		if ($id != $line) {
    		alt_table_row_color($k);

			label_cell($item->code_id);
			label_cell($item->description);
    		if ($dim >= 1)
				label_cell(get_dimension_string($item->dimension_id, true));
    		if ($dim > 1)
				label_cell(get_dimension_string($item->dimension2_id, true));
			if ($order->trans_type == ST_BANKDEPOSIT)
				amount_cell(-$item->amount);
			else		
				amount_cell($item->amount);
			label_cell($item->reference);

			edit_button_cell("Edit$line", _("Edit"), _('Edit document line'));
			delete_button_cell("Delete$line", _("Delete"), _('Remove line from document'));
    		end_row();
		}
		else
			gl_edit_item_controls($order, $dim, $line);
	}

	//if ($id == -1)
		//gl_edit_item_controls($order, $dim);

	if ($order->count_gl_items())
		label_row(_("Total"), number_format2(abs($order->gl_items_total()), user_price_dec()), "colspan=" . $colspan . " align=right", "align=right", 3);

    end_table();
	div_end();
}