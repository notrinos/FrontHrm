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

function add_payroll_account($account) {
    $sql= "INSERT INTO ".TB_PREF."payroll_account (account_code) VALUES (".db_escape($account).")";
    
    db_query($sql, "could not insert account");
}

//--------------------------------------------------------------------------

function get_payroll_accounts() {
    $sql = "SELECT pa.account_id, ac.account_code, ac.account_name FROM ".TB_PREF."chart_master ac, ".TB_PREF."payroll_account pa WHERE ac.account_code = pa.account_code";
    
    return db_query($sql, 'Could not get accounts');
}

//--------------------------------------------------------------------------

function delete_payroll_account($id) {

    $sql = "DELETE FROM ".TB_PREF."payroll_account WHERE account_id=".db_escape($id);
    $result = db_query($sql, 'Could not delete account');
}

//--------------------------------------------------------------------------

function payroll_account_exist($code) {
    return check_empty_result("SELECT account_code FROM ".TB_PREF."payroll_account WHERE account_code=".db_escape($code));
}

//--------------------------------------------------------------------------

function payroll_account_used($id) {

    return check_empty_result("SELECT pa.account_id, ps.payroll_rule, pa.account_code FROM ".TB_PREF."payroll_structure ps, ".TB_PREF."payroll_account pa WHERE pa.account_id=".db_escape($id)." AND ps.payroll_rule LIKE CONCAT('%',pa.account_code,'%')");
}

//--------------------------------------------------------------------------

function get_payroll_rules() {

    $sql = "SELECT ca.account_code, ca.account_name, pa.account_code FROM ".TB_PREF."chart_master ca, ".TB_PREF."payroll_account pa WHERE ca.account_code = pa.account_code ORDER BY ca.account_code";

	return db_query($sql, "Could not get accounts");		
}

//--------------------------------------------------------------------------

function add_payroll_rule($salary_scale_id, $payroll_rule) {

	$sql = "INSERT INTO ".TB_PREF."payroll_structure (salary_scale_id, payroll_rule ) VALUES (".db_escape($salary_scale_id).",".db_escape(implode(';',$payroll_rule)).")";
	db_query($sql, "The payroll could not be added");	
}

//--------------------------------------------------------------------------

function update_payroll_rule($salary_scale_id, $payroll_rule) {

	$sql = "UPDATE ".TB_PREF."payroll_structure SET payroll_rule=".db_escape(implode(';', $payroll_rule))."WHERE salary_scale_id=".db_escape($salary_scale_id);
		
	db_query($sql, "The payroll could not be updated");

}

//--------------------------------------------------------------------------

function payroll_rule_exist($salary_scale_id) {

	return check_empty_result("SELECT payroll_rule FROM ".TB_PREF."payroll_structure WHERE salary_scale_id=".db_escape($salary_scale_id));
}

//--------------------------------------------------------------------------

function payroll_rule_used($salary_scale, $rule) {
	return check_empty_result("SELECT id FROM ".TB_PREF."salary_structure WHERE salary_scale_id = ".db_escape($salary_scale)." AND pay_rule_id = ".db_escape($rule));
}

//--------------------------------------------------------------------------

function reset_payroll($salary_scale_id) {

	$sql = "DELETE FROM ".TB_PREF."payroll_structure WHERE salary_scale_id =".db_escape($salary_scale_id);
	
    db_query($sql, "could not reset payroll rules");
}

//--------------------------------------------------------------------------

function get_payroll_structure($id) {

	$sql = "SELECT * FROM ".TB_PREF."payroll_structure WHERE salary_scale_id = ".db_escape($id);
	$result = db_query($sql, "could not get payroll structure");
	$row = db_fetch($result);
    
	if ($row != false)
		$row['payroll_rule'] = explode(';', $row['payroll_rule']);

	return $row;
}
