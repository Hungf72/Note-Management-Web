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
    is_active TINYINT(1) NOT NULL DEFAULT 0
);

create table notes (
    id int primary key AUTO_INCREMENT,
    user_id int not null,
    title varchar(255) not null,
    content text not null,
    created_at timestamp default CURRENT_TIMESTAMP,
    last_modified timestamp default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    note_password varchar(255) not null,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

create table tasks (
    tasks_id int primary key AUTO_INCREMENT,
    name varchar(255) not null,
    note_id int not null,
    user_id int not null,
    created_at timestamp default CURRENT_TIMESTAMP,
    FOREIGN KEY (note_id) REFERENCES notes(id) on delete cascade,
    FOREIGN KEY (user_id) REFERENCES users(id) on delete cascade
);

alter table notes add column is_pinned boolean default false;
alter table notes add column pinned_order int default null;

