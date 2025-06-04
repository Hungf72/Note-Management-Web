create database NoteApp;
use NoteApp;

create table Users(
    id int AUTO_INCREMENT primary key,
    firstName varchar(50) not null,
    lastName varchar(50) not null,
    age int not null,
    phone varchar(15) not null,
    email varchar(100) not null unique,
    password varchar(255) not null,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    avatar varchar(255) DEFAULT NULL
);

create table notes (
    id int primary key AUTO_INCREMENT,
    user_id int not null,
    title varchar(255) not null,
    content text not null,
    label varchar(255) default null,
    image_path varchar(255) default null,
    created_at timestamp default CURRENT_TIMESTAMP,
    last_modified timestamp default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    note_password varchar(255) not null,
    is_share TINYINT(1) NOT NULL DEFAULT 0,

    FOREIGN KEY (user_id) REFERENCES users(id)
);

create table labels (
    id int primary key AUTO_INCREMENT,
    user_id int not null,
    name varchar(255) not null
);

create table note_labels (
    note_id int not null,
    label_id int not null,
    FOREIGN KEY (note_id) REFERENCES notes(id),
    FOREIGN KEY (label_id) REFERENCES labels(id),
    PRIMARY KEY (note_id, label_id)
);
alter table notes add column is_pinned boolean default false;
alter table notes add column pinned_order int default null;

