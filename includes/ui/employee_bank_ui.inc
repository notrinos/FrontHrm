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

function employee_bank_header(&$order) {
	global $Ajax, $Payable_act;

	$payment = $order->trans_type == ST_BANKPAYMENT;

	div_start('pmt_header');

	start_outer_table(TABLESTYLE2, "width='90%'");

	table_section(1);
	
    date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);

	ref_row(_("Reference:"), 'ref', '', $order->reference, false, $order->trans_type, get_post('date_'));

	table_section(2, "33%");

	if(!isset($_POST['PayType'])) {

		if(isset($_GET['PayType']))
			$_POST['PayType'] = $_GET['PayType'];
		else
			$_POST['PayType'] = "";
	}
	if(!isset($_POST['person_id'])) {

		if(isset($_GET['PayPerson']))
			$_POST['person_id'] = $_GET['PayPerson'];
		else
			$_POST['person_id'] = "";
	}
	if(isset($_POST['_PayType_update'])) {
		$_POST['person_id'] = '';
		$Ajax->activate('pmt_header');
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('footer');
	}
    hidden('PayType');
    employee_list_row(_("Pay To:")."&nbsp;", 'emp_id', null, _("Select Employee"), true, false, false);

    switch($_POST['PayType']) {
		case PT_MISC :
    		text_row_ex($payment ?_("To the Order of:"):_("Name:"), 'person_id', 40, 50);
    		break;
    }

	table_section(3, "33%");

	if(!$order->order_id && !get_post('bank_account')) {

		if($_POST['PayType'] == PT_CUSTOMER)
			$_POST['bank_account'] = get_default_customer_bank_account($_POST['person_id']);
		elseif($_POST['PayType'] == PT_SUPPLIER)	
			$_POST['bank_account'] = get_default_supplier_bank_account($_POST['person_id']);
		else
			unset($_POST['bank_account']);
	}		
    
    bank_accounts_list_row( $payment ? _("From:") : _("Into:"), 'bank_account', null, true);
    if($payment)
		bank_balance_row($_POST['bank_account']);

	if(!isset($_GET['PaymentAdvice']) && !isset($_POST['PaymentAdvice']) && !isset($_GET['NewAdvance']) && empty($_POST['NewAdvance']))
        employee_payslip_list_row(_("Pay Slip:"), 'for_payslip', null, get_post('emp_id'), _("Select payslip"), true);
    if(isset($_GET['NewAdvance']) || !empty($_POST['NewAdvance'])) {
        amount_row(_('Amount:').'&nbsp;', 'advance_amount', null, null, null, null, true);
        hidden('for_payslip', null);
    }

    hidden('NewAdvance');

	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	exchange_rate_display(get_company_currency(), $bank_currency, $_POST['date_']);

	if(list_updated('emp_id')) {

		$order->clear_items();
		unset($_POST['totamount']);
		unset($_POST['amount']);
		unset($_POST['memo_']);
		$Ajax->activate('totamount');
		$Ajax->activate('for_payslip');
		$order->payslip_no = 0;	
	}

	if(list_updated('for_payslip')) {

		$order->clear_items();
		$_POST['memo_'] = '';
		unset($_POST['PaySlipNo']);
		$order->payslip_no = 0;	
		$order->payslip_trans_no = get_post('for_payslip');
            
        if(empty($order->payslip_trans_no))
            $payslip = null;
        else    
            $payslip = get_payslip(false, $order->payslip_trans_no);
                
		
		if($payslip) {
			$_POST['memo_'] = "Payment advice gl entry For Payslip #".$payslip['payslip_no'];

			if(list_updated('for_payslip')) {

				$order->payslip_trans_no = $payslip['trans_no'];
				$order->person_id = $payslip['emp_id'];
				$order->payslip_no = $_POST['PaySlipNo'] = $payslip['payslip_no'];
			}
                
			$pay_amt = $payslip['payable_amount'];

			$bank_ac_code = get_bank_gl_account(get_post('bank_account'));
	
			$order->add_gl_item($Payable_act, 0, 0, $pay_amt, '');
		}

		$Ajax->activate('items_table');
	}

	end_outer_table(1);

	div_end();
}

