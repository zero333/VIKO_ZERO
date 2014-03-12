# Copyright 2001-2007 by VIKO team and contributors
# 
# This file is part of VIKO.
#
# VIKO is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# VIKO is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with VIKO; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


# All tables in VIKO database use UTF-8 encoding
SET FOREIGN_KEY_CHECKS=0;
ALTER DATABASE DEFAULT CHARACTER SET utf8;


DROP TABLE IF EXISTS Schools;
CREATE TABLE Schools (
    school_id               int AUTO_INCREMENT NOT NULL,
    school_name             varchar(128) NOT NULL,
    PRIMARY KEY (school_id),
    UNIQUE INDEX (school_name));

DROP TABLE IF EXISTS Forms;
CREATE TABLE Forms (
    form_id                 int AUTO_INCREMENT NOT NULL,
    form_name               varchar(32) NOT NULL,
    school_id               int NOT NULL,
    PRIMARY KEY (form_id),
    UNIQUE INDEX (form_name, school_id),
    FOREIGN KEY (school_id)
        REFERENCES Schools (school_id));

DROP TABLE IF EXISTS Users;
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
);

INSERT INTO Users (username, password, user_group) VALUES ('admin', SHA1('admin'), 'ADMIN');

DROP TABLE IF EXISTS Courses;
CREATE TABLE Courses (
    course_id               int AUTO_INCREMENT NOT NULL,
    course_name             varchar(255) NOT NULL,
    course_subname          varchar(64) NOT NULL,
    teacher_id              int NOT NULL,
    description             text NOT NULL,
    assessment              text NOT NULL,
    books                   text NOT NULL,
    scheduled_time          varchar(255) NOT NULL,
    classroom               varchar(64) NOT NULL,
    consultation            varchar(255) NOT NULL,
    PRIMARY KEY (course_id),
    FOREIGN KEY (teacher_id)
        REFERENCES Users (user_id));

DROP TABLE IF EXISTS Courses_Users;
CREATE TABLE Courses_Users (
    course_id               int NOT NULL,
    user_id                 int NOT NULL,
    last_view               timestamp,
    total_views             int NOT NULL,
    PRIMARY KEY (course_id, user_id),
    FOREIGN KEY (course_id)
        REFERENCES Courses (course_id),
    FOREIGN KEY (user_id)
        REFERENCES Users (user_id));
		
DROP TABLE IF EXISTS Materials;
CREATE TABLE Materials (
    material_id int AUTO_INCREMENT NOT NULL,
    user_id int NOT NULL,
    course_id int NOT NULL,
    parent_id int NULL,
    material_type ENUM( 'FILE', 'FOLDER', 'LINK', 'EMBED', 'TEXT' ) NOT NULL DEFAULT 'FILE',
    mime_type VARCHAR(100) NOT NULL,
    material_name varchar(255) NOT NULL,
    material_description text NOT NULL,
    material_uri text NOT NULL,
    material_addtime datetime NOT NULL,
    material_size int NOT NULL,
	material_text TEXT NOT NULL, 
	material_visible INT NOT NULL DEFAULT '0',
	material_score INT NOT NULL,
    PRIMARY KEY (material_id),
    FOREIGN KEY (user_id)
        REFERENCES Users (user_id),
    FOREIGN KEY (course_id)
        REFERENCES Courses (course_id),
    FOREIGN KEY (parent_id)
        REFERENCES Materials (material_id));

DROP TABLE IF EXISTS Material_Contents;
CREATE TABLE Material_Contents(
    material_id int NOT NULL,
    material_content longblob NOT NULL,
    PRIMARY KEY (material_id),
    FOREIGN KEY (material_id)
        REFERENCES Materials (material_id));

DROP TABLE IF EXISTS Lessons;
CREATE TABLE Lessons (
    lesson_id               int NOT NULL AUTO_INCREMENT,
    course_id               int NOT NULL,
    lesson_topic            text NOT NULL,
    lesson_date             date NOT NULL,
    hometask                text,
    hometask_date           date,
    marktype                enum('LESSON', 'PRELIM') DEFAULT 'LESSON' NOT NULL,
    PRIMARY KEY (lesson_id),
    FOREIGN KEY (course_id)
        REFERENCES Courses (course_id));

DROP TABLE IF EXISTS Marks;
CREATE TABLE Marks (
    lesson_id               int NOT NULL,
    user_id                 int NOT NULL,
    mark                    varchar(64) NOT NULL,
    PRIMARY KEY (lesson_id, user_id),
    FOREIGN KEY (lesson_id)
        REFERENCES Lessons (lesson_id),
    FOREIGN KEY (user_id)
        REFERENCES Users (user_id));

DROP TABLE IF EXISTS Topics;
CREATE TABLE Topics (
    topic_id                int AUTO_INCREMENT NOT NULL,
    topic_title             varchar(128) NOT NULL,
    course_id               int NOT NULL,
    PRIMARY KEY (topic_id),
    FOREIGN KEY (course_id)
        REFERENCES Courses (course_id));

DROP TABLE IF EXISTS Posts;
CREATE TABLE Posts (
    post_id                 int AUTO_INCREMENT NOT NULL,
    topic_id                int NOT NULL,
    user_id                 int NOT NULL,
    post_content            text NOT NULL,
    addtime                 datetime NOT NULL,
    PRIMARY KEY (post_id),
    FOREIGN KEY (topic_id)
        REFERENCES Topics (topic_id),
    FOREIGN KEY (user_id)
        REFERENCES Users (user_id));
