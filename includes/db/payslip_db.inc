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

function get_next_payslip_no() {

	$sql = "SELECT MAX(payslip_no)+1 FROM ".TB_PREF."payslip";
	$result = db_query($sql, "The next payslip number could not be retreived");
    $row = db_fetch_row($result);

    return ($row[0])?$row[0]:1;
}

//--------------------------------------------------------------------------

function write_payslip(&$cart, $reverse, $use_transaction = true) {

	global $Refs;
	$date       = $cart->tran_date;
	$ref        = $cart->reference;
	$source_ref = $cart->to_the_order_of;
	$memo_      = $cart->memo_;
	$from_date  = $cart->from_date;
	$to_date    = $cart->to_date;
	$trans_type = $cart->trans_type;
	$payslip_no = $cart->payslip_no;
	$new = $cart->order_id == 0;
	
	if ($new)
	    $cart->order_id = get_next_trans_no($trans_type);

    $trans_id = $cart->order_id;

	if ($use_transaction)
		begin_transaction();
	
	if(!$new)
		void_journal_trans($trans_type, $trans_id, false);
	
	foreach ($cart->gl_items as $journal_item) {
    
		// post to first found bank account using given gl acount code.
		$is_bank_to = is_bank_account($journal_item->code_id);

		add_payroll_trans($trans_type, $trans_id, $date, $journal_item->code_id, $journal_item->dimension_id, $journal_item->dimension2_id, $journal_item->reference, $journal_item->amount, null, $cart->paytype, $cart->person_id, '', 0, $payslip_no);

	    if ($is_bank_to)
	        add_bank_trans($trans_type, $trans_id, $is_bank_to, $ref, $date, $journal_item->amount, $cart->paytype, $cart->person_id, get_company_currency(), "Cannot insert a destination bank transaction");

		// store tax details if the gl account is a tax account
		add_gl_tax_details($journal_item->code_id, ST_JOURNAL, $trans_id, $journal_item->amount, 1, $date, $memo_);
	}
    
    $total = $cart->gl_items_total_debit();

    add_journal($trans_type, $trans_id, $total,	$date , $cart->currency, $ref, $cart->source_ref, $cart->rate, Today(), Today());

	//add payslip details or payment advice
	if($from_date != '' && $to_date != '') {
		add_payslip($trans_id, $cart->person_id, $from_date, $to_date, $cart->leaves, $cart->deductable_leaves, $cart->payable_amt, $cart->salary_amt);
		add_payslip_details($payslip_no, $cart->allowance);
	}
	else
	    add_payment_advice($trans_id, $payslip_no, $date, $source_ref, $cart->gl_items_total_debit());
	
	$Refs->save($trans_type, $trans_id, $ref);
    
	if ($new)
		add_comments($trans_type, $trans_id, $date, $memo_);
	else
		update_comments($trans_type, $trans_id, null, $memo_);

	add_audit_trail($trans_type, $trans_id, $date);

	if ($reverse) {
    
    	//$reversingDate = date(user_date_display(),
    	//	Mktime(0,0,0,get_month($date)+1,1,get_year($date)));
    	$reversingDate = begin_month(add_months($date, 1));

    	$trans_id_reverse = get_next_trans_no($trans_type);

    	foreach ($cart->gl_items as $journal_item) {
			$is_bank_to = is_bank_account($journal_item->code_id);

    		add_payroll_trans($trans_type, $trans_id_reverse, $reversingDate,
    			$journal_item->code_id, $journal_item->dimension_id, $journal_item->dimension2_id, $journal_item->reference, -$journal_item->amount);
                
    		if ($is_bank_to) {
    			add_bank_trans($trans_type, $trans_id_reverse, $is_bank_to, $ref, $reversingDate, -$journal_item->amount, 0, '', get_company_currency(), "Cannot insert a destination bank transaction");
    		}
			// store tax details if the gl account is a tax account
			add_gl_tax_details($journal_item->code_id, ST_JOURNAL, $trans_id, $journal_item->amount, 1, $reversingDate, $memo_);
    	}

    	add_comments($trans_type, $trans_id_reverse, $reversingDate, $memo_);

    	$Refs->save($trans_type, $trans_id_reverse, $ref);
		add_audit_trail($trans_type, $trans_id_reverse, $reversingDate);
	}

	if ($use_transaction)
		commit_transaction();

	return $trans_id;
}