//--------------------------------------------------------------------------

function display_bank_gl_items($title, &$order) {
	global $path_to_root;

	$dim = get_company_pref('use_dimension');
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	display_heading($title);

    div_start('items_table');
	start_table(TABLESTYLE, "width='95%'");

	if($dim == 2)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1", _("Dimension")." 2", _("Amount"), _("Memo"), "");
	elseif($dim == 1)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension"), _("Amount"), _("Memo"), "");
	else
		$th = array(_("Account Code"), _("Account Description"), _("Amount"), _("Memo"), "");

	if(count($order->gl_items)) $th[] = '';

	table_header($th);
	$k = 0;

	$id = find_submit('Edit');
	foreach ($order->gl_items as $line => $item) {

		if($id != $line) {
    		alt_table_row_color($k);

			label_cell($item->code_id);
			label_cell($item->description);
    		if($dim >= 1)
				label_cell(get_dimension_string($item->dimension_id, true));
    		if($dim > 1)
				label_cell(get_dimension_string($item->dimension2_id, true));
			if($order->trans_type == ST_BANKDEPOSIT)
				amount_cell(-$item->amount, false, '', 'item_amount');
			else		
				amount_cell($item->amount, false, '', 'item_amount');
			label_cell($item->reference);

			edit_button_cell("Edit$line", _("Edit"), _('Edit document line'));
			delete_button_cell("Delete$line", _("Delete"), _('Remove line from document'));
    		end_row();
		}
		else
			gl_edit_item_controls($order, $dim, $line);
	}

	if($order->count_gl_items())
		label_row(_("Total"), number_format2(abs($order->gl_items_total()), user_price_dec()), "colspan=" . $colspan . " align=right", "align=right id='payment_total_amt'", 3);

    end_table();
	div_end();
}

//--------------------------------------------------------------------------

function show_employee_advances($emp_id) {

	global $systypes_array;
	
	$k = $total_allocated = 0;
    $advances = get_employee_advances($emp_id);

	if(db_num_rows($advances) > 0) {
		display_heading(sprintf(_("Allocated amounts in %s:"), get_company_pref('curr_default')));
		start_table(TABLESTYLE, "width='60%'");
   		$th = array(_('Transaction Type'), _('#'), _('Ref'), _('Date'), _('Amount'), _('Other Allocations'), _('Left to Allocate'), _('This Allocation'),'','');

	   	table_header($th);

		foreach($advances as $row) {
		    $id = $row['id'];

		    if(floatcmp(abs($row['pay_amount']), $row['amt_allocated'])) {
				alt_table_row_color($k);
    			label_cell($systypes_array[$row['trans_type']]);
	   			label_cell(get_trans_view_str($row['trans_type'], $row['trans_no']), "nowrap align='right'");
		   		label_cell(get_reference($row['trans_type'], $row['trans_no']));
    			label_cell(sql2date($row['pay_date']), "align='center'");
	    		amount_cell(abs($row['pay_amount']));
				amount_cell($row['amt_allocated']);

	    		$un_allocated = round((abs($row['pay_amount']) - $row['amt_allocated']), 6);
	    		amount_cell($un_allocated, false,'', 'maxval'.$id);
    			amount_cells(null, "amount" . $id);
				label_cell("<a href='#' name=Alloc$id onclick='emp_allocate_all(this.name.substr(5));return true;'>"._("All")."</a>");
				label_cell("<a href='#' name=DeAll$id onclick='emp_allocate_none(this.name.substr(5));return true;'>"._("None")."</a>".hidden("un_allocated" . $id, price_format($un_allocated), false));
				end_row();

   	    		$total_allocated += input_num('amount' . $id);
		   	}
		}
		label_row(_('Total Amount:'), get_post('amount'), "colspan='7' align='right'", "id='amount' align='right'");
		hidden('amount');
		end_table(1);
	}
	hidden('TotalNumberOfAllocs', count_array($advances));
}