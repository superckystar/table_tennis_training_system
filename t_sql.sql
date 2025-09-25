CREATE DATABASE IF NOT EXISTS table_tennis_training_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE table_tennis_training_system;
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,  -- 必填，唯一
    password VARCHAR(255) NOT NULL,        -- 必填，密码哈希存储
    real_name VARCHAR(50) NOT NULL,        -- 必填
    gender ENUM('male','female') DEFAULT NULL,  -- 可空
    age INT DEFAULT NULL,                   -- 可空
    campus VARCHAR(100) NOT NULL,          -- 必填
    phone VARCHAR(20) NOT NULL,            -- 必填
    email VARCHAR(100) DEFAULT NULL,       -- 可空
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
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '管理员用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码（hash存储）',
    name VARCHAR(100) NOT NULL COMMENT '真实姓名',
    gender ENUM('男','女') DEFAULT NULL COMMENT '性别',
    age INT DEFAULT NULL COMMENT '年龄',
    campus VARCHAR(100) NOT NULL COMMENT '所属校区',
    phone VARCHAR(20) NOT NULL COMMENT '联系电话',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';
CREATE TABLE superadmins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '超级管理员用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码（hash存储）',
    name VARCHAR(100) NOT NULL COMMENT '真实姓名',
    gender ENUM('男','女') DEFAULT NULL COMMENT '性别',
    age INT DEFAULT NULL COMMENT '年龄',
    campus VARCHAR(100) NOT NULL COMMENT '所属校区',
    phone VARCHAR(20) NOT NULL COMMENT '联系电话',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='超级管理员表';
CREATE TABLE campuses (
    id INT AUTO_INCREMENT PRIMARY KEY,          -- 校区ID
    name VARCHAR(100) NOT NULL,                 -- 校区名称
    address VARCHAR(255) NOT NULL,              -- 地址
    contact_person VARCHAR(100) NOT NULL,       -- 联系人
    phone VARCHAR(20) NOT NULL,                 -- 联系电话
    email VARCHAR(100) NOT NULL,                -- 联系邮箱
    admin_id INT NOT NULL,                      -- 负责人ID（关联管理员）
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
ADD COLUMN balance DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '训练费余额';
-- 报名表：记录某次赛事（由 event_date 指定，例如某月第四个周日）中某校区某组别的报名信息
CREATE TABLE monthly_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    campus VARCHAR(100) NOT NULL,
    event_date DATE NOT NULL,               -- 本次月赛的日期（第四个周日）
    group_type ENUM('甲','乙','丙') NOT NULL,
    paid TINYINT(1) NOT NULL DEFAULT 0,     -- 是否已扣费
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_event_group (student_id, event_date, group_type),
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 存储每一场比赛的对阵（赛程）
CREATE TABLE monthly_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    campus VARCHAR(100) NOT NULL,
    group_type ENUM('甲','乙','丙') NOT NULL,
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