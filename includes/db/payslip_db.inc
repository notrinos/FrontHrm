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
	
	if($new)
	    $cart->order_id = get_next_trans_no($trans_type);

    $trans_id = $cart->order_id;

	if($use_transaction)
		begin_transaction();
	
	if(!$new)
		void_journal_trans($trans_type, $trans_id, false);
	
	foreach($cart->gl_items as $journal_item) {
    
		// post to first found bank account using given gl acount code.
		$is_bank_to = is_bank_account($journal_item->code_id);

		add_gl_trans($trans_type, $trans_id, $date, $journal_item->code_id, $journal_item->dimension_id, $journal_item->dimension2_id, $journal_item->reference, $journal_item->amount, null, $cart->paytype);

	    if($is_bank_to)
	        add_bank_trans($trans_type, $trans_id, $is_bank_to, $ref, $date, $journal_item->amount, $cart->paytype, $cart->person_id, get_company_currency(), "Cannot insert a destination bank transaction");

		// store tax details if the gl account is a tax account
		add_gl_tax_details($journal_item->code_id, ST_JOURNAL, $trans_id, $journal_item->amount, 1, $date, $memo_);
	}
    
    $total = $cart->gl_items_total_debit();

    add_journal($trans_type, $trans_id, $total,	$date , $cart->currency, $ref, $cart->source_ref, $cart->rate, Today(), Today());

	//add payslip details
	add_payslip($trans_id, $cart->person_id, $from_date, $to_date, $cart->leaves, $cart->deductable_leaves, $cart->payable_amt, $cart->salary_amt);
	add_payslip_details($payslip_no, $cart->allowance);

	
	$Refs->save($trans_type, $trans_id, $ref);
    
	if($new)
		add_comments($trans_type, $trans_id, $date, $memo_);
	else
		update_comments($trans_type, $trans_id, null, $memo_);

	add_audit_trail($trans_type, $trans_id, $date);

	if($reverse) {

    	$reversingDate = begin_month(add_months($date, 1));

    	$trans_id_reverse = get_next_trans_no($trans_type);

    	foreach($cart->gl_items as $journal_item) {
			$is_bank_to = is_bank_account($journal_item->code_id);

    		add_gl_trans($trans_type, $trans_id_reverse, $reversingDate, $journal_item->code_id, $journal_item->dimension_id, $journal_item->dimension2_id, $journal_item->reference, -$journal_item->amount);
                
    		if($is_bank_to) {
    			add_bank_trans($trans_type, $trans_id_reverse, $is_bank_to, $ref, $reversingDate, -$journal_item->amount, 0, '', get_company_currency(), "Cannot insert a destination bank transaction");
    		}
			// store tax details if the gl account is a tax account
			add_gl_tax_details($journal_item->code_id, ST_JOURNAL, $trans_id, $journal_item->amount, 1, $reversingDate, $memo_);
    	}

    	add_comments($trans_type, $trans_id_reverse, $reversingDate, $memo_);

    	$Refs->save($trans_type, $trans_id_reverse, $ref);
		add_audit_trail($trans_type, $trans_id_reverse, $reversingDate);
	}

	if($use_transaction)
		commit_transaction();

	return $trans_id;
}

//--------------------------------------------------------------------------