//--------------------------------------------------------------------------

function get_sql_for_payslips($ref=false, $memo=false, $from, $to, $dept=0, $emp_id=0, $unpaid_only=false) {

	$sql = "SELECT
	    P.generated_date,
	    P.trans_no,
	    P.type AS Type,
	    e.emp_id,
		CONCAT(e.emp_first_name,' ',e.emp_last_name) as emp_name,
		P.payslip_no,
		P.from_date,
		P.to_date,
		P.payable_amount,
		
		COUNT(P.payslip_no) AS Paid 
		
		FROM (SELECT generated_date, emp_id, trans_no, payslip_no, from_date, to_date, payable_amount, 0 AS type FROM ".TB_PREF."payslip UNION SELECT pay_date, NULL, trans_no, payslip_no, NULL, NULL, pay_amount, 1 FROM ".TB_PREF."employee_trans) P 
		
		LEFT JOIN ".TB_PREF."employee e ON P.emp_id = e.emp_id 
		LEFT JOIN ".TB_PREF."journal j ON j.trans_no = P.trans_no 
		LEFT JOIN ".TB_PREF."comments as c ON j.type = c.type AND j.trans_no = c.id 

		WHERE P.generated_date BETWEEN '".date2sql($from)."' AND '" . date2sql($to) . "'";

		if ($ref)
		    $sql .= " AND j.reference LIKE ". db_escape("%$ref%");
	    if ($memo)
		    $sql .= " AND c.memo_ LIKE ". db_escape("%$memo%");
		if($dept != 0)
		    $sql .= " AND e.department_id = ".db_escape($dept);
		if($emp_id != 0)
		    $sql .= " AND e.emp_id = ".db_escape($emp_id);

		if($unpaid_only)
		    $sql .= " GROUP BY P.payslip_no HAVING Paid = 1";
		else
		    $sql .= " GROUP BY P.trans_no";
	return $sql;
}

//--------------------------------------------------------------------------

function get_payslip($trans_no=false, $payslip_no=false) {

    $sql = "SELECT * FROM ".TB_PREF."payslip";
    if($trans_no)
	    $sql .= " WHERE trans_no = ".db_escape($trans_no);
	elseif($payslip_no)
        $sql .= " WHERE payslip_no = ".db_escape($payslip_no);
		
	$result = db_query($sql, _('Could retreive payslip data'));
    return db_fetch($result);
}
function get_payslip_from_advice($trans_no) {
	$sql = "SELECT p.* FROM ".TB_PREF."employee_trans t, ".TB_PREF."payslip p WHERE p.payslip_no = t.payslip_no AND t.trans_no = ".db_escape($trans_no);

	$result = db_query($sql, _('Could retreive payslip data'));
    return db_fetch($result);
}

//--------------------------------------------------------------------------

function payslip_generated_for_date($date, $employee) {

	$sql = "SELECT to_date, emp_id FROM ".TB_PREF."payslip WHERE emp_id = ".db_escape($employee)." AND '".date2sql($date)."' BETWEEN from_date AND to_date GROUP BY to_date";

	return check_empty_result($sql);
}

//--------------------------------------------------------------------------

function payslip_generated_for_period($from_date, $to_date, $employee) {

	$sql = "SELECT to_date, emp_id FROM ".TB_PREF."payslip WHERE emp_id = ".db_escape($employee)." AND '".date2sql($from_date)."' <= from_date AND '".date2sql($to_date)."' >= to_date GROUP BY to_date";

	return check_empty_result($sql);
}

//--------------------------------------------------------------------------

