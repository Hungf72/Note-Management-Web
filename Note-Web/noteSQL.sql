create database NoteApp
use NoteApp

create table Users(
    id int AUTO_INCREMENT primary key,
    firstName varchar(50) not null,
    lastName varchar(50) not null,
    age int not null,
    phone varchar(15) not null,
    email varchar(100) not null unique,
    password varchar(255) not null
)

CREATE TABLE IF NOT EXISTS notes (
    id int primary key AUTO_INCREMENT,
    user_id int not null,
    title varchar(255) not null,
    content text not null,
    created_at timestamp default CURRENT_TIMESTAMP,
    last_modified timestamp default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);