function get_sql_for_payslips($ref=false, $memo=false, $from, $to, $dept=0, $emp_id=0, $unpaid_only=false) {

	$sql = "SELECT
	    P.generated_date,
	    P.trans_no,
	    P.type AS Type,
	    CASE WHEN e.emp_id IS NULL THEN (SELECT ad.emp_id FROM ".TB_PREF."employee_advance ad, ".TB_PREF."employee_trans tr WHERE ad.emp_trans_no = tr.id AND tr.trans_no = P.trans_no AND tr.payslip_no = 0) ELSE e.emp_id END,
		CASE WHEN e.emp_id IS NULL THEN (SELECT CONCAT(em.emp_first_name,' ',em.emp_last_name) FROM ".TB_PREF."employee em, ".TB_PREF."employee_advance ad, ".TB_PREF."employee_trans tr WHERE ad.emp_trans_no = tr.id AND tr.trans_no = P.trans_no AND tr.payslip_no = 0 AND ad.emp_id = em.emp_id) ELSE CONCAT(e.emp_first_name,' ',e.emp_last_name) END as emp_name,
		P.payslip_no,
		P.from_date,
		P.to_date,
		P.payable_amount,
		
		COUNT(P.payslip_no) AS Paid 
		
		FROM (SELECT p.generated_date, p.emp_id, p.trans_no, p.payslip_no, p.from_date, p.to_date, p.payable_amount, 0 AS type FROM ".TB_PREF."payslip p UNION SELECT t.pay_date, (SELECT emp_id FROM ".TB_PREF."payslip WHERE payslip_no = t.payslip_no), t.trans_no, t.payslip_no, (SELECT from_date FROM ".TB_PREF."payslip WHERE payslip_no = t.payslip_no), (SELECT to_date FROM ".TB_PREF."payslip WHERE payslip_no = t.payslip_no), t.pay_amount, 1 FROM ".TB_PREF."employee_trans t) P 
		
		LEFT JOIN ".TB_PREF."employee e ON P.emp_id = e.emp_id 
		LEFT JOIN ".TB_PREF."journal j ON j.trans_no = P.trans_no 
		LEFT JOIN ".TB_PREF."bank_trans b ON b.trans_no = P.trans_no AND (b.type = ".ST_BANKPAYMENT." OR b.type = ".ST_BANKDEPOSIT.") 
		LEFT JOIN ".TB_PREF."comments c ON (j.type = c.type AND j.trans_no = c.id) OR (b.type = c.type AND b.trans_no = c.id)  

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
		    $sql .= " AND payslip_no != 0 GROUP BY P.payslip_no HAVING Paid = 1";
		else
		    $sql .= " GROUP BY P.trans_no, P.type";
	return $sql;
}

//--------------------------------------------------------------------------

function get_payslip($trans_no=false, $payslip_no=false) {

    $sql = "SELECT * FROM ".TB_PREF."payslip";
    if($trans_no)
	    $sql .= " WHERE trans_no = ".db_escape($trans_no);
	elseif($payslip_no)
        $sql .= " WHERE payslip_no = ".db_escape($payslip_no);
		
	$result = db_query($sql, _('Could not retreive payslip data'));
    return db_fetch($result);
}

//--------------------------------------------------------------------------

function get_employee_payslip($emp_id, $all=false) {
	$sql = "SELECT p.* FROM ".TB_PREF."payslip p LEFT JOIN ".TB_PREF."employee_trans t ON p.payslip_no = t.payslip_no WHERE p.emp_id = ".db_escape($emp_id);

	if(!$all)
		$sql .= " AND t.payslip_no IS NULL";

	$result = db_query($sql, _('could not not retreive payslip data'));

	return $result;
}

//--------------------------------------------------------------------------

function get_payslip_from_advice($trans_no) {
	$sql = "SELECT p.* FROM ".TB_PREF."employee_trans t, ".TB_PREF."payslip p WHERE p.payslip_no = t.payslip_no AND t.trans_no = ".db_escape($trans_no);

	$result = db_query($sql, _('Could not retreive payslip data'));
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

function add_payslip($trans_no, $emp_id, $from, $to, $leaves, $deductable_leaves, $payable_amt, $salary_amt) {

	$from = date2sql($from);
	$to = date2sql($to);
	$today = date2sql(Today());

	$sql = "INSERT INTO ".TB_PREF."payslip (trans_no, emp_id, generated_date, from_date, to_date, leaves, deductable_leaves, payable_amount, salary_amount) VALUES (".db_escape($trans_no).", ".db_escape($emp_id).", '$today', '$from', '$to', ".db_escape($leaves).", ".db_escape($deductable_leaves).", ".db_escape($payable_amt).", ".db_escape($salary_amt).")";
	
	db_query($sql, _('payslip details could not be added'));
}

//--------------------------------------------------------------------------

function add_payslip_details($payslip, $allowances) {

    foreach($allowances as $code => $amount) {
        $sql = "INSERT INTO ".TB_PREF."payslip_details VALUES(".db_escape($payslip).",".db_escape($code).",".db_escape($amount).")";

        db_query($sql, _('could not insert payslip details'));
    }
}