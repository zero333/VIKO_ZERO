################################################################################
#
# First of all, lets convert all the tables to UTF-8
#
################################################################################
ALTER TABLE Courses       CONVERT TO CHARACTER SET utf8;
ALTER TABLE Courses_Users CONVERT TO CHARACTER SET utf8;
#ALTER TABLE Files         CONVERT TO CHARACTER SET utf8;
#ALTER TABLE Files_Folders CONVERT TO CHARACTER SET utf8;
ALTER TABLE Forms         CONVERT TO CHARACTER SET utf8;
ALTER TABLE Lessons       CONVERT TO CHARACTER SET utf8;
ALTER TABLE Links         CONVERT TO CHARACTER SET utf8;
ALTER TABLE Links_Folders CONVERT TO CHARACTER SET utf8;
ALTER TABLE Marks         CONVERT TO CHARACTER SET utf8;
ALTER TABLE Messages      CONVERT TO CHARACTER SET utf8;
ALTER TABLE Schools       CONVERT TO CHARACTER SET utf8;
ALTER TABLE Topics        CONVERT TO CHARACTER SET utf8;
ALTER TABLE Users         CONVERT TO CHARACTER SET utf8;

# finally change the default character set of the database,
# so that if a new table is created to database, it will use UTF-8.
ALTER DATABASE DEFAULT CHARACTER SET utf8;


################################################################################
#
# Forms
#
################################################################################

# The Forms should also have unique numeric indexes,
# by which they can be referenced.
# To accomplish this, we first rename the old forms table
RENAME TABLE Forms TO OldForms;

# Create new forms table, which contains also an artificial
# numeric primary key, but also ensures unique combinations
# of school id-s and form names (instead of form numbers).
# After that is done, we copy the data from the OldForm to Form.
CREATE TABLE Forms (
    form_id    INT          AUTO_INCREMENT NOT NULL,
    form_name  VARCHAR(32)  NOT NULL,
    school_id  INT          NOT NULL,
    PRIMARY KEY (form_id),
    UNIQUE INDEX (form_name, school_id),
    FOREIGN KEY (school_id)
        REFERENCES Schools (school_id)
)
AS SELECT
    '' AS form_id,
    form_number AS form_name,
    school_id
FROM
    OldForms;

# At last, remove the old table.
DROP TABLE OldForms;



################################################################################
#
# Users
#
################################################################################

# The levels in Users table aren't quite descriptive,
# we replace those with user_group field, which contains
# enumerated values.
ALTER TABLE Users
ADD user_group ENUM('STUDENT','TEACHER','SCHOOLADMIN','ADMIN') DEFAULT 'STUDENT' NOT NULL
AFTER level;

UPDATE Users SET user_group='TEACHER' WHERE level='1';
UPDATE Users SET user_group='SCHOOLADMIN' WHERE level='2';
UPDATE Users SET user_group='ADMIN' WHERE level='3';

ALTER TABLE Users DROP level;


# We need to remove the description field from Users tables,
# which actually references the form_name field in Forms table
# if the user is STUDENT, otherwise it's just useless.
RENAME TABLE Users TO OldUsers;

CREATE TABLE Users (
    user_id                 int AUTO_INCREMENT NOT NULL,
    username                varchar(64) NOT NULL,
    password                varchar(41) NOT NULL,
    firstname               varchar(32) NOT NULL,
    lastname                varchar(32) NOT NULL,
    email                   varchar(64) NOT NULL,
    user_group              enum('STUDENT','TEACHER','SCHOOLADMIN','ADMIN') DEFAULT 'STUDENT' NOT NULL,
    school_id               int NOT NULL,
    form_id                 int NULL,
    PRIMARY KEY (user_id),
    UNIQUE INDEX (username),
    FOREIGN KEY (school_id)
        REFERENCES Schools (school_id),
    FOREIGN KEY (form_id)
        REFERENCES Forms (form_id)
)
AS SELECT
    user_id,
    username,
    password,
    firstname,
    lastname,
    email,
    user_group,
    OldUsers.school_id,
    form_id
FROM
    OldUsers
    LEFT JOIN
    Forms ON (
        OldUsers.description = Forms.form_name AND
        OldUsers.school_id = Forms.school_id AND
        OldUsers.user_group='STUDENT'
    );

