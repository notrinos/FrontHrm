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

function db_has_doc_type() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."document_types");
}

//--------------------------------------------------------------------------

function write_doc_type($id = false, $name, $days) {

	if(!$id)
		$sql = "INSERT INTO ".TB_PREF."document_types(type_name, notify_before) VALUES (".db_escape($name).", ".db_escape($days).")";
	else
		$sql = "UPDATE ".TB_PREF."document_types SET type_name = ".db_escape($name).", notify_before = ".db_escape($days)." WHERE type_id = ".db_escape($id);
	
	db_query($sql, _('Could not write document type data'));
}

//--------------------------------------------------------------------------

function get_doc_types($id = false, $all = false) {
	
	$sql = "SELECT * FROM ".TB_PREF."document_types";
	$where = array();
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "type_id = ".db_escape($id);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	
	$sql .= " ORDER BY type_id";

	$result = db_query($sql, _('Could not get document type data.'));
	
	if($id)
		return db_fetch($result);
	else
		return $sql;
}

//--------------------------------------------------------------------------

function delete_doc_type($id) {
	$sql = "DELETE FROM ".TB_PREF."document_types WHERE type_id = ".db_escape($id);
	db_query($sql, _('Could not delete document type.'));
}

//--------------------------------------------------------------------------

function doc_type_used($id) {
	return check_empty_result("SELECT COUNT(type_id) FROM ".TB_PREF."employee_docs WHERE type_id = ".db_escape($id));
}

//--------------------------------------------------------------------------

function add_document($emp_id, $type_id, $description, $issue_date, $expiry_date, $alert, $filename, $unique_name, $filesize, $filetype) {

	$sql = "INSERT INTO ".TB_PREF."employee_docs (emp_id, type_id, description, issue_date, expiry_date, alert, filename, unique_name, filesize, filetype) VALUES (".db_escape($emp_id).", ".db_escape($type_id).",".db_escape($description).", '".date2sql($issue_date)."', '".date2sql($expiry_date)."', ".db_escape($alert).", ".db_escape($filename).", ".db_escape($unique_name).", ".db_escape($filesize).", ".db_escape($filetype).")";

	db_query($sql, _('Attachment could not be inserted'));		
}

//--------------------------------------------------------------------------

function update_document($selected_id, $emp_id, $type_id, $description, $issue_date, $expiry_date, $alert, $filename, $unique_name, $filesize, $filetype) {

	$sql = "UPDATE ".TB_PREF."employee_docs SET emp_id = ".db_escape($emp_id).", type_id=".db_escape($type_id).", description = ".db_escape($description).", issue_date = '".date2sql($issue_date)."', expiry_date = '".date2sql($expiry_date)."', alert = ".db_escape($alert);

	if ($filename != '')
		$sql .= ", filename = ".db_escape($filename).", unique_name = ".db_escape($unique_name).", filesize = ".db_escape($filesize).", filetype = ".db_escape($filetype);

	$sql .= " WHERE id = ".db_escape($selected_id);
	db_query($sql, _('Attachment could not be updated'));		
}

//--------------------------------------------------------------------------

function delete_document($id) {
	$sql = "DELETE FROM ".TB_PREF."employee_docs WHERE id = ".db_escape($id);
	db_query($sql, _('Could not delete attachment'));
}

//--------------------------------------------------------------------------

function get_document($id) {
	$sql = "SELECT * FROM ".TB_PREF."employee_docs WHERE id = ".db_escape($id);
	$result = db_query($sql, _('Could not retrieve attachments'));
	return db_fetch($result);
}

//--------------------------------------------------------------------------

function get_sql_for_employee_documents($emp_id = 0, $type_id = 0, $alert = 0, $no_alert = 0, $expired_from = null, $expired_to = null, $issued_from = null, $issued_to = null, $string = false) {

	$sql = "SELECT id, type_id, description, issue_date, expiry_date, alert, filename, filesize, filetype, emp_id FROM ".TB_PREF."employee_docs ";

	if(empty($expired_from) && empty($expired_to) && empty($issued_from) && empty($issued_to))
	    $sql .= " WHERE emp_id = ".db_escape($emp_id);
	else
	    $sql .= " WHERE issue_date BETWEEN '".date2sql($issued_from)."' AND '" . date2sql($issued_to) . "' AND expiry_date BETWEEN '".date2sql($expired_from)."' AND '".date2sql($expired_to)."' ";

	if(!empty($emp_id))
	    $sql .= " AND emp_id = ".db_escape($emp_id);
	if(!empty($type_id))
	    $sql .= " AND type_id = ".db_escape($type_id);
	if((!empty($alert) && !empty($no_alert)) || (empty($alert) && empty($no_alert)))
	    $sql .= " AND alert >= 0 ";
	elseif(!empty($no_alert))
	    $sql .= " AND alert = 0 ";
	elseif(!empty($alert))
	    $sql .= " AND alert = 1 ";
	if(!empty($string))
        $sql .= " AND (description LIKE ".db_escape("%$string%")." OR filename LIKE ".db_escape("%$string%")." OR id LIKE ".db_escape("%$string%").")";

	$sql .= " ORDER BY id";

	return $sql;
}

//--------------------------------------------------------------------------

function get_alert_from($type_id, $expiry_date) {
	$sql = "SELECT notify_before FROM ".TB_PREF."document_types WHERE type_id = ".db_escape($type_id);
	$result = db_query($sql, _('could not get document type data'));

	$row = db_fetch($result);

	return add_days($expiry_date, 0 - $row[0]);
}