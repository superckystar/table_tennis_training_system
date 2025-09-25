<?php
session_start();
require 'conn.php';

if(!isset($_SESSION['username'])){
    header("Location: login_s.php");
    exit();
}

$username = $_SESSION['username'];

// 获取学生ID和余额
$stmt = $conn->prepare("SELECT id, balance FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id, $balance);
if(!$stmt->fetch()){
    $stmt->close();
    die("未找到学生信息");
}
$stmt->close();

// 获取学生已确认但未支付的预约
$stmt = $conn->prepare("
    SELECT a.id, a.date, a.time_slot, a.campus, a.court_number, a.paid, c.real_name AS coach_name, c.level AS coach_level
    FROM appointments a
    JOIN coaches c ON a.coach_id = c.id
    WHERE a.student_id = ? AND a.status='confirmed' AND a.paid='no'
    ORDER BY a.date, a.time_slot
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>缴费课程</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f0f2f5;margin:0;padding:0;}
.container{max-width:900px;margin:40px auto;padding:20px;background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th,td{border:1px solid #ddd;padding:10px;text-align:center;}
th{background:#3498db;color:#fff;}
button{padding:6px 12px;border:none;border-radius:6px;background:#2ecc71;color:#fff;cursor:pointer;}
button:hover{background:#27ae60;}
.balance{margin-top:15px;font-weight:bold;color:#d35400;}
</style>
</head>
<body>
<div class="container">
    <h2>缴费课程</h2>
    <p class="balance">当前余额：￥<?php echo number_format($balance,2); ?></p>

    <?php if($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>预约ID</th>
            <th>教练</th>
            <th>校区</th>
            <th>球桌号</th>
            <th>日期</th>
            <th>时间段</th>
            <th>应缴金额</th>
            <th>操作</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <?php
            switch($row['coach_level']){
                case 1: $fee = 80; break;
                case 2: $fee = 150; break;
                case 3: $fee = 200; break;
                default: $fee = 0;
            }
        ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['coach_name']); ?></td>
            <td><?php echo htmlspecialchars($row['campus']); ?></td>
            <td><?php echo $row['court_number']; ?></td>
            <td><?php echo htmlspecialchars($row['date']); ?></td>
            <td><?php echo htmlspecialchars($row['time_slot']); ?></td>
            <td>￥<?php echo $fee; ?></td>
            <td>
                <form action="s_pay_process.php" method="post" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                    <button type="submit">缴费</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p>暂无需要缴费的课程。</p>
    <?php endif; ?>
    <form action="s_success.php" method="get" style="margin-top:20px; text-align:center;">
    <button type="submit" style="background:#3498db; color:#fff; padding:8px 16px; border:none; border-radius:6px; cursor:pointer;">返回主页</button>
</form>
</div>
</body>
</html>