DROP TABLE OldUsers;


# The next query performs test, to see, if all students are
# associated with a form. The test succeeds, if no rows are returned.
#
# SELECT * FROM Students WHERE (user_group='STUDENT') AND (form_id IS NULL);




################################################################################
#
# Schools
#
################################################################################

# All the schools should have a unique name.
ALTER TABLE Schools MODIFY school_name VARCHAR(128) NOT NULL UNIQUE;




################################################################################
#
# Courses
#
################################################################################

# Many fields in this table need more descriptive names.
# Also the lengths of the fiels seem to be too small.

# All the varchar(64)/(128) fields are converted to 255 chars.
ALTER TABLE Courses
CHANGE name course_name VARCHAR(255) NOT NULL,
CHANGE time scheduled_time VARCHAR(255) NOT NULL,
CHANGE consult consultation VARCHAR(255) NOT NULL;

# All the varchar(32)fields are converted to 64 chars.
ALTER TABLE Courses
CHANGE description course_subname VARCHAR(64) NOT NULL,
CHANGE classroom classroom VARCHAR(64) NOT NULL;

# Many of the text fields are just renamed.
ALTER TABLE Courses
CHANGE goal description TEXT NOT NULL,
CHANGE marks assessment TEXT NOT NULL;

# Also the teacher field should describe it's content
ALTER TABLE Courses
CHANGE teacher teacher_id INT NOT NULL;

# A year number or semester should also be connected with the course,
# but at the time being, I don't know really how this should be implemented.

#
# For some odd reason the old VIKO databases may not have a key defined
# for the teacher_id (formerly teacher) column.
# That's why we drop the possible indexes and create a new one.
ALTER TABLE Courses DROP FOREIGN KEY teacher;
ALTER TABLE Courses DROP FOREIGN KEY teacher_id;
ALTER TABLE Courses ADD FOREIGN KEY (teacher_id) REFERENCES Users (user_id);



################################################################################
#
# Lessons
#
################################################################################

# In Lessons table the primary key was composed of course_id and lesson_num.
# The last one was a key, wich was unique in the range of one course, but
# it was generated by PHP, not by the database itself. Plus the generation
# process was quite clumsy and buggy.
#
# As there seem to be no real need for numbering lessons inside one course
# from 1 to n, the lesson_num field is removed and replaced with lesson_id,
# which is a unique primary key for any lesson.
#
# Also, many of the field names weren't descriptive enough, so they are renamed.
#
# For storing dates, the TIMESTAMP field was used, which was not approriate,
# so now they are changed to DATE type instead.
#
RENAME TABLE Lessons TO Old_Lessons;

CREATE TABLE Lessons (
    lesson_id               int NOT NULL AUTO_INCREMENT,
    lesson_num              int NOT NULL,
    course_id               int NOT NULL,
    lesson_topic            text NOT NULL,
    lesson_date             date NOT NULL,
    hometask                text,
    hometask_date           date,
    marktype                enum('LESSON', 'PRELIM') DEFAULT 'LESSON' NOT NULL,
    PRIMARY KEY (lesson_id),
    FOREIGN KEY (course_id)
        REFERENCES Courses (course_id)) AS
SELECT
    '' AS lesson_id,
    lesson_num,
    course_id,
    topics AS lesson_topic,
    date AS lesson_date,
    hometask,
    term AS hometask_date,
    marktype
FROM
    Old_Lessons
ORDER BY
    course_id ASC,
    lesson_date ASC,
    lesson_num ASC;

DROP TABLE Old_Lessons;




################################################################################
#
# Marks
#
################################################################################

#
# Because we are going to replace lesson_num in Lessons with lesson_id,
# we have to also change Marks, so it would point to lesson_id instead of
# lesson_num and course_id.
#

RENAME TABLE Marks TO Old_Marks;

CREATE TABLE Marks (
    lesson_id               int NOT NULL,
    user_id                 int NOT NULL,
    mark                    varchar(64) NOT NULL,
    PRIMARY KEY (lesson_id, user_id),
    FOREIGN KEY (lesson_id)
        REFERENCES Lessons (lesson_id),
    FOREIGN KEY (user_id)
        REFERENCES Users (user_id))
