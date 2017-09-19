# FrontHrm
[FrontAccounting](http://frontaccounting.com/) Payroll & Human Resource Module

Requirement
-----------
- FrontAccounting 2.4.x
- [dejavu font](http://frontaccounting.com/wb3/modules/download_gallery/dlc.php?file=57)

Installation
------------
- Copy FrontHrm folder to the FA modules directory.
- Comment out block of codes from lines 215 to 220 of "*admin/inst_module.php*".
- Copy rep889.php to FA reporting folder.
- Replace "*reporting/includes/reporting.inc*" with reporting.inc in the FrontHrm.
- Install and active the module.
- Uncomment lines 215-220 of "*admin/inst_module.php*".
