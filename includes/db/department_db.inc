<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương                                      |
|   Date :   09-Jul-2017                                  |
|   Description: Frontaccounting Payroll & Hrm Module    |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

function db_has_department() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."department");
}

//--------------------------------------------------------------------------

function write_department($id = false, $name) {

	if(!$id) {
		$sql = "INSERT INTO ".TB_PREF."department(dept_name) VALUES (".db_escape($name).")";
	}
	else {
		$sql = "UPDATE ".TB_PREF."department SET 
		dept_name = ".db_escape($name)." WHERE dept_id = ".db_escape($id);
	}
	
	db_query($sql, _('Could not write department data'));
}

//--------------------------------------------------------------------------

function get_departments($id = false, $all = false) {
	
	$sql = "SELECT * FROM ".TB_PREF."department";
	$where = array();
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "dept_id=".db_escape($id);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	else
		$sql .= ' WHERE 1';

	$result = db_query($sql, _('Could not get department data.'));
	
	if($id)
		return db_fetch($result);
	else
		return $sql;
}

//--------------------------------------------------------------------------

function delete_department($id) {
	$sql = "DELETE FROM ".TB_PREF."department WHERE dept_id = ".db_escape($id);
	db_query($sql, _('Could not delete department.'));
}

//--------------------------------------------------------------------------

function department_has_employees($id) {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."employee WHERE department_id = ".db_escape($id));
}