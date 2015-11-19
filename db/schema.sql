/*  schema.sql - ABET1 application database source file
    primary author: Roger Gee

    Database: abet
    Engine: mysql  Ver 14.14 Distrib 5.5.44, for debian-linux-gnu (x86_64)
*/

-- create and select database `abet`
DROP DATABASE IF EXISTS abet1;
CREATE DATABASE abet1;
USE abet1;

-- create table `userauth`
--  The `userauth` entity represents user authentication information including
--  passwords and system role.
CREATE TABLE userauth (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    passwd VARCHAR(256), /* one-way encryption */
    old_passwd VARCHAR(256),
    role ENUM('admin','faculty','staff','observer'),
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- create table `userprofile`
CREATE TABLE userprofile (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_userauth INT NOT NULL,
    username VARCHAR(32),
    first_name VARCHAR(32),
    middle_initial VARCHAR(32),
    last_name VARCHAR(32),
    suffix VARCHAR(8),
    gender ENUM('male','female'),
    bio VARCHAR(512),
    email_addr VARCHAR(32),
    office_phone VARCHAR(32),
    mobile_phone VARCHAR(32),
    created TIMESTAMP DEFAULT 0,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_userauth) REFERENCES userauth (id)
) ENGINE=InnoDB;

-- create table `course`
CREATE TABLE course (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(256),
    fk_coordinator INT NOT NULL, /* the person who created this entry */
    instructor VARCHAR(256),
    description VARCHAR(512),
    textbook VARCHAR(512),
    credit_hours ENUM('1','2','3'),
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_coordinator) REFERENCES userprofile (id)
) ENGINE=InnoDB;

-- create table `recipient_list`
CREATE TABLE recipient_list (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY

) ENGINE=InnoDB;

-- create intersection table `recipient_list_entry`
CREATE TABLE recipient_list_entry (
    fk_profile INT NOT NULL,
    fk_list INT NOT NULL,

    FOREIGN KEY (fk_profile) REFERENCES userprofile (id),
    FOREIGN KEY (fk_list) REFERENCES recipient_list (id)
) ENGINE=InnoDB;

-- create table `notification`
CREATE TABLE notification (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    subject varchar(512),
    fk_recipient_list INT NOT NULL,
    message VARCHAR(4096), /* HTML message content */
    kind ENUM('email','system','both'), /* 'system' messages sent internally */
    sent_time TIMESTAMP DEFAULT 0,
    intended_time TIMESTAMP DEFAULT 0,

    FOREIGN KEY (fk_recipient_list) REFERENCES recipient_list (id)
) ENGINE=InnoDB;

-- create table abet_criterion
--  a criterion is an ABET-defined category to be assessed for a program; these
--  are static and immutable (meaning they can't be expanded or modified)
CREATE TABLE abet_criterion (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    rank INT,
    description VARCHAR(4096)
) ENGINE=InnoDB;

-- create table `acl`
CREATE TABLE acl (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- create table `acl_entry`
CREATE TABLE acl_entry (
    fk_acl INT NOT NULL,
    fk_profile INT NOT NULL,

    FOREIGN KEY (fk_acl) REFERENCES acl (id),
    FOREIGN KEY (fk_profile) REFERENCES userprofile (id)
) ENGINE=InnoDB;

-- create table `program`
CREATE TABLE program (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(256),
    number VARCHAR(8),
    semester ENUM('fall','spring'), /* semester/year describe program cycle (e.g. Fall 2013) */
    year INT,
    description VARCHAR(4096),
    fk_acl INT NOT NULL,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_acl) REFERENCES acl (id)
) ENGINE=InnoDB;

-- create table `abet_characteristic`
CREATE TABLE abet_characteristic (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    level CHAR, /* the organizational level ordered by (e.g. a) */
    program_specifier VARCHAR(8), /* e.g. CS, IT, ETC. */
    short_name VARCHAR(128), /* display name */
    description VARCHAR(4096),
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- create table `abet_assessment`
--  an assessment is a characteristic that proves a criterion
CREATE TABLE abet_assessment (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_program INT NOT NULL, /* assessment bracketed under program */
    fk_characteristic INT, /* may be null if no characteristic */
    fk_criterion INT NOT NULL, /* must refer to a criterion */
    fk_acl INT NOT NULL, /* who can write to the assessment and its children */
    currency DATE,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_program) REFERENCES program (id),
    FOREIGN KEY (fk_characteristic) REFERENCES abet_characteristic (id),
    FOREIGN KEY (fk_criterion) REFERENCES abet_criterion (id),
    FOREIGN KEY (fk_acl) REFERENCES acl (id)
) ENGINE=InnoDB;

-- create table `general_content`
--  one general_content --> many 'file_upload' OR 'user_comment'
CREATE TABLE general_content (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_assessment INT NOT NULL, /* the assessment which the content supports */

    FOREIGN KEY (fk_assessment) REFERENCES abet_assessment (id)
) ENGINE=InnoDB;

-- create table `file_upload`
CREATE TABLE file_upload (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(256),
    file_contents BLOB,
    file_comment VARCHAR(1024),
    file_created TIMESTAMP DEFAULT 0,
    file_modified TIMESTAMP DEFAULT 0,
    fk_author INT NOT NULL,
    fk_content_set INT NOT NULL,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_author) REFERENCES userprofile (id),
    FOREIGN KEY (fk_content_set) REFERENCES general_content (id)
) ENGINE=InnoDB;

