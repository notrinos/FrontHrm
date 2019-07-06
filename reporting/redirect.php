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
global $page_security;
$page_security = 'SA_OPEN';
include_once($path_to_root . '/includes/session.inc');

if (!isset($_POST['REP_ID'])) {
	$def_pars = array(0, 0, '', '', 0, '', '', 0);
	$rep = $_POST['REP_ID'] = $_GET['REP_ID'];

	for($i=0; $i<3; $i++) {
		$_POST['PARAM_'.$i] = isset($_GET['PARAM_'.$i]) ? $_GET['PARAM_'.$i] : $def_pars[$i];
	}
}

$rep = preg_replace('/[^a-z_0-9]/i', '', $_POST['REP_ID']);

$rep_file = $path_to_root."/modules/FrontHrm/reporting/rep$rep.php";

if (file_exists($rep_file))
	require($rep_file);
else {
	page('');
	display_error(_('Report') . '&nbsp;' . $rep . '&nbsp;' . _('could not be found.'));
	display_footer_exit();
}
exit();