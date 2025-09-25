<?php
session_start();
require 'conn.php'; // 数据库连接文件

// 检查教练登录
$username = $_SESSION['username'] ?? '';
if (!$username) {
    die("请先登录");
}

// 获取教练ID
$stmt = $conn->prepare("SELECT id FROM coaches WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($coach_id);
$stmt->fetch();
$stmt->close();

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $action = $_POST['action'];

    if ($action === 'confirm') {
        $stmt = $conn->prepare("UPDATE appointments SET status='confirmed', confirmed_time=NOW() WHERE id=? AND coach_id=?");
        $stmt->bind_param("ii", $appointment_id, $coach_id);
        $stmt->execute();
        $stmt->close();
        $msg = "预约已确认";
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE appointments SET status='rejected' WHERE id=? AND coach_id=?");
        $stmt->bind_param("ii", $appointment_id, $coach_id);
        $stmt->execute();
        $stmt->close();
        $msg = "预约已拒绝";
    }
    echo "<script>alert('{$msg}'); window.location='c_review_appointments.php';</script>";
    exit();
}

// 获取教练待审核的预约
$stmt = $conn->prepare("
    SELECT a.id, s.real_name AS student_name, a.campus, a.court_number, a.date, a.time_slot 
    FROM appointments a
    JOIN students s ON a.student_id=s.id
    WHERE a.coach_id=? AND a.status='pending'
    ORDER BY a.date, a.time_slot
");
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>预约审核 - 教练端</title>
<style>
body { font-family:"Microsoft YaHei",Arial; background:#f4f6f9; margin:0; padding:0; }
.container { max-width:900px; margin:40px auto; background:#fff; padding:20px 30px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#333; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ddd; padding:10px; text-align:center; }
th { background:#3498db; color:#fff; }
button { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; transition:0.3s; }
button.confirm { background:#2ecc71; color:#fff; }
button.confirm:hover { background:#27ae60; }
button.reject { background:#e74c3c; color:#fff; }
button.reject:hover { background:#c0392b; }
.back { display:inline-block; margin-top:20px; padding:8px 16px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none; }
.back:hover { background:#2980b9; }
</style>
</head>
<body>
<div class="container">
<h2>预约审核</h2>

<?php if (empty($appointments)): ?>
    <p style="text-align:center; margin-top:30px;">暂无待审核的预约</p>
<?php else: ?>
<table>
<tr>
    <th>学生姓名</th>
    <th>校区</th>
    <th>球台</th>
    <th>日期</th>
    <th>时间段</th>
    <th>操作</th>
</tr>
<?php foreach($appointments as $a): ?>
<tr>
    <td><?php echo htmlspecialchars($a['student_name']); ?></td>
    <td><?php echo htmlspecialchars($a['campus']); ?></td>
    <td><?php echo htmlspecialchars($a['court_number']); ?></td>
    <td><?php echo htmlspecialchars($a['date']); ?></td>
    <td><?php echo htmlspecialchars($a['time_slot']); ?></td>
    <td>
        <form method="post" style="display:inline-block;">
            <input type="hidden" name="appointment_id" value="<?php echo $a['id']; ?>">
            <button type="submit" name="action" value="confirm" class="confirm">确认</button>
        </form>
        <form method="post" style="display:inline-block;">
            <input type="hidden" name="appointment_id" value="<?php echo $a['id']; ?>">
            <button type="submit" name="action" value="reject" class="reject">拒绝</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<div style="text-align:center;">
    <a href="t_success.php" class="back">返回主页</a>
</div>
</div>
</body>
</html>
