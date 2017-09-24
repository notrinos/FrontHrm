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

function get_next_payslip_no() {

	$sql = "SELECT MAX(payslip_no)+1 FROM ".TB_PREF."gl_trans";
	$result = db_query($sql, "The next payslip number could not be retreived");
    $row = db_fetch_row($result);

    return ($row[0])?$row[0]:1;
}

//--------------------------------------------------------------------------

function write_payslip(&$cart, $reverse, $use_transaction = true) {

	global $Refs;
	$date       = $cart->tran_date;
	$ref        = $cart->reference;
	$memo_      = $cart->memo_;
	$from_date  = $cart->from_date;
	$to_date    = $cart->to_date;
	$trans_type = $cart->trans_type;
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

		add_payroll_trans($trans_type, $trans_id, $date, $journal_item->code_id, $journal_item->dimension_id, $journal_item->dimension2_id, $journal_item->reference, $journal_item->amount, null, $cart->paytype, $cart->person_id, '', 0, $cart->payslip_no);

	    if ($is_bank_to) {
            
	        add_bank_trans($trans_type, $trans_id, $is_bank_to, $ref, $date, $journal_item->amount, $cart->paytype, $cart->person_id, get_company_currency(), "Cannot insert a destination bank transaction");
	    }

		// store tax details if the gl account is a tax account
		add_gl_tax_details($journal_item->code_id, ST_JOURNAL, $trans_id, $journal_item->amount, 1, $date, $memo_);
	}
    
    $total = $cart->gl_items_total_debit();

	//add payslip details
	if($from_date != '' && $to_date != '')
		add_payslip_details($cart->payslip_no, $cart->to_the_order_of, $from_date, $to_date, $cart->leaves, $cart->deductable_leaves);
    add_journal($trans_type, $trans_id, $total,	$date , $cart->currency, $ref, $cart->source_ref, $cart->rate, Today(), Today());
	
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

function get_sql_for_payslips($employee, $from = '', $to = '', $dept = '', $salary_scale = '', $only_unpaid = false) {
	global $Payable_act;

	$sql = "SELECT 
        gl.type_no as trans_no,
        gl.tran_date,
        e.emp_id,
		CONCAT(e.emp_first_name,' ',e.emp_last_name) as emp_name,
		p.payslip_no,
		p.from_date,
		p.to_date,
		ABS(gl.amount) as amount,
		c.memo_,
		gl.type as trans_type,
		IF(ISNULL(a.gl_seq),0,a.gl_seq) as gl_seq,
		COUNT(gl.payslip_no) as PaySlip
		
		FROM ".TB_PREF."gl_trans as gl
		 LEFT JOIN ".TB_PREF."audit_trail as a ON
			(gl.type = a.type AND gl.type_no = a.trans_no)
		 LEFT JOIN ".TB_PREF."comments as c ON
			(gl.type = c.type AND gl.type_no = c.id)
		 LEFT JOIN ".TB_PREF."employee as e ON
			e.emp_id = SUBSTRING(gl.person_id, 4)
		 LEFT JOIN ".TB_PREF."salaryscale as s ON
			e.salary_scale_id = s.scale_id
		 LEFT JOIN ".TB_PREF."department as d ON
			e.department_id = d.dept_id
		 LEFT JOIN ".TB_PREF."payslip_detail as p ON
		    gl.payslip_no = p.payslip_no
		WHERE gl.person_type_id = ".PT_EMPLOYEE."
		AND gl.account = " .$Payable_act ;

	if($from != '' && $to != '')
		$sql .= " AND gl.tran_date >= '" . date2sql($from) . "' AND gl.tran_date <= '" . date2sql($to) . "'";
	if($dept != '')
		$sql .= " AND e.department_id = ".db_escape($dept);
	elseif($salary_scale != '')
		$sql .= " AND e.salary_scale_id = ".db_escape($salary_scale);
	elseif($employee != -1 && $employee != '')
		$sql .= " AND SUBSTRING(gl.person_id, 4) = ".db_escape($employee);
		
	$sql .= " AND gl.amount != 0 ";
	
	$sql .= " GROUP BY gl.payslip_no";

	if($only_unpaid)
		$sql .= " HAVING PaySlip = 1";
	
	return $sql;
}

//--------------------------------------------------------------------------

function get_payslip($trans_id) {
	global $Payable_act;

	$sql = "SELECT gl.*, pd.to_the_order_of FROM ".TB_PREF."gl_trans as gl, ".TB_PREF."payslip_detail as pd WHERE type = ".ST_JOURNAL." AND account = " .$Payable_act." AND type_no = ".db_escape($trans_id)." AND gl.payslip_no = pd.payslip_no";
		
	$result = db_query($sql, "Cannot retreive a gl transaction");
    return db_fetch($result);
}

//--------------------------------------------------------------------------

function payslip_generated_for_date($date, $employee) {

	$sql = "SELECT pd.to_date, bt.person_id FROM ".TB_PREF."gl_trans gl, ".TB_PREF."bank_trans bt, ".TB_PREF."payslip_detail pd WHERE gl.payslip_no = pd.payslip_no AND gl.person_type_id = ".PT_EMPLOYEE." AND SUBSTRING(gl.person_id, 4) = ".db_escape($employee)." AND '".date2sql($date)."' BETWEEN pd.from_date AND pd.to_date GROUP BY to_date";

	return check_empty_result($sql);
}

//--------------------------------------------------------------------------

function payslip_generated_for_period($from_date, $to_date, $employee) {

	$sql = "SELECT pd.to_date, bt.person_id FROM ".TB_PREF."gl_trans gl, ".TB_PREF."bank_trans bt, ".TB_PREF."payslip_detail pd WHERE gl.payslip_no = pd.payslip_no AND gl.person_type_id = ".PT_EMPLOYEE." AND SUBSTRING(gl.person_id, 4) = ".db_escape($employee)." AND '".date2sql($from_date)."' <= pd.from_date AND '".date2sql($to_date)."' >= pd.to_date GROUP BY to_date";

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
	return db_query($sql, 'could not get gl data');
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
	$sql = "INSERT INTO ".TB_PREF."gl_trans ( type, type_no, tran_date, account, dimension_id, dimension2_id, memo_, amount, payslip_no";

	if ($person_type_id != null)
		$sql .= ", person_type_id, person_id";

	$sql .= ") VALUES (".db_escape($type).", ".db_escape($trans_id).", '$date', ".db_escape($account).", ".db_escape($dimension).", ".db_escape($dimension2).", ".db_escape($memo_).", ".db_escape($amount_in_home_currency).", ".db_escape($payslip_no);

	if ($person_type_id != null)
		$sql .= ", ".db_escape($person_type_id).", ".db_escape('EMP'.$person_id);

	$sql .= ')';

	if ($err_msg == '')
		$err_msg = "The GL transaction could not be inserted";

	db_query($sql, $err_msg);
	return $amount_in_home_currency;
}

//--------------------------------------------------------------------------

function add_payslip_details($payslip_no, $to_the_order_of, $from, $to, $leaves, $deductable_leaves) {

	$from = date2sql($from);
	$to = date2sql($to);
	$today = date2sql(Today());

	$sql = "INSERT INTO ".TB_PREF."payslip_detail (payslip_no, generated_date, to_the_order_of, from_date, to_date, leaves, deductable_leaves) VALUES (".db_escape($payslip_no).", '$today', ".db_escape($to_the_order_of).", '$from', '$to', ".db_escape($leaves).", ".db_escape($deductable_leaves).")";
	
	db_query($sql, "payslip details could not be added");
}