function get_expences_n_deposits($emp_id, $from_date = null, $to_date = null) {

	$sql= "SELECT * FROM ".TB_PREF."gl_trans WHERE type IN (".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.") AND person_type_id = ".PT_EMPLOYEE." AND account NOT IN (SELECT account_code FROM ".TB_PREF."bank_accounts) AND person_id = ".db_escape('EMP'.$emp_id);

	if($from_date != null && $to_date != null) {

		$from = date2sql($from_date);
		$to = date2sql($to_date);
		$sql .= " AND tran_date BETWEEN '$from' AND '$to'";
	}
	return db_query($sql, _('could not get gl data'));
}

//--------------------------------------------------------------------------

function add_payroll_trans($type, $trans_id, $date, $account, $dimension, $dimension2, $memo_, $amount, $currency = null, $person_type_id = null, $person_id = null, $err_msg = '', $rate = 0, $payslip_no = 0) {

	global $use_audit_trail;

	$date = date2sql($date);

	if ($currency != null){
		if ($rate == 0)
			$amount_in_home_currency = to_home_currency($amount, $currency, $date);
		else
			$amount_in_home_currency = round2($amount * $rate,  user_price_dec());
	}		
	else
		$amount_in_home_currency = round2($amount, user_price_dec());
	if ($dimension == null || $dimension < 0)
		$dimension = 0;
	if ($dimension2 == null || $dimension2 < 0)
		$dimension2 = 0;

	if (isset($use_audit_trail) && $use_audit_trail) {
		if ($memo_ == '' || $memo_ == null)
			$memo_ = $_SESSION["wa_current_user"]->username;
		else
			$memo_ = $_SESSION["wa_current_user"]->username . " - " . $memo_;
	}
	$sql = "INSERT INTO ".TB_PREF."gl_trans ( type, type_no, tran_date, account, dimension_id, dimension2_id, memo_, amount";

	if ($person_type_id != null)
		$sql .= ", person_type_id";

	$sql .= ") VALUES (".db_escape($type).", ".db_escape($trans_id).", '$date', ".db_escape($account).", ".db_escape($dimension).", ".db_escape($dimension2).", ".db_escape($memo_).", ".db_escape($amount_in_home_currency);

	if ($person_type_id != null)
		$sql .= ", ".db_escape($person_type_id);

	$sql .= ')';

	if ($err_msg == '')
		$err_msg = "The GL transaction could not be inserted";

	db_query($sql, $err_msg);
	return $amount_in_home_currency;
}

//--------------------------------------------------------------------------

function add_payslip($trans_no, $emp_id, $from, $to, $leaves, $deductable_leaves, $payable_amt, $salary_amt) {

	$from = date2sql($from);
	$to = date2sql($to);
	$today = date2sql(Today());

	$sql = "INSERT INTO ".TB_PREF."payslip (trans_no, emp_id, generated_date, from_date, to_date, leaves, deductable_leaves, payable_amount, salary_amount) VALUES (".db_escape($trans_no).", ".db_escape($emp_id).", '$today', '$from', '$to', ".db_escape($leaves).", ".db_escape($deductable_leaves).", ".db_escape($payable_amt).", ".db_escape($salary_amt).")";
	
	db_query($sql, _('payslip details could not be added'));
}
function add_payslip_details($payslip, $allowances) {

    foreach($allowances as $code => $amount) {
        $sql = "INSERT INTO ".TB_PREF."payslip_details VALUES(".db_escape($payslip).",".db_escape($code).",".db_escape($amount).")";

        db_query($sql, _('could not insert payslip details'));
    }

}

//--------------------------------------------------------------------------

function add_payment_advice($trans_id, $payslip_no, $date, $source_ref, $pay_amount) {

	$sql = "INSERT INTO ".TB_PREF."employee_trans(trans_no, payslip_no, pay_date, to_the_order_of, pay_amount) VALUES (".db_escape($trans_id).", ".db_escape($payslip_no).", '".date2sql($date)."', ".db_escape($source_ref).", ".db_escape($pay_amount).")";

	db_query($sql, _('employee trans could not be inserted'));
}
function has_payment_advice($payslip_no) {
	return check_empty_result("SELECT id FROM ".TB_PREF."employee_trans WHERE payslip_no = ".db_escape($payslip_no));
}