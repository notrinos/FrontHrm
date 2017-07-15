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

function db_has_salary_scale() {

	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."salaryscale");
}

//--------------------------------------------------------------------------

function write_scale($id = false, $name) {
	
	if(!$id)
		$sql = "INSERT INTO ".TB_PREF."salaryscale(scale_name ) VALUES (".db_escape($name).")";
	else
		$sql = "UPDATE ".TB_PREF."salaryscale SET scale_name = ".db_escape($name)." WHERE scale_id = ".db_escape($id);
	
	db_query($sql, 'Could not write salary scale data');
}

//--------------------------------------------------------------------------

function delete_salary_scale($id) {
	
	$sql = "DELETE FROM ".TB_PREF."salaryscale WHERE scale_id = ".db_escape($id);;
	db_query($sql, "could not delete salary scale");
}

//--------------------------------------------------------------------------

function get_salary_scale($id = false, $all = false) {
    
	$sql = "SELECT * FROM ".TB_PREF."salaryscale";
	$where = array();
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "scale_id=".db_escape($id);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	else
		$sql .= ' WHERE 1';

	$result = db_query($sql, "Could not get salary scale data.");
	
	if($id)
		return db_fetch($result);
	else
		return $sql;

}

//--------------------------------------------------------------------------

function salary_scale_used($id) {

	return  check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."employee WHERE salary_scale_id = ".db_escape($id));
}