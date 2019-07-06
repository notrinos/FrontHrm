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

$path_to_root = '../../..';

if(isset($_GET['View'])) $_POST['View'] = $_GET['View'];
$page_security = empty($_POST['View']) ? 'SA_ATTACHDOCUMENT' : 'SA_EMPL';

include_once($path_to_root . "/modules/FrontHrm/includes/hrm_classes.inc");
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/admin/db/transactions_db.inc");

include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

if (isset($_GET['vw']))
	$view_id = $_GET['vw'];
else
	$view_id = find_submit('view');
if ($view_id != -1) {

	$row = get_document($view_id);

	if ($row['filename'] != "") {
		if(in_ajax())
			$Ajax->popup($_SERVER['PHP_SELF'].'?vw='.$view_id);
		else {
			$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';	
    		header("Content-type: ".$type);
    		header('Content-Length: '.$row['filesize']);
 			header("Content-Disposition: inline");
	    	echo file_get_contents(company_path(). "/attachments/".$row['unique_name']);
    		exit();
		}
	}	
}
if (isset($_GET['dl']))
	$download_id = $_GET['dl'];
else
	$download_id = find_submit('download');

if ($download_id != -1) {

	$row = get_document($download_id);

	if ($row['filename'] != "") {
		if(in_ajax())
			$Ajax->redirect($_SERVER['PHP_SELF'].'?dl='.$download_id);
		else {
			$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';	
    		header("Content-type: ".$type);
	    	header('Content-Length: '.$row['filesize']);
    		header('Content-Disposition: attachment; filename='.$row['filename']);
    		echo file_get_contents(company_path()."/attachments/".$row['unique_name']);
	    	exit();
		}
	}	
}

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(1200, 600);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = 'Employee Attach Documents'), isset($_GET['EmpId'])&&isset($_GET['DocId']), false, '', $js);

if(!db_has_doc_type()) {
	display_error(_('There are no <b>Document Types</b> defined in the system'));
	display_footer_exit();
}

simple_page_mode(true);

//----------------------------------------------------------------------------------------

if (isset($_GET['EmpId']))
	$_POST['emp_id'] = $_GET['EmpId'];
if (isset($_GET['DocId'])) {
	$selected_id = $_GET['DocId'];
	$Mode = 'Edit';
}

if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
	$error = 0;

	if (empty($_POST['emp_id'])) {
		display_error(_('Select an employee.'));
		set_focus('emp_id');
		$error = 1;
	}
	elseif (empty($_POST['type_id'])) {
		display_error(_('Select a document type.'));
		set_focus('type_id');
		$error = 1;
	}
	elseif (strlen($_POST['doc_title']) == 0 || empty(trim($_POST['doc_title']))) {
		display_error(_('The document description cannot be empty.'));
		set_focus('doc_title');
		$error = 1;
	}
	elseif (date_comp($_POST['issue_date'], $_POST['expiry_date']) > 0) {
		display_error(_('Issue date cannot be after expiry date.'));
		set_focus('issue_date');
		$error = 1;
	}
	elseif ($Mode == 'ADD_ITEM' && !isset($_FILES['filename'])){
		display_error(_('Select attachment file.'));
		$error = 1;
	}
	elseif ($Mode == 'ADD_ITEM' && ($_FILES['filename']['error'] > 0)) {
    	if ($_FILES['filename']['error'] == UPLOAD_ERR_INI_SIZE) 
		  	display_error(_('The file size is over the maximum allowed.'));
    	else
		  	display_error(_('Select attachment file.'));
		$error = 1;
  	}
	else {
		$tmpname = $_FILES['filename']['tmp_name'];

		$dir =  company_path()."/attachments";

		if (!file_exists($dir)) {
			mkdir ($dir,0777);
			$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
			$fp = fopen($dir."/index.php", "w");
			fwrite($fp, $index_file);
			fclose($fp);
		}

		$filename = basename($_FILES['filename']['name']);
		$filesize = $_FILES['filename']['size'];
		$filetype = $_FILES['filename']['type'];

		if ($Mode == 'UPDATE_ITEM') {
		    $row = get_document($selected_id);
		    if ($row['filename'] == "")
        		exit();
			$unique_name = $row['unique_name'];
			if ($filename && file_exists($dir."/".$unique_name))
				unlink($dir."/".$unique_name);
		}
		else
			$unique_name = random_id();

		move_uploaded_file($tmpname, $dir."/".$unique_name);

		if ($Mode == 'ADD_ITEM') {
			add_document($_POST['emp_id'], $_POST['type_id'], $_POST['doc_title'], $_POST['issue_date'], $_POST['expiry_date'], $_POST['alert'], $filename, $unique_name, $filesize, $filetype);
			display_notification(_("Attachment has been inserted.")); 
		}
		else {
			update_document($selected_id, $_POST['emp_id'], $_POST['type_id'], $_POST['doc_title'], $_POST['issue_date'], $_POST['expiry_date'], $_POST['alert'], $filename, $unique_name, $filesize, $filetype); 
			display_notification(_("Attachment has been updated.")); 
		}
	}
	refresh_pager('trans_tbl');
	if(empty($error))
		$Mode = 'RESET';
}

if ($Mode == 'Delete') {
	$row = get_document($selected_id);
	$dir =  company_path()."/attachments";
	if (file_exists($dir."/".$row['unique_name']))
		unlink($dir."/".$row['unique_name']);
	delete_document($selected_id);	
	display_notification(_("Attachment has been deleted.")); 
	$Mode = 'RESET';
}

if ($Mode == 'RESET'){
	unset($_POST['doc_title']);
	unset($_POST['type_id']);
	unset($_POST['alert']);
	$selected_id = -1;
}

