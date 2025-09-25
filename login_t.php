<?php
session_start();
require 'conn.php';  // 包含数据库连接

if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];  // 学员 / 教练 / 管理员 / 超级管理员

    if ($role === 'student') {
        // 学员登录
        $stmt = $conn->prepare("SELECT id, password FROM students WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                // 登录成功后，尝试生成比赛流程
                generateMatches($conn);

                header("Location: s_success.php");
                exit();
            }
        }
        echo "<script>alert('用户名或密码错误'); window.history.back();</script>";
        exit();

    } elseif ($role === 'teacher') {
        // 教练登录
        $stmt = $conn->prepare("SELECT id, password, status FROM coaches WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $status);
            $stmt->fetch();

            if ($status !== 'approved') {
                echo "<script>alert('您的账号尚未通过管理员审核'); window.history.back();</script>";
                exit();
            }

            if (password_verify($password, $hashed_password)) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                // 教练也可触发比赛生成
                generateMatches($conn);

                header("Location: t_success.php");
                exit();
            }
        }
        echo "<script>alert('用户名或密码错误'); window.history.back();</script>";
        exit();

    } elseif ($role === 'admin') {
        // 普通管理员登录
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                header("Location: a_success.php");
                exit();
            }
        }
        echo "<script>alert('用户名或密码错误'); window.history.back();</script>";
        exit();

    } elseif ($role === 'superadmin') {
        // 超级管理员
        if ($username === 'admin' && $password === 'admin@123') {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            header("Location: sa_success.html");
            exit();
        } else {
            echo "<script>alert('超级管理员账号或密码错误'); window.history.back();</script>";
            exit();
        }
    }
}

$conn->close();

// ==========================
// 生成比赛流程函数
// ==========================
function generateMatches($conn) {
    $today = new DateTime();
    $tomorrow = clone $today;
    $tomorrow->modify('+1 day');
    $tomorrow_str = $tomorrow->format('Y-m-d');

    // 获取明天有比赛的所有校区+组别
    $stmt = $conn->prepare("SELECT campus, group_type FROM monthly_registrations WHERE event_date=? GROUP BY campus, group_type");
    $stmt->bind_param("s", $tomorrow_str);
    $stmt->execute();
    $groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($groups as $group) {
        $campus = $group['campus'];
        $group_type = $group['group_type'];

        // 检查是否已生成
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM monthly_matches WHERE event_date=? AND campus=? AND group_type=?");
        $check_stmt->bind_param("sss", $tomorrow_str, $campus, $group_type);
        $check_stmt->execute();
        $check_stmt->bind_result($match_count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($match_count > 0) continue; // 已生成，跳过

        // 获取该组报名学员并随机排序
        $players_stmt = $conn->prepare("SELECT student_id FROM monthly_registrations WHERE event_date=? AND campus=? AND group_type=? ORDER BY RAND()");
        $players_stmt->bind_param("sss", $tomorrow_str, $campus, $group_type);
        $players_stmt->execute();
        $players = $players_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $players_stmt->close();

        $num_players = count($players);
        $court_number = 1;

        if ($num_players <= 6) {
            // 全循环赛
            $round_no = 1;
            for ($i = 0; $i < $num_players - 1; $i++) {
                for ($j = $i + 1; $j < $num_players; $j++) {
                    $player1 = $players[$i]['student_id'];
                    $player2 = $players[$j]['student_id'];

                    $insert_stmt = $conn->prepare("INSERT INTO monthly_matches (event_date, campus, group_type, round_no, player1_id, player2_id, court_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')");
                    $insert_stmt->bind_param("sssiiii", $tomorrow_str, $campus, $group_type, $round_no, $player1, $player2, $court_number);
                    $insert_stmt->execute();
                    $insert_stmt->close();

                    $court_number++;
                }
                $round_no++;
            }
        } else {
            // 超过6人，分小组，每组全循环
            $group_size = 6;
            $num_subgroups = ceil($num_players / $group_size);

            for ($g = 0; $g < $num_subgroups; $g++) {
                $subgroup = array_slice($players, $g * $group_size, $group_size);
                $sub_round_no = 1;
                for ($i = 0; $i < count($subgroup) - 1; $i++) {
                    for ($j = $i + 1; $j < count($subgroup); $j++) {
                        $player1 = $subgroup[$i]['student_id'];
                        $player2 = $subgroup[$j]['student_id'];

                        $insert_stmt = $conn->prepare("INSERT INTO monthly_matches (event_date, campus, group_type, round_no, player1_id, player2_id, court_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')");
                        $insert_stmt->bind_param("sssiiii", $tomorrow_str, $campus, $group_type, $sub_round_no, $player1, $player2, $court_number);
                        $insert_stmt->execute();
                        $insert_stmt->close();

                        $court_number++;
                    }
                    $sub_round_no++;
                }
            }
        }
    }
}
?>