-- create table `user_comment`
CREATE TABLE user_comment (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    content VARCHAR(4096), /* HTML content; may include links */
    fk_author INT NOT NULL,
    fk_content_set INT NOT NULL,
    created TIMESTAMP DEFAULT 0,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_author) REFERENCES userprofile (id),
    FOREIGN KEY (fk_content_set) REFERENCES general_content (id)
) ENGINE=InnoDB;

-- create table `rubric_description`
CREATE TABLE rubric_description (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    outstanding_desc VARCHAR(1024),
    expected_desc VARCHAR(1024),
    marginal_desc VARCHAR(1024),
    unacceptable_desc VARCHAR(1024),
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- create table `rubric`
-- one rubric_description goes to many rubrics
-- threshold is a whole percentage; pass/fail threshold; e.g. 70%
CREATE TABLE rubric (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(512),
    fk_description INT NOT NULL,
    threshold DECIMAL(2,2),
    threshold_desc VARCHAR(128),
    created TIMESTAMP DEFAULT 0,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_description) REFERENCES rubric_description (id)
) ENGINE=InnoDB;

-- create table rubric_results
CREATE TABLE rubric_results (
    id INT NOT NULL PRIMARY KEY,
    action VARCHAR(4096),
    acheivement VARCHAR(4096),
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- create table `assessment_worksheet`
-- each worksheet must have an assessment rubric
-- `activity` - description of assessing activity
-- `fk_course` - can be NULL in which case `activity` is used
CREATE TABLE assessment_worksheet (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_program INT NOT NULL,
    fk_profile INT NOT NULL, /* the profile to whom the worksheet is assigned */
    fk_assessment INT NOT NULL,
    activity VARCHAR(512),
    fk_course INT,
    objective VARCHAR(512),
    instrument VARCHAR(512),
    course_of_action VARCHAR(4096),
    fk_rubric INT NOT NULL,
    fk_rubric_results INT NOT NULL,
    created TIMESTAMP DEFAULT 0,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_program) REFERENCES program (id),
    FOREIGN KEY (fk_profile) REFERENCES userprofile (id),
    FOREIGN KEY (fk_assessment) REFERENCES abet_assessment (id),
    FOREIGN KEY (fk_rubric) REFERENCES rubric (id),
    FOREIGN KEY (fk_rubric_results) REFERENCES rubric_results (id)
) ENGINE=InnoDB;

-- create table competency; a competency is also called a 'component' of the
-- assessment rubric
CREATE TABLE competency (
    id INT NOT NULL PRIMARY KEY,
    description VARCHAR(1024),
    created TIMESTAMP DEFAULT 0,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- create intersection table rubric_competency; creates a many-to-many
-- relationship between rubrics and competencies
CREATE TABLE rubric_competency (
    fk_rubric_id INT NOT NULL,
    fk_competency_id INT NOT NULL,

    FOREIGN KEY (fk_rubric_id) REFERENCES rubric (id),
    FOREIGN KEY (fk_competency_id) REFERENCES competency (id)
) ENGINE=InnoDB;

-- create table competency_results
CREATE TABLE competency_results (
    id INT NOT NULL PRIMARY KEY,
    total_students INT,
    outstanding_tally INT,
    expected_tally INT,
    marginal_tally INT,
    unacceptable_tally INT,
    comment VARCHAR(4096),
    fk_rubric_results INT NOT NULL,
    fk_competency INT NOT NULL,
    created TIMESTAMP DEFAULT 0,
    last_touch TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (fk_rubric_results) REFERENCES rubric_results (id),
    FOREIGN KEY (fk_competency) REFERENCES competency (id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- create default entities for any new instantiation of the database
-- -----------------------------------------------------------------------------

-- create admin user
INSERT INTO userauth (passwd,role)
VALUES /* this password is the hash of 'password' */
    ("$2y$10$7jgC2AF5smg8j8uLPiZ6nuhGw8d.x9IkYL9wMea7aDAzilJx4VdH6",'admin');
INSERT INTO userprofile (username,fk_userauth,created)
SELECT 'root', id, now() FROM userauth
WHERE role = 'admin'
LIMIT 1;

-- create abet criterion categories
INSERT INTO abet_criterion (rank,description)
VALUES
    (1,'Students'),
    (2,'PEOs'),
    (3,'Student Outcomes'),
    (4,'Continuous Improvement'),
    (5,'Curriculum'),
    (6,'Faculty'),
    (7,'Facilities'),
    (8,'Institutional Support');
