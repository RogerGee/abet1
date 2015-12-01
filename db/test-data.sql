/* test-data.sql - test data for ABET1 Database

    This must be run immediately after the schema has been sourced.
*/
use abet1;

-- create users
INSERT INTO userauth (passwd,role) VALUES
    ('$2y$10$yhwEBi8Wfe4qKir0Ks./qOPsCjjJMh3nFCu16w0rfLs4fjgIN1w6S','faculty'), /* gospel1 */
    ('$2y$10$KstYzCbqAwt06LlC2k.2ROx6yYRvjE62CFTzSgE6rvADruHnKBabi','faculty'), /* gospel2 */
    ('$2y$10$e0a45re3N7/eaylGfuf7iuOye1XaFt7cySbj/Mc7nuZEcpjnWR.c2','faculty'), /* gospel3 */
    ('$2y$10$yAhLtISfMnWtLh88YChvLOIoAHhJfixwcpJ7NRDX1JUgbJnTuYMfi','faculty'); /* gospel4 */
INSERT INTO userprofile (fk_userauth,username,first_name,last_name,gender,email_addr) VALUES
    (2,'matthew','Matthew','Disciple','male','matt@jesus.org'),
    (3,'mark','Marcus','Disciple','male','mark@jesus.org'),
    (4,'luke','Lucas','Disciple','male','luke@jesus.org'),
    (5,'john','John','Disciple','male','john@jesus.org');

-- create courses
INSERT INTO course (title,course_number,fk_coordinator,instructor,description,textbook,credit_hours) VALUES
    ('Scripting 1','CS 115',2,'Ray Pettit','Teach da Python','Learn da Python!','3'),
    ('Programming 1','CS 120',3,'John Homer','Teach da C--','Learn da C--','3'),
    ('Intro to VB','CS 567',4,'Roger Gee','Teaches fundamentals of the Visual Basic Programming Language','VB DOT NET BOOK','1'),
    ('Underwater BB-stacking in Java','CS 318',5,'James Prather','Stack \'em all','jStack','3');

-- create programs
INSERT INTO program (name,abbrv,semester,year,description) VALUES
    ('Computer Science','CS','Fall',2012,'Teaches kids how to compute stuff'),
    ('Information Technology','IT','Fall',2013,'Instructs fundaments of plugging in ethernet cables');

-- create characteristics
INSERT INTO abet_characteristic (level,program_specifier,short_name,description) VALUES
    ('a',NULL,'Apply Knowledge','An ability to appy knowledge of computing and mathematics appropriate to the discipline'),
    ('b',NULL,'Analyze, Identify and Define','An ability to analyze a problem, and identify and define the computing requirements appropriate to its solution'),
    ('c',NULL,'Design, Implement and Evaluate','An ability to design, implement, and evaluate a computer-based system, process, component, or program to meet desired needs'),
    ('d',NULL,'Teamwork','An ability to function effectively on teams to accomplish a common goal'),
    ('e',NULL,'Understand Issues','An understanding of professional, ethical, legal, security and social issues and responsibilities'),
    ('f',NULL,'Communication','An ability to communicate effectively with a range of audiences'),
    ('g',NULL,'Local/Global Impact','An ability to analyze the local and global impact of computing on individuals, organizations, and society'),
    ('h',NULL,'Professional Development','Recognition of the need for and an ability to engage in continuing professional development'),
    ('i',NULL,'Skills and Techniques','An ability to use current techniques, skills, and tools necessary for computing practice.'),
    ('j','CS','Computer Science Theory','An ability to apply mathematical foundations, algorithmic principles, and computer science theory in the modeling and design of computer-based systems in a way that demonstrates comprehension of the tradeoffs involved in design choices.'),
    ('k','CS','Software Design and Development','An ability to apply design and development principles in the construction of software systems of varying complexity.'),
    ('j','IT','Apply Core Information Technologies','An ability to use and apply current technical concepts and practices in the core information   technologies.'),
    ('k','IT','Identify User Needs','An ability to identify and analyze user needs and take them into account in the selection, creation, evaluation and administration of computer-based systems.'),
    ('l','IT','Integrate IT-solutions','An ability to effectively integrate IT-based solutions into the user environment.'),
    ('m','IT','Best Practices','An understanding of best practices and standards and their application.'),
    ('n','IT','Effective Project Plan','An ability to assist in the creation of an effective project plan.');

