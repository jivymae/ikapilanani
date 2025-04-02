-- Create Patients Table
CREATE TABLE Patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    dob DATE NOT NULL, -- Date of Birth
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    phone_number VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Dentists/Staff Table
CREATE TABLE Dentists (
    dentist_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    specialty VARCHAR(100),
    phone_number VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Appointments Table
CREATE TABLE Appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    dentist_id INT,
    appointment_date DATETIME NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES Patients(patient_id),
    FOREIGN KEY (dentist_id) REFERENCES Dentists(dentist_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Treatment Records Table
CREATE TABLE Treatment_Records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    treatment VARCHAR(255) NOT NULL,
    notes TEXT,
    FOREIGN KEY (appointment_id) REFERENCES Appointments(appointment_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Billing/Invoices Table
CREATE TABLE Billing (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    appointment_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    paid BOOLEAN DEFAULT FALSE,
    payment_date DATE,
    FOREIGN KEY (patient_id) REFERENCES Patients(patient_id),
    FOREIGN KEY (appointment_id) REFERENCES Appointments(appointment_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Feedback/Reviews Table
CREATE TABLE Feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    dentist_id INT,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    comments TEXT,
    FOREIGN KEY (patient_id) REFERENCES Patients(patient_id),
    FOREIGN KEY (dentist_id) REFERENCES Dentists(dentist_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Schedules Table
CREATE TABLE Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    dentist_id INT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    FOREIGN KEY (dentist_id) REFERENCES Dentists(dentist_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Visits Table
CREATE TABLE Visits (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    visit_date DATE NOT NULL,
    visit_type ENUM('Routine', 'Emergency', 'Consultation') NOT NULL,
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES Patients(patient_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
