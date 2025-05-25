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