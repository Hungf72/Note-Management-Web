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
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);