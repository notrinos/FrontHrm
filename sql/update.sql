DROP TABLE IF EXISTS `0_employee`;
CREATE TABLE IF NOT EXISTS `0_employee` (
    `emp_id` int(11) NOT NULL AUTO_INCREMENT,
    `emp_first_name` varchar(100) DEFAULT NULL,
    `emp_last_name` varchar(100) DEFAULT NULL,
	  `gender` tinyint(1) NOT NULL DEFAULT '0',
    `emp_address` tinytext,
    `emp_mobile` varchar(30) DEFAULT NULL,
    `emp_email` varchar(100) DEFAULT NULL,
    `emp_birthdate` date NOT NULL,
    `national_id` varchar(100) DEFAULT NULL,
    `passport` varchar(100) DEFAULT NULL,
    `bank_account` varchar(100) DEFAULT NULL,
    `tax_number` varchar(100) DEFAULT NULL,
    `emp_notes` tinytext NOT NULL,
    `emp_hiredate` date DEFAULT NULL,
    `department_id` int(11) NOT NULL,
    `position_id` int(11) NOT NULL DEFAULT '0',
    `grade_id` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
    `personal_salary` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
    `emp_releasedate` date DEFAULT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`emp_id`),
    KEY `position_id` (`position_id`),
    KEY `department_id` (`department_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_department`;
CREATE TABLE IF NOT EXISTS `0_department` (
    `dept_id` int(11) NOT NULL AUTO_INCREMENT,
    `dept_name` tinytext NOT NULL,
    `basic_account` varchar(15) NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`dept_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_position`;
CREATE TABLE IF NOT EXISTS `0_position` (
    `position_id` int(11) NOT NULL AUTO_INCREMENT,
    `position_name` text NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
    `pay_basis` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = monthly, 1 = daily',
    PRIMARY KEY (`position_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_overtime`;
CREATE TABLE IF NOT EXISTS `0_overtime` (
    `overtime_id` int(11) NOT NULL AUTO_INCREMENT,
	  `overtime_name` varchar(100) NOT NULL,
    `overtime_rate` float(5) NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`overtime_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_attendance`;
CREATE TABLE IF NOT EXISTS `0_attendance` (
    `emp_id` int(11) NOT NULL,
    `overtime_id` int(11) NOT NULL,
    `hours_no` float(5) UNSIGNED NOT NULL,
    `rate` float(5) NOT NULL DEFAULT '1',
    `att_date` date NOT NULL,
    PRIMARY KEY (`emp_id`,`overtime_id`,`att_date`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_leave`;
CREATE TABLE IF NOT EXISTS `0_leave` (
    `emp_id` int(11) NOT NULL,
    `leave_id` int(11) NOT NULL,
    `pay_rate` float(5) NOT NULL DEFAULT '1',
    `date` date NOT NULL,
    PRIMARY KEY (`emp_id`,`leave_id`,`date`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_pay_element`;
CREATE TABLE IF NOT EXISTS `0_pay_element` (
  `element_id` int(11) NOT NULL AUTO_INCREMENT,
  `element_name` varchar(100) NOT NULL DEFAULT '',
  `account_code` varchar(15) NOT NULL,
  PRIMARY KEY (`element_id`),
  UNIQUE (`account_code`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_payroll_structure`;
CREATE TABLE IF NOT EXISTS `0_payroll_structure` (
  `position_id` int(11) NOT NULL,
  `payroll_rule` text NOT NULL,
  KEY `position_id` (`position_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_salary_structure`;
CREATE TABLE IF NOT EXISTS `0_salary_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `position_id` int(11) NOT NULL,
  `grade_id` tinyint(2) NOT NULL DEFAULT '0',
  `pay_rule_id` varchar(15) NOT NULL,
  `pay_amount` double NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '0 for credit, 1 for debit',
  `is_basic` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_personal_salary_structure`;
CREATE TABLE IF NOT EXISTS `0_personal_salary_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `emp_id` int(11) NOT NULL,
  `pay_rule_id` varchar(15) NOT NULL,
  `pay_amount` double NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '0 for credit, 1 for debit',
  `is_basic` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_payslip`;
CREATE TABLE IF NOT EXISTS `0_payslip` (
  `payslip_no` int(11) NOT NULL AUTO_INCREMENT,
  `trans_no` int(11) NOT NULL DEFAULT '0',
  `emp_id` int(11) NOT NULL,
  `generated_date` date NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `leaves` int(11) NOT NULL,
  `deductable_leaves` int(11) NOT NULL,
  `payable_amount` double NOT NULL DEFAULT '0',
  `salary_amount` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`payslip_no`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_payslip_details`;
CREATE TABLE IF NOT EXISTS `0_payslip_details` (
  `payslip_no` int(11) NOT NULL AUTO_INCREMENT,
  `detail` int(11) NOT NULL DEFAULT '0',
  `amount` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`payslip_no`, `detail`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_employee_trans`;
CREATE TABLE IF NOT EXISTS `0_employee_trans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_no` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `trans_type` smallint(6) UNSIGNED NOT NULL DEFAULT '0',
  `payslip_no` int(11) NOT NULL DEFAULT '0',
  `pay_date` date NOT NULL,
  `to_the_order_of` varchar(255) NOT NULL,
  `pay_amount` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_employee_advance`;
CREATE TABLE IF NOT EXISTS `0_employee_advance` (
  `emp_trans_no` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_employee_advance_allocation`;
CREATE TABLE IF NOT EXISTS `0_employee_advance_allocation` (
  `trans_no_from` int(11) DEFAULT NULL,
  `trans_no_to` int(11) DEFAULT NULL,
  `amount` double UNSIGNED DEFAULT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_document_types`;
CREATE TABLE IF NOT EXISTS `0_document_types` (
    `type_id` int(11) NOT NULL AUTO_INCREMENT,
    `type_name` varchar(100) NOT NULL,
    `notify_before` smallint(5) UNSIGNED NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`type_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_employee_docs`;
CREATE TABLE IF NOT EXISTS `0_employee_docs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `emp_id` int(11) NOT NULL,
    `type_id` int(11) NOT NULL,
    `description` varchar(60) NOT NULL default '',
    `issue_date` date DEFAULT NULL,
    `expiry_date` date DEFAULT NULL,
    `alert` tinyint(1) NOT NULL DEFAULT '0',
    `unique_name` varchar(60) NOT NULL default '',
    `filename` varchar(60) NOT NULL default '',
    `filesize` int(11) NOT NULL default '0',
    `filetype` varchar(60) NOT NULL default '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_grade_table`;
CREATE TABLE IF NOT EXISTS `0_grade_table` (
    `grade_id` tinyint(2) NOT NULL,
    `position_id` int(11) NOT NULL,
    `amount` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`grade_id`, `position_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_leave_type`;
CREATE TABLE IF NOT EXISTS `0_leave_type` (
    `leave_id` int(11) NOT NULL AUTO_INCREMENT,
    `leave_name` varchar(100) NOT NULL,
    `leave_code` varchar(3) NOT NULL,
    `pay_rate` float(5) NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`leave_id`)
) ENGINE=InnoDB;

DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_deductleave_act';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_month_work_days';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_overtime_act';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_payable_act';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_work_hours';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_dept_based';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_grades';

INSERT INTO `0_sys_prefs` VALUES ('payroll_deductleave_act', NULL, 'varchar', 15, '');
INSERT INTO `0_sys_prefs` VALUES ('payroll_month_work_days', NULL, 'float', NULL, 26);
INSERT INTO `0_sys_prefs` VALUES ('payroll_overtime_act', NULL, 'varchar', 15, 5420);
INSERT INTO `0_sys_prefs` VALUES ('payroll_payable_act', NULL, 'varchar', 15, 2100);
INSERT INTO `0_sys_prefs` VALUES ('payroll_work_hours', NULL, 'float', NULL, 8);
INSERT INTO `0_sys_prefs` VALUES ('payroll_dept_based', NULL, 'tinyint', 1, 0);
INSERT INTO `0_sys_prefs` VALUES ('payroll_grades', NULL, 'tinyint', 2, 5);