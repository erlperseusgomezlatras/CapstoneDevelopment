
CREATE DATABASE IF NOT EXISTS practicum_system;
USE practicum_system;

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO roles (role_name) VALUES
('Administrator'),
('Teacher'),
('Coordinator'),
('Student');

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    fullname VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE schools (
    school_id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(150) NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    geofence_radius INT DEFAULT 100
);

CREATE TABLE sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) NOT NULL
);

CREATE TABLE coordinator_assignments (
    coordinator_id INT,
    school_id INT,
    section_id INT,
    PRIMARY KEY (coordinator_id, section_id),
    FOREIGN KEY (coordinator_id) REFERENCES users(user_id),
    FOREIGN KEY (school_id) REFERENCES schools(school_id),
    FOREIGN KEY (section_id) REFERENCES sections(section_id)
);

CREATE TABLE students (
    student_id INT PRIMARY KEY,
    school_id INT,
    section_id INT,
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (school_id) REFERENCES schools(school_id),
    FOREIGN KEY (section_id) REFERENCES sections(section_id)
);

CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    date DATE,
    time_in TIME,
    time_out TIME,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    status ENUM('valid','invalid') DEFAULT 'valid',
    FOREIGN KEY (student_id) REFERENCES users(user_id)
);

CREATE TABLE journals (
    journal_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    journal_date DATE,
    content TEXT,
    status ENUM('pending','approved','revision') DEFAULT 'pending',
    teacher_comment TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id)
);

CREATE TABLE checklist_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255)
);

CREATE TABLE student_checklist (
    student_id INT,
    item_id INT,
    completed ENUM('yes','no') DEFAULT 'no',
    validated_by INT NULL,
    PRIMARY KEY (student_id, item_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (validated_by) REFERENCES users(user_id)
);