-- create acls for assessments
INSERT INTO acl () VALUES();
INSERT INTO acl_entry (fk_acl,fk_profile)
SELECT LAST_INSERT_ID(), userprofile.id
FROM userprofile
WHERE username = 'mark';

INSERT INTO acl () VALUES();
INSERT INTO acl_entry (fk_acl,fk_profile)
SELECT LAST_INSERT_ID(), userprofile.id
FROM userprofile
WHERE username = 'mark' OR username = 'luke';

INSERT INTO acl () VALUES();
INSERT INTO acl_entry (fk_acl,fk_profile)
SELECT LAST_INSERT_ID(), userprofile.id
FROM userprofile
WHERE username = 'matthew' OR username = 'john';

INSERT INTO acl () VALUES();
INSERT INTO acl_entry (fk_acl,fk_profile)
SELECT LAST_INSERT_ID(), userprofile.id
FROM userprofile
WHERE username = 'john';

-- create assessments
INSERT INTO abet_assessment (fk_program,fk_characteristic,fk_criterion,fk_acl,name) VALUES
    (1,10,3,1,'Intro Scripting'),
    (1,11,3,2,'Intro Programming'),
    (1,4,3,3,'Team Programming'),
    (2,2,3,4,'Random');

-- create rubric descriptions for rubrics
INSERT INTO rubric_description (outstanding_desc,expected_desc,marginal_desc,unacceptable_desc) VALUES
(
    'Clear evidence that all basic programming concepts have been mastered and that demonstration of mastery of many of the concepts exceeds expectations.',
    'Clear evidence that all basic programming concepts have been mastered.',
    'Clear evidence that many basic programming concepts have been mastered, but some basic programming concepts have not been mastered.',
    'Clear evidence that basic programming concepts have not been mastered.'
);

-- create rubrics for assessment worksheets
INSERT INTO rubric (name,fk_description,threshold,threshold_desc) VALUES
(
    'CS 115 Programming Concepts Assessment Rubric',
    1,
    0.7,
    'At least 70% of the students should achieve expected or outstanding performance.'
),
(
    'CS 120 Programming Concepts Assessment Rubric',
    1,
    0.6,
    'At least 60% of the students should achieve expected or outstanding performance.'
),
(
    'CS 567 Basically Visual Assessment Workamasheet Ruberarick',
    1,
    1.0,
    'All of the students must pass or face a painful death'
),
(
    'CS 318 Visual Basically Rubric',
    1,
    0.01,
    'Only some of the students do we actually care about (1%)'

);
-- create rubric results for assessment worksheets and competency results
INSERT INTO rubric_results (total_students) VALUES
(
    23
),
(
    20
),
(
    10
),
(
    5
);

