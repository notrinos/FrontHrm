# FrontHrm
[FrontAccounting](http://frontaccounting.com/) Payroll & Human Resource Module

[DEMO](http://notrinos.cf)

Requirement
-----------
- FrontAccounting 2.4.x
- [dejavu font](http://frontaccounting.com/wb3/modules/download_gallery/dlc.php?file=57)

Installation
------------
1. Rename FrontHrm-master to `FrontHrm` then copy folder to the FA modules directory.
2. Copy `rep889.php` to FA reporting folder.
3. Replace `reporting/includes/reporting.inc` with `reporting.inc` in the FrontHrm.
4. For FrontAccounting versions released from 15/Dec/2017 up to now: just install and active normally. For the earlier versions, do the following:

- Comment out block of codes from lines 215 to 220 of `admin/inst_module.php`.
- Install and active the module.
- Uncomment lines 215-220 of `admin/inst_module.php`.
