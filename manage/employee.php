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

$page_security = 'SA_EMPL';
$path_to_root  = '../../..';

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_db.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_ui.inc");

//--------------------------------------------------------------------------

foreach(db_query(get_employees(false, true)) as $emp_row) {
	
	if(isset($_POST[$emp_row['emp_id']])) {
		
		$_SESSION['EmpId'] = $emp_row['emp_id'];
		$_POST['_tabs_sel'] = 'add';
		$Ajax -> activate('_page_body');
	}
}

$cur_id = isset($_SESSION['EmpId']) ? $_SESSION['EmpId'] : '';

$upload_file = "";
$avatar_path = $path_to_root."/modules/FrontHrm/images/avatar/";
if (isset($_FILES['pic']) && $_FILES['pic']['name'] != '') {
	
	$result = $_FILES['pic']['error'];
 	$upload_file = 'Yes';
	$filename = $avatar_path;
	if (!file_exists($filename))
		mkdir($filename);
	
	$filename .= emp_img_name($cur_id).".jpg";
	
	if ($_FILES['pic']['error'] == UPLOAD_ERR_INI_SIZE) {

		display_error(_('The file size is over the maximum allowed.'));
		$upload_file ='No';
	}
	elseif ($_FILES['pic']['error'] > 0) {

		display_error(_('Error uploading file.'));
		$upload_file ='No';
	}
	if ((list($width, $height, $type, $attr) = getimagesize($_FILES['pic']['tmp_name'])) !== false)
		$imagetype = $type;
	else
		$imagetype = false;

	if ($imagetype != IMAGETYPE_GIF && $imagetype != IMAGETYPE_JPEG && $imagetype != IMAGETYPE_PNG) {

		display_warning( _('Only graphics files can be uploaded'));
		$upload_file ='No';
	}
	elseif (!in_array(strtoupper(substr(trim($_FILES['pic']['name']), strlen($_FILES['pic']['name']) - 3)), array('JPG','PNG','GIF'))) {

		display_warning(_('Only graphics files are supported - a file extension of .jpg, .png or .gif is expected'));
		$upload_file ='No';
	}
	elseif ( $_FILES['pic']['size'] > ($SysPrefs->max_image_size * 1024)) {

		display_warning(_('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . $SysPrefs->max_image_size);
		$upload_file ='No';
	} 
	elseif ( $_FILES['pic']['type'] == "text/plain" ) {

		display_warning( _('Only graphics files can be uploaded'));
        $upload_file ='No';
	}
	elseif (file_exists($filename)) {

		$result = unlink($filename);
		if (!$result) {
			display_error(_('The existing image could not be removed'));
			$upload_file ='No';
		}
	}
	if ($upload_file == 'Yes')
		$result  =  move_uploaded_file($_FILES['pic']['tmp_name'], $filename);
	
	$Ajax->activate('_page_body');
}

//--------------------------------------------------------------------------

function can_process() {
	
	if(strlen($_POST['EmpFirstName']) == 0 || $_POST['EmpFirstName'] == "") {

		display_error(_("The employee first name must be entered."));
		set_focus('EmpFirstName');
		return false;
	}
	if(strlen($_POST['EmpLastName']) == 0 || $_POST['EmpLastName'] == "") {

		display_error(_("The employee last name must be entered."));
		set_focus('EmpLastName');
		return false;
	}
	if(!filter_var($_POST['EmpEmail'], FILTER_VALIDATE_EMAIL)) {

		display_error(_("Invalid email."));
		set_focus('EmpEmail');
		return false;
	}
	if (!is_date($_POST['EmpBirthDate'])) {

		display_error( _("Invalid birth date."));
		set_focus('EmpBirthDate');
		return false;
	}
	if (!is_date($_POST['EmpHireDate']) && $_POST['EmpHireDate'] != null && $_POST['EmpHireDate'] != '00/00/0000') {

		display_error( _("Invalid date."));
		set_focus('EmpHireDate');
		return false;
	}
	if (get_post('EmpInactive') == 1) {

	    if (!is_date($_POST['EmpReleaseDate'])) {
		display_error( _("Invalid release date."));
		set_focus('EmpReleaseDate');
		return false;
	    }
	}
	return true;
}

//--------------------------------------------------------------------------

function can_delete($cur_id) {

	$employee = get_employees($cur_id, true);

	if($employee['emp_hiredate'] && $employee['emp_hiredate'] != '0000-00-00') {
		display_error('Employed person cannot be deleted.');
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------

function id_link($row) {
	return button($row['emp_id'], $row['emp_id']);
}
function get_name($row) {
	return $row['emp_first_name'].' '.$row['emp_last_name'];
}
function gender_name($row) {
	return $row['gender'] == 0 ? 'Female' : 'Male';
}
function emp_hired($row) {
	return ($row['emp_hiredate'] == '0000-00-00') ? 'Not hired' : "<center>".sql2date($row['emp_hiredate'])."</center>";
}
function emp_department($row) {
	
	if($row['emp_hiredate'] == '0000-00-00' || $row['department_id'] == 0)
		return 'Not selected';
	else
		return get_departments($row['department_id'])['dept_name'];

}

function employees_table() {
	
	$_SESSION['EmpId'] = '';
	if(db_has_employee()) {
		
		$sql = get_employees(false, check_value('show_inactive'), get_post('DeptId'));
		
		start_table(TABLESTYLE_NOBORDER);
		start_row();
		department_list_cells(_('Department:'), 'DeptId', null, _('All departments'), true);
		check_cells(_("Show resigned:"), 'show_inactive', null, true);
		end_row();
		end_table(1);
		
        $cols = array(
          _('ID') => array('fun'=>'id_link'),
		  'first_name' => 'skip',
          _('Name') => array('fun'=>'get_name'),
		  _('Gender') => array('fun'=>'gender_name'),
		  'address' => 'skip',
		  _('Mobile') => array(),
		  _('Email'),
		  _('Birth') => array('type'=>'date'),
		  'notes' => 'skip',
		  _('Hired Date') => array('fun'=>'emp_hired'),
		  _('Department') => array('fun'=>'emp_department')
        );

        $table =& new_db_pager('student_tbl', $sql, $cols);
        $table->width = "80%";
	
	    display_note(_('Press Id to edit employee details.'));
        display_db_pager($table);
	}
	else
		display_note(_("No employee defined."), 1);
}

//--------------------------------------------------------------------------

function employee_settings($cur_id) {
	global $path_to_root, $avatar_path;
	
	if($cur_id) {
		$employee = get_employees($cur_id, true);
		$_POST['EmpFirstName'] = $employee['emp_first_name'];
		$_POST['EmpLastName'] = $employee['emp_last_name'];
		$_POST['EmpGender'] = $employee['gender'];
		$_POST['EmpAddress'] = $employee['emp_address'];
		$_POST['EmpMobile'] = $employee['emp_mobile'];
		$_POST['EmpEmail'] = $employee['emp_email'];
		$_POST['EmpBirthDate'] = sql2date($employee['emp_birthdate']);
		$_POST['EmpNotes'] = $employee['emp_notes'];
		$_POST['EmpHireDate'] = sql2date($employee['emp_hiredate']);
		$_POST['DepartmentId'] = $employee['department_id'];
		$_POST['EmpSalary'] = $employee['salary_scale_id'];
		$_POST['EmpReleaseDate'] = sql2date($employee['emp_releasedate']);
		$_POST['EmpInactive'] = $employee['inactive'];
	}
	start_outer_table(TABLESTYLE2);

	table_section(1);
	
	hidden('emp_id');

	file_row(_("Image File") . ":", 'pic', 'pic');
	$emp_img_link = "";
	$check_remove_image = false;
	if ($cur_id && file_exists($avatar_path.emp_img_name($cur_id).".jpg")) {
		$emp_img_link .= "<img id='emp_img' alt = '[".$cur_id.".jpg".
			"]' src='".$avatar_path.emp_img_name($cur_id).
			".jpg?nocache=".rand()."'"." height='100'>";
		$check_remove_image = true;
	} 
	else 
		$emp_img_link .= "<img id='emp_img' alt = '.jpg' src='$avatar_path"."no_image.svg' height='100'>";

	label_row("&nbsp;", $emp_img_link);
	if ($check_remove_image)
		check_row(_("Delete Image:"), 'del_image');
	
	table_section_title(_("Personal Information"));

	if($cur_id)
		label_row(_('Employee Id:'), $cur_id);

	text_row(_("First Name:"), 'EmpFirstName', get_post('EmpFirstName'), 37, 50);
	text_row(_("Last Name:"), 'EmpLastName', get_post('EmpLastName'), 37, 50);
	gender_radio_row(_('Gender:'), 'EmpGender', get_post('EmpGender'));
	textarea_row(_("Address:"), 'EmpAddress', get_post('EmpAddress'), 35, 5);
	text_row(_("Mobile:"), 'EmpMobile', get_post('EmpMobile'), 37, 30);
	email_row(_("e-Mail:"), 'EmpEmail', get_post('EmpEmail'), 37, 100);
	date_row(_("Birth Date:"), 'EmpBirthDate', null, null, 0, 0, -13);
	
	table_section(2);
	
	table_section_title(_("Job Information"));
	
	textarea_row(_("Notes:"), 'EmpNotes', null, 35, 5);
	date_row(_("Hire Date:"), 'EmpHireDate', null, null, 0, 0, 1001);
	
	if($cur_id) {
		if($employee['emp_hiredate'] != '0000-00-00')
			department_list_row(_('Department:'), 'DepartmentId', null, _('Select department'));
		else {
			label_row('Department:', _('Set hire date first'));
			hidden('DepartmentId');
		}
	}
	else
		department_list_row(_('Department:'), 'DepartmentId', null, _('Select department'));
		
	salaryscale_list_row(_('Salary:'), 'EmpSalary', null, _('Select salary scale'));
	if($cur_id) {
		check_row('Resigned:', 'EmpInactive');
		date_row(_("Release Date:"), 'EmpReleaseDate', null, null, 0, 0, 1001);
	}
	else{
		hidden('EmpInactive');
		hidden('EmpReleaseDate');
	}
	end_outer_table(1);
	
	div_start('controls');
	
	if ($cur_id) {
		
		submit_center_first('addupdate', _("Update Employee"), _('Update employee details'), 'default');
		submit_return('select', get_post('emp_id'), _("Select this employee and return to document entry."));
		submit_center_last('delete', _("Delete Employee"), _('Delete employee data if have been never used'), true);
	}
	else
		submit_center('addupdate', _("Add New Employee Details"), true, '', 'default');
	
	div_end();
}

//--------------------------------------------------------------------------

if (isset($_POST['addupdate'])) {
	
	if(!can_process())
		return;
	write_employee(
		$cur_id,
		$_POST['EmpFirstName'],
		$_POST['EmpLastName'],
		$_POST['EmpGender'],
		$_POST['EmpAddress'],
		$_POST['EmpMobile'],
		$_POST['EmpEmail'],
		$_POST['EmpBirthDate'],
		$_POST['EmpNotes'],
		$_POST['EmpHireDate'],
		$_POST['DepartmentId'],
		$_POST['EmpSalary'],
		$_POST['EmpReleaseDate'],
		$_POST['EmpInactive']
	);
	$_SESSION['EmpId'] = db_insert_id();

	if (check_value('del_image')) {
		$filename = $avatar_path.emp_img_name($cur_id).".jpg";
		if (file_exists($filename))
			unlink($filename);
	}
	if($cur_id)
		display_notification(_("Employee details has been updated."));
	else
		display_notification(_("A new employee has been added."));
	
	$Ajax->activate('_page_body');
}
elseif(isset($_POST['delete'])) {

	if(!can_delete($cur_id))
		return;
	delete_employee($cur_id);
	display_notification(_("Employee details has been deleted."));
	$Ajax -> activate('_page_body');
}

//--------------------------------------------------------------------------

page(_($help_context = "Manage Employees"), false, false, "", $js);

start_form(true);

tabbed_content_start(
	'tabs',
	array(
		'list' => array(_('Employees &List'), 999),
		'add' => array(_('&Add/Edit Employee'), 999)
	)
);

if(get_post('_tabs_sel') == 'list')
	employees_table();
elseif(get_post('_tabs_sel') == 'add')
	employee_settings($cur_id);

br();

tabbed_content_end();

end_form();
end_page();
