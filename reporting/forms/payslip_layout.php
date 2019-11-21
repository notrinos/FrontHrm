<?php

$this->row = $this->pageHeight - $this->topMargin;
$upper = $this->row - 2 * $this->lineHeight;
$lower = $this->bottomMargin + 2 * $this->lineHeight;
$iline1 = $upper - 7.5 * $this->lineHeight;
$iline2 = $iline1 - 8 * $this->lineHeight;
$iline3 = $iline2 - 1.5 * $this->lineHeight;
$iline4 = $iline3 - 1.5 * $this->lineHeight;
$iline5 = $iline4 - 3 * $this->lineHeight;
$iline6 = $iline5 - 1.5 * $this->lineHeight;
$iline7 = $lower;
$right = $this->pageWidth - $this->rightMargin;
$width = ($right - $this->leftMargin) / 5;
$page_width = $this->pageWidth - $this->leftMargin - $this->rightMargin;
$page_height = $this->pageHeight - $this->topMargin - $this->bottomMargin;
$icol = $this->pageWidth / 2;
$ccol = $this->cols[0] + 4;
$c2col = $ccol + 60;
$ccol2 = $icol / 2;
$mcol = $icol + 8;
$mcol2 = $this->pageWidth - $ccol2;

// Company Logo
$this->NewLine();
$logo = company_path().'/images/'.$this->company['coy_logo'];
if ($this->company['coy_logo'] != '' && file_exists($logo))
	$this->Image($logo, 0, $this->topMargin, '', 45, '', '', '', false, '300', 'C');
else {
	$this->fontSize += 4;
	$this->Font('bold');
	$this->SetTextColor(100, 100, 100);
	$this->Text($ccol, $this->company['coy_name'], $icol);
	$this->Font();
	$this->fontSize -= 4;
}

// Document title
$this->row = $this->row - $this->topMargin;
$this->NewLine(4);
$this->SetTextColor(80, 80, 80);
$this->fontSize += 20;
$this->Font('bold');
$this->TextWrap(0, $this->row, $this->pageWidth, $this->title, 'center');
$this->Font();
$this->fontSize -= 18;
$this->NewLine(3);
$this->SetTextColor(0, 0, 0);

// Document infomations box
$this->Line($this->row);
$this->LineTo($this->leftMargin, $this->row, $this->leftMargin, $this->row - 5*$this->lineHeight);
$this->LineTo($right, $this->row, $right, $this->row - 5*$this->lineHeight);
$this->NewLine(5);
$this->Line($this->row);

//Body & Signature
$this->NewLine(5);
$this->Line($this->row);

// Lines Headers
$this->NewLine();
$this->Font('bold');
$count = count($this->headers);
$this->cols[$count] = $right - 3;
for ($i = 0; $i < $count; $i++)
	$this->TextCol($i, $i + 1, $this->headers[$i]);
$this->Font();

$this->LineTo($this->leftMargin, $this->row + $this->lineHeight, $this->leftMargin, $this->row - 30*$this->lineHeight);
$this->LineTo($right, $this->row + $this->lineHeight, $right, $this->row - 30*$this->lineHeight);
$this->Line($this->row - $this->lineHeight/2);
$this->NewLine(30);
$this->Line($this->row);

$this->NewLine(2);
$this->Text($ccol + 20, _('Printout Date'), $icol);
$this->Text($right - 30 - (2*$this->rightMargin), _('Printed by'));
$this->NewLine();
$this->Font('bold');
$this->Text($ccol + 20, Today(), $icol);
$this->Text($right - 30 - (2*$this->rightMargin), $this->user);

// Footer
$footer_lines = $this->company['postal_address'].
	_(' - Phone1:').$this->company['phone'].
	_(' - Fax:').$this->company['fax'].
	_(' - Email:').$this->company['email'];

$this->row = $lower + $this->lineHeight;
$this->Font('italic');
$this->Line(2*$this->bottomMargin + $this->lineHeight);
$this->NewLine();
$this->TextWrapLines($ccol, $this->pageWidth - $this->rightMargin - $this->leftMargin, $footer_lines);
$this->Font();

$this->row = $upper;
$this->NewLine();