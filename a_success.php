<?php
session_start();
require 'conn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
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
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>管理员主页</title>
<style>
body{
    font-family:"Microsoft YaHei",Arial;
    background: linear-gradient(135deg, #dfe9f3, #ffffff);
    margin:0;
    padding:0;
}

/* 容器 */
.container{
    max-width:1000px;
    margin:50px auto;
    padding:40px;
    background:#fff;
    border-radius:16px;
    box-shadow:0 8px 25px rgba(0,0,0,0.1);
    text-align:center;
}

/* 标题 */
h2{
    color:#333;
    margin-bottom:30px;
    font-size:28px;
}

/* 网格布局按钮 */
.grid{
    display:grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap:20px;
    margin-top:20px;
}

/* 按钮样式 */
button{
    width:100%;
    padding:14px 0;
    border:none;
    border-radius:10px;
    background:linear-gradient(135deg,#4facfe,#00f2fe);
    color:#fff;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:all 0.3s ease;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
}

button:hover{
    background:linear-gradient(135deg,#43e97b,#38f9d7);
    transform:translateY(-2px);
    box-shadow:0 6px 18px rgba(0,0,0,0.2);
}

/* 退出登录按钮 */
.logout-btn{
    background:#e74c3c;
}

.logout-btn:hover{
    background:#c0392b;
}

/* 响应式 */
@media(max-width:600px){
    .grid{
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<div class="container">
    <h2>欢迎管理员 - 校区：<?php echo htmlspecialchars($campus); ?></h2>

    <div class="grid">
        <button onclick="location.href='a_review_coaches.php'">审核本校区教练</button>
        <button onclick="location.href='a_manage_coaches.php'">管理本校区教练</button>
        <button onclick="location.href='a_manage_appointments.php'">管理本校区预约</button>
        <button onclick="location.href='a_manage_students.php'">管理本校区学生</button>
        <button onclick="location.href='a_info.php'">个人信息</button>
        <button class="logout-btn" onclick="window.location.href='login_t.html';">退出登录</button>
    </div>
</div>
</body>
</html>
