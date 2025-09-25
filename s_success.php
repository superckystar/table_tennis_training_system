<?php
session_start();
if(!isset($_SESSION['username'])){
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>学生主页</title>
<style>
body{
    font-family:"Microsoft YaHei",Arial;
    background: linear-gradient(135deg, #dfe9f3, #ffffff);
    margin:0;
    padding:0;
}
.container{
    max-width:1000px;
    margin:50px auto;
    padding:40px;
    background:#fff;
    border-radius:16px;
    box-shadow:0 8px 25px rgba(0,0,0,0.1);
    text-align:center;
}
h2{
    color:#333;
    margin-bottom:30px;
    font-size:28px;
}
.grid{
    display:grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap:20px;
    margin-top:20px;
}
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
.logout-btn{
    background:#e74c3c;
}
.logout-btn:hover{
    background:#c0392b;
}
</style>
</head>
<body>
<div class="container">
    <h2>欢迎，<?php echo htmlspecialchars($username); ?>！</h2>

    <div class="grid">
        <!-- 功能按钮 -->
        <form action="s_info.php" method="get">
            <button type="submit">个人信息</button>
        </form>

        <form action="s_search_coach.php" method="get">
            <button type="submit">查询教练员</button>
        </form>

        <form action="s_unbind_coach.php" method="get">
            <button type="submit">解绑教练员</button>
        </form>

        <!-- 预约课程 -->
        <form action="s_book_course.php" method="get">
            <button type="submit">预约课程</button>
        </form>

        <!-- 缴费课程 -->
        <form action="s_pay.php" method="get">
            <button type="submit">缴费课程</button>
        </form>

        <!-- 新增：账户充值 -->
        <form action="s_recharge.php" method="get">
            <button type="submit">账户充值</button>
        </form>

        <!-- 学生取消预约 -->
        <form action="s_cancel_appointment.php" method="get">
            <button type="submit">取消预约</button>
        </form>

        <!-- 新增：确认教练取消申请 -->
        <form action="s_confirm_cancellations.php" method="get">
            <button type="submit">确认教练取消预约</button>
        </form>

        <!-- 查看已预约并缴费课程 -->
        <form action="s_my_appointments.php" method="get">
            <button type="submit">我的预约课程</button>
        </form>

        <form action="register_monthly.php" method="get">
            <button type="submit">报名比赛</button>
        </form>

        <form action="s_view_match.php" method="get">
            <button type="submit">我的比赛</button>
        </form>

        <!-- 退出登录 -->
        <form action="logout.php" method="post">
            <button type="submit" class="logout-btn">退出登录</button>
        </form>
    </div>
</div>
</body>
</html>
