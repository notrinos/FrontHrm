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

function write_employee_bank_transaction($trans_type, $trans_no, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id,	$ref, $memo_, $use_transaction=true, $settled_amount=null, $payslip_no, $emp_id) {
	global $Refs, $SysPrefs;

	if ($trans_type != ST_BANKPAYMENT && $trans_type != ST_BANKDEPOSIT)
		display_db_error('Invalid type ($trans_type) sent to add_bank_transaction');

	$do_exchange_variance = false;
	$exchanged = false;
	if ($use_transaction)
		begin_transaction();

	db_query("SET sql_mode = ''");

	$args = func_get_args(); if (count($args) < 13) $args[] = true;

	$args = (object)array_combine(array('trans_type', 'trans_no', 'from_account', 'items', 'date_', 'person_type_id', 'person_id', 'person_detail_id', 'ref', 'memo_', 'use_transaction', 'settled_amount', 'payslip_no', 'emp_id'), $args);

	hook_db_prewrite($args, $trans_type);

	$aid = 0;
	if ($trans_no) {
		$old_trans = $trans_no;
		$Refs->restore_last($trans_type, $trans_no);
		$aid = has_attachment($trans_type, $trans_no);
	}
	else
		$old_trans = false;

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);

	// the gl items are already inversed/negated for type 2 (deposit)
	$total_amount = $items->gl_items_total();

    
   	$trans_no = get_next_trans_no($trans_type);
   	$do_exchange_variance = $SysPrefs->auto_currency_revaluation();
    if ($do_exchange_variance)
    	$trans_no1 = get_next_trans_no(ST_JOURNAL);

	if ($aid != 0) {
		$row = get_attachment($aid);
		update_attachment($aid, $row['type_no'], $trans_no, $row['description'], $row['filename'], $row['unique_name'], $row['filesize'], $row['filetype']);
	}
	// do the source account postings

    add_bank_trans($trans_type, $trans_no, $from_account, $ref, $date_, -$total_amount, $person_type_id, $person_id, $currency, "Cannot insert a source bank transaction");
	$total = 0;
	foreach ($items->gl_items as $gl_item) {
		$is_bank_to = is_bank_account($gl_item->code_id);

		if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
			display_db_error(_('invalid payment entered. Cannot pay to another bank account'), "");

    	// do the destination account postings
    	$total += add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id, $gl_item->dimension_id, $gl_item->dimension2_id, $gl_item->reference, $gl_item->amount, $currency, $person_type_id, $person_id);

    	if ($is_bank_to) {
    		add_bank_trans($trans_type, $trans_no, $is_bank_to, $ref, $date_, $gl_item->amount, $person_type_id, $person_id, $currency, "Cannot insert a destination bank transaction");

    		if ($do_exchange_variance)
    			add_exchange_variation($trans_no1, $date_, $is_bank_to, $gl_item->code_id, $currency, $person_type_id, $person_id);
    	}
		// store tax details if the gl account is a tax account

		$amount = $gl_item->amount;
		$ex_rate = get_exchange_rate_from_home_currency($currency, $date_);

		add_gl_tax_details($gl_item->code_id, $trans_type, $trans_no, -$amount, $ex_rate, $date_, $memo_);
	}

	// do the source account postings
    add_gl_trans($trans_type, $trans_no, $date_, $bank_gl_account, 0, 0, $memo_, -$total, null, $person_type_id, $person_id);

    if ($do_exchange_variance) {
    	if ($exchanged || add_exchange_variation($trans_no1, $date_, $from_account, $bank_gl_account, $currency, $person_type_id, $person_id)) {
   			$ref1 = $Refs->get_next(ST_JOURNAL, null, $date_);
			$Refs->save(ST_JOURNAL, $trans_no1, $ref1);
			add_audit_trail(ST_JOURNAL, $trans_no1, $date_);
		}
	}

	//$total = $cart->gl_items_total_debit();
    
    if($trans_type == ST_BANKPAYMENT) {

	    add_employee_trans($trans_no, ST_BANKPAYMENT, $payslip_no, $date_, $person_id, $total);

	    $trans_counter = db_insert_id();

	    if(isset($_GET['NewAdvance']) || !empty($_POST['NewAdvance']))
	        add_employee_advance($trans_counter, $emp_id);
    }

	add_comments($trans_type, $trans_no, $date_, $memo_);

	$Refs->save($trans_type, $trans_no, $ref);
	add_audit_trail($trans_type, $trans_no, $date_);

	// old transaction can be voided only after new transaction is entered,
	//  otherwise the operation could fail for cash accounts due to temporary negative balance
	if ($old_trans) {
		$msg = void_transaction($trans_type, $old_trans, Today(), _("Document reentered."));
		if ($msg){
			display_error($msg);
			return false;
		}
	}

	$args->trans_no = $trans_no;
	hook_db_postwrite($args, $trans_type);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no, $trans_counter);
}

//--------------------------------------------------------------------------

function add_employee_trans($trans_id, $type, $payslip_no, $date, $source_ref, $pay_amount) {

	$sql = "INSERT INTO ".TB_PREF."employee_trans(trans_no, trans_type, payslip_no, pay_date, to_the_order_of, pay_amount) VALUES (".db_escape($trans_id).", ".db_escape($type).", ".db_escape($payslip_no).", '".date2sql($date)."', ".db_escape($source_ref).", ".db_escape($pay_amount).")";

	db_query($sql, _('employee trans could not be inserted'));
}

//--------------------------------------------------------------------------

function has_payment_advice($payslip_no) {
	return check_empty_result("SELECT id FROM ".TB_PREF."employee_trans WHERE payslip_no = ".db_escape($payslip_no));
}

//--------------------------------------------------------------------------

function add_employee_advance($emp_trans_no, $emp_id) {
	$sql = "INSERT INTO ".TB_PREF."employee_advance (emp_trans_no, emp_id) VALUES (".db_escape($emp_trans_no).",".db_escape($emp_id).")";

	db_query($sql, _('could not insert employee advance data'));
}

//--------------------------------------------------------------------------

function get_employee_advances($emp_id) {
	$sql = "SELECT t.id, t.trans_no, t.trans_type, t.pay_date, t.pay_amount, SUM(IFNULL(al.amount,0)) AS amt_allocated, (t.pay_amount - SUM(IFNULL(al.amount,0))) AS remain FROM ".TB_PREF."employee_trans t LEFT JOIN ".TB_PREF."employee_advance_allocation al ON al.trans_no_to = t.id LEFT JOIN ".TB_PREF."employee_advance a ON a.emp_trans_no = t.id WHERE a.emp_id = ".db_escape($emp_id)." GROUP BY trans_no, trans_type HAVING remain > 0";

	return db_query($sql, _('could not get employee advance data'));
}

//--------------------------------------------------------------------------

function add_employee_allocations($trans=null, $allocs = array()) {

    if(count($allocs)) {
	    foreach($allocs as $trans_to => $amt) {
            $sql = "INSERT INTO ".TB_PREF."employee_advance_allocation (trans_no_from, trans_no_to, amount) VALUES (".db_escape($trans).", ".db_escape($trans_to).", ".db_escape($amt).")";
            db_query($sql, _('could not add employee allocation'));
        }
    }
}