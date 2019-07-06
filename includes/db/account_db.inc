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

function add_pay_element($name, $account) {
    $sql= "INSERT INTO ".TB_PREF."pay_element (element_name, account_code) VALUES (".db_escape($name).",".db_escape($account).")";
    
    db_query($sql, _('could not insert account'));
}

//--------------------------------------------------------------------------

function get_payroll_elements($id=false, $account=false) {
    $sql = "SELECT e.element_id, e.element_name, a.account_code, a.account_name FROM ".TB_PREF."chart_master a, ".TB_PREF."pay_element e WHERE a.account_code = e.account_code";

    if($id)
    	$sql .= " AND e.element_id = ".db_escape($id);
    if($account)
    	$sql .= " AND e.account_code = ".db_escape($account);
    
    $result = db_query($sql, _('Could not get accounts'));

    if($account || $id)
    	return db_fetch($result);
    else
    	return $result;
}

//--------------------------------------------------------------------------

function update_pay_element($id, $name) {

	$sql = "UPDATE ".TB_PREF."pay_element SET element_name = ".db_escape($name)." WHERE element_id = ".db_escape($id);

	db_query($sql, _('could not update pay element'));
}

//--------------------------------------------------------------------------

function delete_payroll_account($id) {

    $sql = "DELETE FROM ".TB_PREF."pay_element WHERE element_id=".db_escape($id);
    $result = db_query($sql, _('Could not delete element'));
}

//--------------------------------------------------------------------------

function payroll_account_exist($code) {
    return check_empty_result("SELECT account_code FROM ".TB_PREF."pay_element WHERE account_code=".db_escape($code));
}

//--------------------------------------------------------------------------

function payroll_account_used($id) {

    return check_empty_result("SELECT e.element_id, p.payroll_rule, e.account_code FROM ".TB_PREF."payroll_structure p, ".TB_PREF."pay_element e WHERE e.element_id=".db_escape($id)." AND p.payroll_rule LIKE CONCAT('%',e.account_code,'%')");
}

//--------------------------------------------------------------------------

function get_payroll_rules() {

    $sql = "SELECT e.element_name, c.account_code, c.account_name FROM ".TB_PREF."chart_master c, ".TB_PREF."pay_element e WHERE c.account_code = e.account_code ORDER BY c.account_code";

	return db_query($sql, _('Could not get accounts'));
}

//--------------------------------------------------------------------------

function add_payroll_rule($position_id, $payroll_rule) {

	$sql = "INSERT INTO ".TB_PREF."payroll_structure (position_id, payroll_rule ) VALUES (".db_escape($position_id).",".db_escape(implode(';',$payroll_rule)).")";
	db_query($sql, _('The payroll could not be added'));
}

//--------------------------------------------------------------------------

function update_payroll_rule($position_id, $payroll_rule) {

	$sql = "UPDATE ".TB_PREF."payroll_structure SET payroll_rule=".db_escape(implode(';', $payroll_rule))."WHERE position_id=".db_escape($position_id);
		
	db_query($sql, _('The payroll could not be updated'));

}

//--------------------------------------------------------------------------

function payroll_rule_exist($position_id) {

	return check_empty_result("SELECT payroll_rule FROM ".TB_PREF."payroll_structure WHERE position_id=".db_escape($position_id));
}

//--------------------------------------------------------------------------

function payroll_rule_used($position, $rule) {
	return check_empty_result("SELECT id FROM ".TB_PREF."salary_structure WHERE position_id = ".db_escape($position)." AND pay_rule_id = ".db_escape($rule));
}

//--------------------------------------------------------------------------

function reset_payroll($position) {

	$sql = "DELETE FROM ".TB_PREF."payroll_structure WHERE position_id =".db_escape($position);
	
    db_query($sql, _('could not reset payroll rules'));
}

//--------------------------------------------------------------------------

function get_payroll_structure($id) {

	$sql = "SELECT * FROM ".TB_PREF."payroll_structure WHERE position_id = ".db_escape($id);
	$result = db_query($sql, _('could not get payroll structure'));
	$row = db_fetch($result);
    
	if ($row != false)
		$row['payroll_rule'] = explode(';', $row['payroll_rule']);

	return $row;
}