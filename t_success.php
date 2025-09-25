<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>教练主页</title>
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
    <h2>欢迎，<?php echo htmlspecialchars($username); ?>！</h2>

    <div class="grid">
        <form action="t_info.php" method="get">
            <button type="submit">个人信息</button>
        </form>

        <form action="c_review_requests.php" method="get">
            <button type="submit">审核学生申请</button>
        </form>

        <form action="t_process_unbind.php" method="get">
            <button type="submit">审核学生解绑申请</button>
        </form>

        <form action="c_review_appointments.php" method="get">
            <button type="submit">预约审核</button>
        </form>

        <form action="c_confirm_cancellations.php" method="get">
            <button type="submit">取消预约确认</button>
        </form>

        <form action="c_cancel_appointment.php" method="get">
            <button type="submit">取消我的预约</button>
        </form>

        <form action="t_my_appointments.php" method="get">
            <button type="submit">查看已预约课程</button>
        </form>

        <form action="logout.php" method="post">
            <button type="submit" class="logout-btn">退出登录</button>
        </form>
    </div>
</div>
</body>
</html>
