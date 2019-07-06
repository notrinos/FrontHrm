# FrontHrm
[FrontAccounting](http://frontaccounting.com/) Payroll & Human Resource Module

[DEMO](http://notrinos.com/fa/index.php)

[Forum Discussion](http://frontaccounting.com/punbb/viewtopic.php?id=6860)

![dashboard](http://notrinos.com/misc/dashboard.jpg)

Requirement
-----------
- FrontAccounting 2.4.x
- [dejavu font](http://frontaccounting.com/wb3/modules/download_gallery/dlc.php?file=57)

Installation
------------
##### From 01/Apr/2018 FrontHrm can be installed without any changes in FA core so following steps 2, 3, 4 can be ignored.
1. Rename the folder to `FrontHrm` then copy to the FA `modules` directory.
2. ~~Copy `rep889.php` to FA `reporting` folder.~~
3. ~~Copy `dejavu font files` to FA `reporting/font` folder.~~
4. ~~Replace `reporting/includes/reporting.inc` with `reporting.inc` in the FrontHrm.~~
5. For FrontAccounting 2.4.4 up to now: just install and active normally. For the earlier versions, do the following:

- Comment out block of codes from lines 215 to 220 of `admin/inst_module.php`.
- Install and active the module.
- Uncomment lines 215-220 of `admin/inst_module.php`.