SELECT
    lesson_id,
    user_id,
    mark
FROM
    Old_Marks
    JOIN
    Lessons ON
    (
        Old_Marks.lesson_num=Lessons.lesson_num
        AND
        Old_Marks.course_id=Lessons.course_id
    );

DROP TABLE Old_Marks;

#
# Now is safe to also remove the lesson_num field from Lessons table.
#
ALTER TABLE Lessons
DROP COLUMN lesson_num;




################################################################################
#
# Courses_Users
#
################################################################################

#
# These fields are just redundant, the values will be calculated directly
#

ALTER TABLE Courses_Users
DROP COLUMN total_messages;

ALTER TABLE Courses_Users
DROP COLUMN total_links;




################################################################################
#
# Topics
#
################################################################################

#
# user_id and addtime are redundant, because the author of the topic is
# the author of the first post that belongs to the topic. And the addtime of
# the topic is the same as addtime of the first post into that topic.
#
ALTER TABLE Topics
DROP COLUMN user_id;

ALTER TABLE Topics
DROP COLUMN addtime;

#
# field 'topic' in table 'topics' - not very descriptive.
# This should actually be the title of the topic.
#
ALTER TABLE Topics
CHANGE topic topic_title VARCHAR(128) NOT NULL;

#
# Add FOREIGN KEYs
#
alter table Topics add FOREIGN KEY (course_id) REFERENCES Courses (course_id);



################################################################################
#
# Messages  -->  Posts
#
################################################################################

#
# First of all the messages table is for storing forum posts -
# table name should reflect it.
#
RENAME TABLE Messages TO Posts;

#
# The course_id is easily available from the Topics table
#
ALTER TABLE Posts
DROP COLUMN course_id;

#
# Addtime should be datetime instead of timestamp. It may not be null.
#
ALTER TABLE Posts
CHANGE addtime addtime DATETIME NOT NULL;

#
# Along with renaming the table, some fields should be also renamed for clarity.
#
ALTER TABLE Posts
CHANGE message_id post_id INT NOT NULL AUTO_INCREMENT;

ALTER TABLE Posts
CHANGE message post_content TEXT NOT NULL;

#
# Add FOREIGN KEYs
#
alter table Posts add FOREIGN KEY (topic_id) REFERENCES Topics (topic_id);
alter table Posts add FOREIGN KEY (user_id) REFERENCES Users (user_id);



################################################################################
#
# Files, Files_Folders, Links, Links_Folders  -->  Materials, Material_Contents
#
################################################################################

#
# All the old files and links tables are combined into one table Materials,
# including the folders. This means, that folder hirarchy is no longer restricted
# to only one level.
#

# new table that will contain all files, links and folders.
CREATE TABLE Materials (
    material_id int not null primary key auto_increment,
    user_id int not null,
    course_id int not null,
    parent_id int null,
    material_type enum('FILE','FOLDER','LINK') not null default 'FILE',
    mime_type varchar(100) not null,
    material_name varchar(255) not null,
    material_description text not null,
    material_uri text not null,
    material_addtime datetime not null,
    material_size int not null,
    folder_id int not null, # temporary field for old values
    FOREIGN KEY (user_id) REFERENCES Users (user_id),
    FOREIGN KEY (course_id) REFERENCES Courses (course_id),
    FOREIGN KEY (parent_id) REFERENCES Materials (material_id),
    KEY (folder_id)
);

#
# The contents of the files is no more stored in filesystem,
# they are placed into database instead.
#
CREATE TABLE Material_Contents(
    material_id int NOT NULL,
    material_content longblob NOT NULL,
    PRIMARY KEY (material_id),
    FOREIGN KEY (material_id) REFERENCES Materials (material_id)
);


# copy file folders from old table to new
INSERT INTO
Materials (
    user_id,
    course_id,
    parent_id,
    material_type,
    mime_type,
    material_name,
    material_description,
    material_uri,
    material_addtime,
    material_size,
    folder_id
)
SELECT
    Courses.teacher_id as user_id,
    Files_Folders.course_id,
    NULL as parent_id,
    'FOLDER' as material_type,
    '' as mime_type,
    folder_name as material_name,
    '' as material_description,
    '' as material_uri,
    NOW() as material_addtime,
    0 as material_size,
    folder_id