function viewing_controls() {
	global $selected_id;
	
    start_table(TABLESTYLE_NOBORDER);
    
    if(empty($_POST['View'])) {
	    start_row();
	    employee_list_cells(null, 'emp_id', null, _('Select employee'), true);
	    if (list_updated('emp_id'))
		    $selected_id = -1;

	    end_row();
    }
    else {
    	start_row();
    	ref_cells(_('Enter search string:'), 'string', _('Enter fragment or leave empty'), null, null, true);
    	employee_list_cells(null, 'emp_id', null, _('All employees'), true);
    	doctype_list_cells(null, 'type_id', null, _('All document type'), true);
    	check_cells(_('Alert:'), 'alert', null, true);
    	check_cells(_('Not Alert:'), 'no_alert', null, true);
    	end_row();
    	// end_table();

    	// start_table(TABLESTYLE_NOBORDER);
    	start_row();
    	date_cells(_('Expired').':', 'expired_from', '', null, 0, 0, -5, null, true);
    	date_cells(_('To').':', 'expired_to', '', null, 0, 0, 5, null, true);
    	date_cells(_('Issued').':', 'issue_from', '', null, 0, 0, -5, null, true);
    	date_cells(_('To').':', 'issue_to', '', null, 0, 0, 5, null, true);
    	submit_cells('Search', _('Search'), '', '', 'default');
    	end_row();
    }

    end_table(1);
}

function type_name($row){
	return get_doc_types($row["type_id"])['type_name'];
}
function is_alert($row) {
	return $row['alert'] == 1 ? _('Alert') : '';
}
function edit_link($row){
	if(!empty($_POST['View']))
		return viewer_link(_('Click to edit this document'), "modules/FrontHrm/manage/employee_docs.php?EmpId=".$row['emp_id']."&DocId=".$row['id'], '', '', ICON_EDIT);
	else
		return button('Edit'.$row["id"], _("Edit"), _("Edit"), ICON_EDIT);
}
function view_link($row){
  	return button('view'.$row["id"], _("View"), _("View"), ICON_VIEW);
}
function download_link($row){
  	return button('download'.$row["id"], _("Download"), _("Download"), ICON_DOWN);
}
function delete_link($row) {
  	return button('Delete'.$row["id"], _("Delete"), _("Delete"), ICON_DELETE);
}
function check_expired($row) {
	return date_comp(Today(), sql2date($row['expiry_date'])) > 0 && $row['alert'] != 0;
}
function check_warning($row) {
	$alert_from = get_alert_from($row['type_id'], sql2date($row['expiry_date']));
	return date_comp(Today(), $alert_from) > 0 && $row['alert'] != 0;
}

function display_rows($emp_id) {

	$sql = get_sql_for_employee_documents(get_post('emp_id'), get_post('type_id'), get_post('alert'), get_post('no_alert'), get_post('expired_from'), get_post('expired_to'), get_post('issue_from'), get_post('issue_to'), get_post('string'));
	$cols = array(
		_('Doc No') => array('name'=>'id', 'ord'=>'asc', 'align'=>'center'),
		_('Document Type') => array('fun'=>'type_name'),
	    _("Document Title") => array('name'=>'doc_title'),
	    _('Issue Date') => array('name'=>'issue_date','type'=>'date', 'ord'=>'desc'),
	    _('Expiry Date') => array('name'=>'expiry_date','type'=>'date','ord'=>'desc'),
	    _('Alert') => array('fun'=>'is_alert','align'=>'center'),
	    _("Filename") => array('name'=>'filename'),
	    _("Size") => array('name'=>'filesize'),
	    _("Filetype") => array('name'=>'filetype'),
	    $cols[]	= array('insert'=>true, 'fun'=>'edit_link','align'=>'center'),
	    array('insert'=>true, 'fun'=>'view_link','align'=>'center'),
	    array('insert'=>true, 'fun'=>'download_link','align'=>'center')
	);

	if(empty($_POST['View']))
	    $cols[]	= array('insert'=>true, 'fun'=>'delete_link','align'=>'center');

	$table =& new_FrontHrm_pager('trans_tbl', $sql, $cols);
	$table->set_marker_warnings('check_warning', _('Marked rows are nearly expired'));
    $table->set_marker('check_expired', _('Marked rows are expired'));
	$table->width = "auto";

	display_FrontHrm_pager($table);
}

//----------------------------------------------------------------------------------------

start_form(true);

viewing_controls();

display_rows($_POST['emp_id']);

br();

if(empty($_POST['View'])) {
	start_table(TABLESTYLE2);

    if ($selected_id != -1) {
	    if ($Mode == 'Edit') {
		    $row = get_document($selected_id);
		    $_POST['type_id']  = $row['type_id'];
		    $_POST['doc_title']  = $row["description"];
		    $_POST['issue_date']  = sql2date($row['issue_date']);
		    $_POST['expiry_date']  = sql2date($row['expiry_date']);
		    $_POST['alert']  = $row["alert"];
		    hidden('unique_name', $row['unique_name']);
	    }	
	    hidden('selected_id', isset($_GET['DocId']) ? $_GET['DocId'] : $selected_id);
    }

    if($selected_id != -1)
	    label_row(_('Document Number:'), '&nbsp;&nbsp;'.$selected_id);
    doctype_list_row(_('Document type:'), 'type_id', null, _('Select document type'));
    text_row_ex(_("Document title:"), 'doc_title', 40);
    date_row(_('Issue date:'), 'issue_date');
    date_row(_('Expiry date:'), 'expiry_date');
    file_row(_('Attached File:'), 'filename', 'filename');
    check_row(_('Alert:'), 'alert');

    end_table(1);

    submit_add_or_update_center($selected_id == -1, '', 'process');
}

hidden('View', @$_GET['View']);
end_form();

end_page();