-- create competency results
INSERT INTO competency_results (pass_fail_type,outstanding_tally,expected_tally,marginal_tally,unacceptable_tally,fk_rubric_results,competency_desc) VALUES
    (FALSE,15,8,0,0,1,'Demonstrate knowledge of fundamental terms and concepts associated with introductory level computer programming.'), /* CS 115 Assessment Rubric */
    (FALSE,8,8,2,5,1,'Given a simple problem statement, develop a program to solve the problem.'),
    (FALSE,13,6,0,4,1,'In a structured problem setting, trace the code and correctly identify the basic programming constructs, such as data types, assignment statements, arithmetic operators, relational and logical operators, decision processing steps, repetition, and mathematical functions.'),
    (FALSE,7,0,7,9,1,'When tracing the steps of ill-posed source code, identify the sections or steps that process or function incorrectly and/or produce errors (such as syntax errors) and specify solutions.'),

    (FALSE,10,5,3,2,2,'Demonstrate knowledge of fundamental terms and concepts associated with introductory level computer programming.'), /* CS 120 Assessment Rubric */
    (FALSE,10,5,3,2,2,'Given a simple problem statement, develop a program to solve the problem.'),
    (FALSE,10,5,3,2,2,'In a structured problem setting, trace the code and correctly identify the basic programming constructs, such as data types, assignment statements, arithmetic operators, relational and logical operators, decision processing steps, repetition, and mathematical functions.'),
    (FALSE,10,5,3,2,2,'When tracing the steps of ill-posed source code, identify the sections or steps that process or function incorrectly and/or produce errors (such as syntax errors) and specify solutions.'),

    (TRUE,10,0,0,0,3,'Demonstrate knowledge of fundamental terms and concepts associated with introductory level computer programming.'), /* CS 567 Assessment Rubric */
    (TRUE,10,0,0,0,3,'Given a simple problem statement, develop a program to solve the problem.'),
    (TRUE,10,0,0,0,3,'In a structured problem setting, trace the code and correctly identify the basic programming constructs, such as data types, assignment statements, arithmetic operators, relational and logical operators, decision processing steps, repetition, and mathematical functions.'),
    (TRUE,9,0,0,0,3,'When tracing the steps of ill-posed source code, identify the sections or steps that process or function incorrectly and/or produce errors (such as syntax errors) and specify solutions.'),

    (FALSE,4,1,0,0,4,'Demonstrate knowledge of fundamental terms and concepts associated with introductory level computer programming.'), /* CS 318 Assessment Rubric */
    (FALSE,3,2,0,0,4,'Given a simple problem statement, develop a program to solve the problem.'),
    (FALSE,2,1,1,1,4,'In a structured problem setting, trace the code and correctly identify the basic programming constructs, such as data types, assignment statements, arithmetic operators, relational and logical operators, decision processing steps, repetition, and mathematical functions.'),
    (FALSE,1,1,1,2,4,'When tracing the steps of ill-posed source code, identify the sections or steps that process or function incorrectly and/or produce errors (such as syntax errors) and specify solutions.');

-- create assessment worksheets
INSERT INTO assessment_worksheet (fk_assessment,fk_course,objective,instrument,course_of_action,fk_rubric,fk_rubric_results) VALUES
(
    1,
    1,
    'Develop basic skill in programming in a scripting-based language',
    'Final Exam (both the written component and the practical component)',
    'Incorporate more examples of ill-formed code into the class presentations to aid in code tracing capabilities. After programming quizzes are given, select certain submissions that are not functioning correctly and trace through those with the class to identify the problems in those programs.',
    1,
    1
),
(
    2,
    2,
    'Teach people how to write good C-- Code',
    'Final Exam',
    'Make fun of poor student work to encourage success',
    2,
    2
),
(
    3,
    3,
    'Develop basic skills in Visual Basic',
    'Strenuous physical activity and pain',
    'Better enhanced interrogation techniques',
    3,
    3
),
(
    4,
    4,
    'Nothing really',
    'Sure',
    'Yes and no',
    4,
    4
);

-- test comments/file uploads by adding general_content entity to assessment #3
INSERT INTO general_content (fk_assessment) VALUES (3);
INSERT INTO user_comment (content,fk_author,fk_content_set,created) VALUES
    ('This is a comment made by Matthew.',2,LAST_INSERT_ID(),NOW()),
    ('This is a comment made by John.',5,LAST_INSERT_ID(),NOW());

INSERT INTO file_upload (file_name,file_contents,file_comment,file_created,fk_author,fk_content_set) VALUES
    ('a.txt','Hello, World!','This is exciting.',NOW(),2,1),
    ('b.txt','Bye-Bye, World!','This is sad.',NOW(),5,1);
