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

include_once('../reporting/includes/tcpdf.php');
include_once("../modules/FrontHrm/includes/frontHrm_db.inc");
include_once("../modules/FrontHrm/includes/frontHrm_ui.inc");

$pdf = new TCPDF("P", 'mm', 'A4', true, 'UTF-8');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(16,0,16);

$pdf->SetAutoPageBreak(TRUE, 2);

if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

//--------------------------------------------------------------------------

$x = $pdf->getPageWidth();
$y = $pdf->getPageHeight();
$img_width = 80;

$payslip = get_payslip($_POST['PARAM_0']);
$emp = get_employees(substr($payslip['person_id'], 3));
$emp_name = $emp['emp_first_name'].' '.$emp['emp_last_name'];
$emp_id = $emp['emp_id'];
if($emp['department_id'] != 0)
	$emp_dept = get_departments($emp['department_id'])['dept_name'];
else
	$emp_dept = 'Not set';
$payslip_no = $payslip['payslip_no'];
$from = get_pay_period($payslip_no)[0];
$to = get_pay_period($payslip_no)[1];

$myrow = get_company_prefs();
$comp_name = $myrow["coy_name"];
$comp_adrs = $myrow["postal_address"];
$comp_phone = $myrow["phone"];
$comp_logo = $myrow['coy_logo'];

$head = "<table border='0.5' cellpadding='5'>
				<tr>
					<td colspan=3>
						Employee: $emp_name <br />
						ID : $emp_id <br />
						Department: $emp_dept
					</td>
					<td>
						Payslip no: $payslip_no <br />
						From: ".sql2date($from)."<br />
						To : ".sql2date($to)."
					</td>
				</tr>
			</table>";

$title = "<table border='0.5' cellpadding='3'>
		 <tr><td>Description</td>
		 	 <td>Quantity</td>
			 <td>Leaves</td>
			 <td>Earning</td></tr>
		 </table>";

$contents = "<table border='0.5' cellpadding='3'>";

$overtimes = get_overtime();
$emp_payslip = get_emp_payslip($payslip_no, $emp_id);
$work_days = 0;
$leave_hours = 0;
$regular_amount = get_amount($payslip_no, $emp_id);
$overtime_amount = get_amount($payslip_no, $emp_id, true);
$total_earn = $regular_amount + $overtime_amount;

$contents .= "<tr>
				<td>Regular time</td>";

while($row = db_fetch($emp_payslip)){
	
	if($row['overtime_id'] == 0) {
		$work_days += 1;
		if($row['hours_no'] != 8)
			$leave_hours += (8 - $row['hours_no']);
	}
}

$contents .= "<td>$work_days days</td>
			 <td>$leave_hours hours</td>
			 <td style='text-align:right'>$regular_amount</td>
			 </tr>";

foreach(db_query($overtimes) as $overtime) {
	
	$emp_payslip = get_emp_payslip($payslip_no, $emp_id);
	$overtime_hours = 0;
	$time_name = $overtime['overtime_name'];
	
	while($row = db_fetch($emp_payslip)) {
		
		if($row['overtime_id'] == $overtime['overtime_id'])
			$overtime_hours += $row['hours_no'];
	}
	$contents .= "<tr>
				 <td>$time_name</td>
				 <td>$overtime_hours hours</td>
				 <td></td>";
	if($overtime_hours > 0)
		$contents .= "<td style='text-align:right'>$overtime_amount</td>";
	else
		$contents .= "<td></td>";
	
	$contents .= "</tr>";
}

$contents .= "<tr>
				<td colspan='3' style='font-weight:bold'>Total</td><td style='text-align:right'>$total_earn</td>
			 </tr>
		 </table>";

function get_pay_period($payslip_no) {
	
	$sql = "SELECT from_date, to_date FROM ".TB_PREF."payslip_detail WHERE payslip_no = $payslip_no";
	$result = db_fetch(db_query($sql, 'Could not get payslip details.'));
	$from = $result['from_date'];
	$to = $result['to_date'];
	
	return array($from, $to);
}

function get_emp_payslip($payslip_no, $emp) {
	
	$from = get_pay_period($payslip_no)[0];
	$to = get_pay_period($payslip_no)[1];
	
	$sql = "SELECT * FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp)." AND att_date BETWEEN '$from' AND '$to'";
	$result = db_query($sql, 'Could not get employee attendance data.');

	return $result;
}

function get_amount($payslip_no, $emp, $overtime = false) {
	global $Overtime_act;
	
	if(!$overtime)
		$sql = "SELECT gl.amount FROM ".TB_PREF."gl_trans AS gl, ".TB_PREF."salary_structure AS sa, ".TB_PREF."employee AS e WHERE gl.account = sa.pay_rule_id AND SUBSTRING(gl.person_id, 4) = ".db_escape($emp)." AND gl.payslip_no = $payslip_no AND e.salary_scale_id = sa.salary_scale_id AND e.emp_id = ".db_escape($emp);
	else
		$sql = "SELECT amount FROM ".TB_PREF."gl_trans WHERE account = ".$Overtime_act." AND payslip_no = $payslip_no";
	
	$result = db_query($sql, "could not get payslip details.");
	$amount = 0;
	while($row = db_fetch($result)) {
		$amount += $row['amount'];
	}
	return $amount;
}

//--------------------------------------------------------------------------

$pdf->AddPage('P', 'A4');

$pdf->Image(company_path().'/images/'.$comp_logo, $x/2 - $img_width/2, 20, $img_width);

$pdf->writeHTMLCell(2*$x/3-30, 0, 15, 40, _("Company: ").$comp_name, 0, 0, 0, true);
$pdf->writeHTMLCell($x/3-30, 0, 2*$x/3+20, 45, _("Date: ").Today(), 0, 0, 0, true);
$pdf->writeHTMLCell($x, 0, 15, 45, _("Address: ").$comp_adrs, 0, 0, 0, true);
$pdf->writeHTMLCell($x, 0, 15, 50, _("Phone: ").$comp_phone, 0, 0, 0, true);

$pdf->SetFont('dejavu', 'BI', 25);
$pdf->Write(0, _('Payslip'), '', 0, 'C', true, 0, false, false, 0);

$pdf->SetFont('dejavu', '', 10);
$pdf->writeHTMLCell($x-30, 0, 15, 85, $head, 0, 0, 0, true);
$pdf->writeHTML("");

$pdf->SetFont('helvetica', 'B', 10);
$pdf->writeHTMLCell($x-30, 0, 15, 123.5, $title, 0, 0, 0, true, 'C');
$pdf->setFont('dejavu', '', 10);
$pdf->writeHTMLCell($x-30, 0, 15, 130, $contents, 0, 0, 0, true);

//--------------------------------------------------------------------------

$pdf->Output(__DIR__.'/../modules/FrontHrm/reports/payslip_'.$payslip_no.'.pdf', 'I');


