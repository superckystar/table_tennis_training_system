<?php
session_start();
require 'conn.php';

if(!isset($_SESSION['username'])){
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取学生ID
$stmt = $conn->prepare("SELECT id FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id);
$stmt->fetch();
$stmt->close();

// 获取已缴费且预约成功的课程信息
$stmt = $conn->prepare("
    SELECT a.id, a.date, a.time_slot, a.court_number, c.real_name AS coach_name, a.status
    FROM appointments a
    JOIN coaches c ON a.coach_id=c.id
    WHERE a.student_id=? AND a.status='confirmed'
    ORDER BY a.date DESC, a.time_slot DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$appointments = [];
while($row = $res->fetch_assoc()){
    $appointments[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>我的预约课程</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f0f2f5;margin:0;padding:0;}
.container{max-width:900px;margin:50px auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th,td{border:1px solid #ddd;padding:8px;text-align:center;}
th{background:#3498db;color:#fff;}
button{margin:10px;padding:10px 20px;border:none;border-radius:6px;background:#3498db;color:#fff;cursor:pointer;}
button:hover{background:#2980b9;}
</style>
</head>
<body>
<div class="container">
<h2>我的预约课程</h2>

<?php if(empty($appointments)): ?>
    <p style="text-align:center;color:#888;margin-top:20px;">您暂时没有已缴费并确认的课程。</p>
<?php else: ?>
<table>
    <tr>
        <th>日期</th>
        <th>时间段</th>
        <th>球台号</th>
        <th>教练</th>
        <th>状态</th>
    </tr>
    <?php foreach($appointments as $a): 
        $slot_start_time = strtotime($a['date'] . ' ' . explode('-', $a['time_slot'])[0]);
        $status_display = ($slot_start_time <= time()) ? '已过期' : ($a['status']=='confirmed' ? '已确认' : htmlspecialchars($a['status']));
    ?>
    <tr>
        <td><?php echo $a['date']; ?></td>
        <td><?php echo $a['time_slot']; ?></td>
        <td><?php echo $a['court_number']; ?></td>
        <td><?php echo htmlspecialchars($a['coach_name']); ?></td>
        <td><?php echo $status_display; ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<div style="text-align:center;margin-top:20px;">
    <form action="s_success.php" method="get">
        <button type="submit">返回主页</button>
    </form>
</div>
</div>
</body>
</html>
