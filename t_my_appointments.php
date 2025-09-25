<?php
session_start();
require 'conn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取教练ID和校区
$stmt = $conn->prepare("SELECT id, campus, real_name FROM coaches WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($coach_id, $campus, $real_name);
$stmt->fetch();
$stmt->close();

// 时间段
$time_slots = ["08:00-09:00","09:00-10:00","10:00-11:00","11:00-12:00",
               "14:00-15:00","15:00-16:00","16:00-17:00","17:00-18:00"];

// 生成未来7天日期
$days = [];
for($i=0;$i<7;$i++){
    $days[] = date("Y-m-d", strtotime("+$i day"));
}

// 获取教练未来7天已确认且学生已缴费的预约信息
$appointments = []; // 格式: $appointments[日期][时间段] = ['student'=>姓名, 'court'=>球台号, 'status'=>状态]
$stmt = $conn->prepare("
    SELECT a.date, a.time_slot, s.real_name, a.court_number, a.status, s.balance
    FROM appointments a
    JOIN students s ON a.student_id = s.id
    WHERE a.coach_id=? 
      AND a.status='confirmed' 
      AND a.paid='yes'
      AND s.balance>0
      AND a.date>=CURDATE()
");
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $appointments[$row['date']][$row['time_slot']] = [
        'student' => $row['real_name'],
        'court' => $row['court_number'],
        'status' => $row['status']
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>我的课程表</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f0f2f5;margin:0;padding:0;}
.container{max-width:1000px;margin:50px auto;padding:20px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;font-size:14px;}
th,td{border:1px solid #ddd;padding:6px;text-align:center;word-break:break-word;}
th{background:#3498db;color:#fff;}
.available{background:#2ecc71;color:#fff;}
.booked{background:#e74c3c;color:#fff;}
.past{background:#95a5a6;color:#fff;}
</style>
</head>
<body>
<div class="container">
<h2><?php echo htmlspecialchars($real_name); ?> 的课程表</h2>

<table>
<tr>
<th>时间/日期</th>
<?php foreach($days as $day): ?>
    <th><?php echo $day; ?></th>
<?php endforeach; ?>
</tr>

<?php foreach($time_slots as $slot): ?>
<tr>
<th><?php echo $slot; ?></th>
<?php foreach($days as $day): 
    $slot_start_time = strtotime($day . ' ' . explode('-', $slot)[0]);
    $is_past = $slot_start_time <= time();
    if($is_past): ?>
        <td class="past">已过期</td>
    <?php elseif(isset($appointments[$day][$slot])): 
        $appt = $appointments[$day][$slot]; ?>
        <td class="booked">
            <?php echo htmlspecialchars($appt['student']); ?><br>
            球台<?php echo $appt['court']; ?><br>
        </td>
    <?php else: ?>
        <td class="available">空闲</td>
    <?php endif; ?>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>

<div style="text-align:center;margin-top:20px;">
    <a href="t_success.php" style="padding:10px 20px;background:#e67e22;color:#fff;border-radius:6px;text-decoration:none;">返回主页</a>
</div>

</div>
</body>
</html>
