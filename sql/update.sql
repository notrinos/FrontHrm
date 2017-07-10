CREATE TABLE IF NOT EXISTS `0_employee` (
    `emp_id` int(11) NOT NULL AUTO_INCREMENT,
    `emp_first_name` varchar(100) DEFAULT NULL,
    `emp_last_name` varchar(100) DEFAULT NULL,
    `emp_address` tinytext,
    `emp_phone` varchar(30) DEFAULT NULL,
    `emp_email` varchar(100) DEFAULT NULL,
    `emp_birthdate` date NOT NULL,
    `emp_notes` tinytext NOT NULL,
    `emp_hiredate` date DEFAULT NULL,
    `department_id` int(11) NOT NULL,
    `salary_scale_id` int(11) NOT NULL,
    `emp_releasedate` date DEFAULT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`emp_id`),
    KEY `salary_scale_id` (`salary_scale_id`),
    KEY `department_id` (`department_id`)
) ENGINE=InnoDB;