FROM
    Files_Folders
    JOIN
    Courses USING (course_id);




# insert the files that are not inside folders
INSERT INTO Materials (
    material_id,
    user_id,
    course_id,
    parent_id,
    material_type,
    mime_type,
    material_name,
    material_description,
    material_uri,
    material_addtime,
    material_size,
    folder_id
)
SELECT
    '' as material_id,
    user_id,
    course_id,
    NULL as parent_id,
    'FILE' as material_type,
    '' as mime_type,
    title as material_name,
    description as material_description,
    CONCAT(Files.file_id, '/', Files.filename) as material_uri,
    addtime as material_addtime,
    filesize as material_size,
    0 as folder_id
FROM
    Files
WHERE
    folder_id = 0;


# insert files inside folders
INSERT INTO Materials (
    material_id,
    user_id,
    course_id,
    parent_id,
    material_type,
    mime_type,
    material_name,
    material_description,
    material_uri,
    material_addtime,
    material_size,
    folder_id
)
SELECT
    '' as material_id,
    Files.user_id,
    Files.course_id,
    Materials.material_id as parent_id, # get the parent id
    'FILE' as material_type,
    '' as mime_type,
    Files.title as material_name,
    Files.description as material_description,
    CONCAT(Files.file_id, '/', Files.filename) as material_uri,
    Files.addtime as material_addtime,
    Files.filesize as material_size,
    0 as folder_id
FROM
    Files
    JOIN
    Materials ON ( Files.folder_id = Materials.folder_id )
WHERE
    Files.folder_id != 0;


# set all folder_id fields to 0
UPDATE Materials set folder_id=0;







# copy link folders from old table to new
INSERT INTO
Materials (
    material_id,
    user_id,
    course_id,
    parent_id,
    material_type,
    mime_type,
    material_name,
    material_description,
    material_uri,
    material_addtime,
    material_size,
    folder_id
)
SELECT
    '' as material_id,
    Courses.teacher_id as user_id,
    Links_Folders.course_id,
    NULL as parent_id,
    'FOLDER' as material_type,
    '' as mime_type,
    folder_name as material_name,
    '' as material_description,
    '' as material_uri,
    NOW() as material_addtime,
    0 as material_size,
    folder_id
FROM
    Links_Folders
    JOIN
    Courses USING (course_id);


# insert the links that are not inside folders
INSERT INTO Materials (
    material_id,
    user_id,
    course_id,
    parent_id,
    material_type,
    mime_type,
    material_name,
    material_description,
    material_uri,
    material_addtime,
    material_size,
    folder_id
)
SELECT
    '' as material_id,
    Links.user_id,
    Links.course_id,
    NULL as parent_id,
    'LINK' as material_type,
    '' as mime_type,
    Links.title as material_name,
    Links.description as material_description,
    Links.url as material_uri,
    Links.addtime as material_addtime,
    0 as material_size, # links point to external sources
    0 as folder_id
FROM
    Links
WHERE
    Links.folder_id = 0;

# insert links inside folders
INSERT INTO Materials (
    material_id,
    user_id,
    course_id,
    parent_id,
    material_type,
    mime_type,
    material_name,
    material_description,
    material_uri,
    material_addtime,
    material_size,
    folder_id
)
SELECT
    '' as material_id,
    Links.user_id,
    Links.course_id,
    Materials.material_id as parent_id, # get parent
    'LINK' as material_type,
    '' as mime_type,
    Links.title as material_name,
    Links.description as material_description,
    Links.url as material_uri,
    Links.addtime as material_addtime,
    0 as material_size, # links point to external sources
    0 as folder_id
FROM
    Links
    JOIN
    Materials ON ( Links.folder_id = Materials.folder_id )
WHERE
    Links.folder_id != 0;

# drop the no more needed folder_id column
ALTER TABLE Materials
DROP folder_id;



# Delete old files, folders and links tables
drop table Files;
drop table Files_Folders;
drop table Links;
drop table Links_Folders;
