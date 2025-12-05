CREATE DATABASE IF NOT EXISTS course_management_system;
USE course_management_system;

-- Staff Table (Aliya's Component)
CREATE TABLE Staff (
    StaffID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Email VARCHAR(100),
    Department VARCHAR(50),
    IsActive BOOLEAN DEFAULT 1
);

-- Course Table (Abdullah's Component)
CREATE TABLE Course (
    CourseID INT AUTO_INCREMENT PRIMARY KEY,
    StaffID INT NULL,
    CourseName VARCHAR(100) NOT NULL,
    CourseCode VARCHAR(20) NOT NULL UNIQUE,
    Description TEXT,
    Credits INT NOT NULL,
    Fee DECIMAL(10,2) DEFAULT 0.00,
    IsActive TINYINT(1) DEFAULT 1,
    StartDate DATE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StaffID) REFERENCES Staff(StaffID) ON DELETE SET NULL
);

-- Student Table (Mithil's Component)
CREATE TABLE Student (
    StudentID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Email VARCHAR(100),
    DateOfBirth DATE,
    GPA DECIMAL(3,2),
    IsActive BOOLEAN DEFAULT 1
);

-- Enrollment Table (Anmol's Component)
CREATE TABLE Enrollment (
    EnrollmentID INT AUTO_INCREMENT PRIMARY KEY,
    StudentID INT NOT NULL,
    CourseID INT NOT NULL,
    EnrollmentDate DATE NOT NULL,
    Status VARCHAR(20) DEFAULT 'Enrolled',
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID) ON DELETE CASCADE,
    FOREIGN KEY (CourseID) REFERENCES Course(CourseID) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO Staff (FirstName, LastName, Email, Department) VALUES 
('John', 'Smith', 'john.smith@university.edu', 'Computer Science'),
('Sarah', 'Johnson', 'sarah.johnson@university.edu', 'Mathematics'),
('Michael', 'Brown', 'michael.brown@university.edu', 'Physics'),
('Emily', 'Davis', 'emily.davis@university.edu', 'Engineering');

INSERT INTO Course (StaffID, CourseName, CourseCode, Description, Credits, Fee, StartDate) VALUES 
(1, 'Introduction to Programming', 'CS101', 'Basic programming concepts with Python', 3, 500.00, '2024-09-01'),
(2, 'Calculus I', 'MATH101', 'Differential and integral calculus', 4, 450.00, '2024-09-01'),
(1, 'Web Development', 'CS201', 'HTML, CSS, JavaScript and PHP', 3, 600.00, '2024-09-15'),
(3, 'Physics Fundamentals', 'PHY101', 'Basic principles of physics', 3, 550.00, '2024-09-01');

INSERT INTO Student (FirstName, LastName, Email, DateOfBirth, GPA) VALUES 
('Alice', 'Johnson', 'alice.johnson@student.edu', '2000-05-15', 3.8),
('Bob', 'Smith', 'bob.smith@student.edu', '1999-08-22', 3.5),
('Carol', 'Davis', 'carol.davis@student.edu', '2001-02-10', 3.9),
('David', 'Wilson', 'david.wilson@student.edu', '2000-11-30', 3.2);

INSERT INTO Enrollment (StudentID, CourseID, EnrollmentDate, Status) VALUES 
(1, 1, '2024-08-15', 'Enrolled'),
(1, 2, '2024-08-20', 'Enrolled'),
(2, 1, '2024-08-18', 'Enrolled'),
(3, 3, '2024-08-22', 'Enrolled'),
(4, 4, '2024-08-25', 'Enrolled');