<?php
session_start();
require 'conn.php';

if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}

// 获取管理员信息
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT campus FROM admins WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($campus);
$stmt->fetch();
$stmt->close();

// 获取同校区学生列表
$stmt = $conn->prepare("SELECT id, username, real_name, gender, age, phone, email, balance FROM students WHERE campus=?");
$stmt->bind_param("s", $campus);
$stmt->execute();
$stmt->bind_result($id, $username, $real_name, $gender, $age, $phone, $email, $balance);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>管理学生信息</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
        }

        .container {
            width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #3498db;
            color: white;
        }

        button {
            padding: 5px 10px;
            margin: 5px;
            border: none;
            border-radius: 6px;
            background: #3498db;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #2980b9;
        }

        .back-btn {
            background: #e74c3c;
        }

        .back-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>管理本校区学生信息</h2>
        
        <!-- 学生信息表格 -->
        <table>
            <thead>
                <tr>
                    <th>用户名</th>
                    <th>真实姓名</th>
                    <th>性别</th>
                    <th>年龄</th>
                    <th>电话</th>
                    <th>邮箱</th>
                    <th>账户余额</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php while($stmt->fetch()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($username); ?></td>
                    <td><?php echo htmlspecialchars($real_name); ?></td>
                    <td><?php echo htmlspecialchars($gender); ?></td>
                    <td><?php echo htmlspecialchars($age); ?></td>
                    <td><?php echo htmlspecialchars($phone); ?></td>
                    <td><?php echo htmlspecialchars($email); ?></td>
                    <td><?php echo htmlspecialchars($balance); ?> 元</td>
                    <td>
                        <button onclick="location.href='a_edit_student.php?id=<?php echo $id; ?>'">编辑</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- 返回主页按钮 -->
        <button class="back-btn" onclick="location.href='a_success.php'">返回主页</button>
    </div>

    <?php
    // 关闭 statement 连接
    $stmt->close();
    ?>
</body>
</html>
