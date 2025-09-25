CREATE DATABASE IF NOT EXISTS table_tennis_training_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE table_tennis_training_system;
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,  -- ���Ψһ
    password VARCHAR(255) NOT NULL,        -- ��������ϣ�洢
    real_name VARCHAR(50) NOT NULL,        -- ����
    gender ENUM('male','female') DEFAULT NULL,  -- �ɿ�
    age INT DEFAULT NULL,                   -- �ɿ�
    campus VARCHAR(100) NOT NULL,          -- ����
    phone VARCHAR(20) NOT NULL,            -- ����
    email VARCHAR(100) DEFAULT NULL,       -- �ɿ�
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    real_name VARCHAR(255) NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    age INT,
    campus VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    photo VARCHAR(255) NOT NULL,
    achievements TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '����Ա�û���',
    password VARCHAR(255) NOT NULL COMMENT '���루hash�洢��',
    name VARCHAR(100) NOT NULL COMMENT '��ʵ����',
    gender ENUM('��','Ů') DEFAULT NULL COMMENT '�Ա�',
    age INT DEFAULT NULL COMMENT '����',
    campus VARCHAR(100) NOT NULL COMMENT '����У��',
    phone VARCHAR(20) NOT NULL COMMENT '��ϵ�绰',
    email VARCHAR(100) DEFAULT NULL COMMENT '����',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '����ʱ��'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='����Ա��';
CREATE TABLE superadmins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '��������Ա�û���',
    password VARCHAR(255) NOT NULL COMMENT '���루hash�洢��',
    name VARCHAR(100) NOT NULL COMMENT '��ʵ����',
    gender ENUM('��','Ů') DEFAULT NULL COMMENT '�Ա�',
    age INT DEFAULT NULL COMMENT '����',
    campus VARCHAR(100) NOT NULL COMMENT '����У��',
    phone VARCHAR(20) NOT NULL COMMENT '��ϵ�绰',
    email VARCHAR(100) DEFAULT NULL COMMENT '����',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '����ʱ��'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='��������Ա��';
CREATE TABLE campuses (
    id INT AUTO_INCREMENT PRIMARY KEY,          -- У��ID
    name VARCHAR(100) NOT NULL,                 -- У������
    address VARCHAR(255) NOT NULL,              -- ��ַ
    contact_person VARCHAR(100) NOT NULL,       -- ��ϵ��
    phone VARCHAR(20) NOT NULL,                 -- ��ϵ�绰
    email VARCHAR(100) NOT NULL,                -- ��ϵ����
    admin_id INT NOT NULL,                      -- ������ID����������Ա��
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE RESTRICT
);

CREATE TABLE student_coach_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    coach_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_date TIMESTAMP NULL,
    remark TEXT DEFAULT NULL,
    CONSTRAINT fk_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_coach FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE,
    UNIQUE KEY uq_student_coach (student_id, coach_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE courts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus VARCHAR(50) NOT NULL,
    court_number INT NOT NULL
);
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    coach_id INT NOT NULL,
    campus VARCHAR(50) NOT NULL,
    court_number INT NOT NULL,
    date DATE NOT NULL,
    time_slot ENUM('08:00-09:00','09:00-10:00','10:00-11:00','11:00-12:00',
                   '13:00-14:00','14:00-15:00','15:00-16:00','16:00-17:00') NOT NULL,
    status ENUM('pending','confirmed','rejected','cancel_requested','cancelled') DEFAULT 'pending',
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_time TIMESTAMP NULL,
    cancelled_time TIMESTAMP NULL,
    cancel_requested_by ENUM('student','coach') NULL,
    CONSTRAINT fk_appoint_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_appoint_coach FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE
);
ADD COLUMN balance DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'ѵ�������';
-- ��������¼ĳ�����£��� event_date ָ��������ĳ�µ��ĸ����գ���ĳУ��ĳ���ı�����Ϣ
CREATE TABLE monthly_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    campus VARCHAR(100) NOT NULL,
    event_date DATE NOT NULL,               -- �������������ڣ����ĸ����գ�
    group_type ENUM('��','��','��') NOT NULL,
    paid TINYINT(1) NOT NULL DEFAULT 0,     -- �Ƿ��ѿ۷�
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_event_group (student_id, event_date, group_type),
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- �洢ÿһ�������Ķ������̣�
CREATE TABLE monthly_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    campus VARCHAR(100) NOT NULL,
    group_type ENUM('��','��','��') NOT NULL,
    round_no INT NOT NULL,
    player1_id INT NULL,
    player2_id INT NULL,
    court_number INT NULL,
    status ENUM('scheduled','completed','walkover') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_match_player1 FOREIGN KEY (player1_id) REFERENCES students(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_player2 FOREIGN KEY (player2_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE student_coach_relations
ADD COLUMN action_type ENUM('bind','unbind') NOT NULL DEFAULT 'bind' AFTER status;