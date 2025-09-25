<?php
session_start();
require 'conn.php'; // 数据库连接

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取教练ID
$stmt = $conn->prepare("SELECT id FROM coaches WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($coach_id);
$stmt->fetch();
$stmt->close();

// 获取本月教练已发起取消次数
$current_month = date("Y-m");
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE coach_id=? AND cancel_requested_by='coach' 
      AND DATE_FORMAT(request_time,'%Y-%m')=? 
");
$stmt->bind_param("is", $coach_id, $current_month);
$stmt->execute();
$stmt->bind_result($cancel_count);
$stmt->fetch();
$stmt->close();

$remaining_cancels = max(0, 3 - $cancel_count);

// 获取教练未来预约（可取消的预约，状态为 confirmed 且支付成功）
$stmt = $conn->prepare("
    SELECT a.id, a.date, a.time_slot, a.court_number, s.real_name AS student_name,
           a.date, a.time_slot
    FROM appointments a 
    JOIN students s ON a.student_id = s.id
    WHERE a.coach_id=? AND a.status='confirmed' AND a.date >= CURDATE()
    ORDER BY a.date DESC, a.time_slot DESC
");
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$res = $stmt->get_result();
$appointments = [];
while($row = $res->fetch_assoc()) {
    // 检查是否距离上课时间 >=24小时
    $slot_start_time = explode("-", $row['time_slot'])[0]; // e.g., '14:00-15:00' -> '14:00'
    $appointment_datetime = strtotime($row['date'] . " " . $slot_start_time);
    $now = time();
    $row['can_cancel'] = ($appointment_datetime - $now) >= 24*3600; // true/false
    $appointments[] = $row;
}
$stmt->close();

// 提交取消申请
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $selected_ids = $_POST['appointment_id'] ?? [];
    foreach($selected_ids as $aid) {
        // 再次检查时间限制
        $stmt = $conn->prepare("SELECT date, time_slot FROM appointments WHERE id=? AND coach_id=?");
        $stmt->bind_param("ii", $aid, $coach_id);
        $stmt->execute();
        $stmt->bind_result($date, $time_slot);
        $stmt->fetch();
        $stmt->close();
        $slot_start_time = explode("-", $time_slot)[0];
        $appointment_datetime = strtotime($date . " " . $slot_start_time);
        if (($appointment_datetime - time()) >= 24*3600) {
            $stmt = $conn->prepare("
                UPDATE appointments 
                SET status='cancel_requested', cancel_requested_by='coach' 
                WHERE id=? AND coach_id=?
            ");
            $stmt->bind_param("ii", $aid, $coach_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo "<script>alert('取消申请已提交');window.location='c_cancel_appointment.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>取消预约申请 - 教练</title>
<style>
body { font-family:"Microsoft YaHei",Arial; background:#f0f2f5; margin:0; padding:0; }
.container { max-width:900px; margin:40px auto; background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#333; font-size:22px; margin-bottom:20px; }
table { width:100%; border-collapse:collapse; margin-top:15px; font-size:14px; }
th,td { border:1px solid #ddd; padding:6px; text-align:center; vertical-align:middle; }
th { background:#3498db; color:#fff; }
button { padding:8px 16px; margin-top:15px; border:none; border-radius:6px; background:#e74c3c; color:#fff; font-size:14px; cursor:pointer; }
button:disabled { background:#ccc; cursor:not-allowed; }
.info { color:#e67e22; margin-bottom:10px; text-align:center; }
</style>
</head>
<body>
<div class="container">
<h2>取消预约申请 - 教练</h2>

<div class="info">
    本月剩余可发起取消次数：<?php echo $remaining_cancels; ?>
</div>

<?php if(empty($appointments)): ?>
    <p style="text-align:center;">暂无可取消的未来预约</p>
<?php else: ?>
<form method="post">
<table>
<tr>
    <th>选择</th>
    <th>学生姓名</th>
    <th>日期</th>
    <th>时间段</th>
    <th>球台</th>
</tr>
<?php foreach($appointments as $a): ?>
<tr>
    <td>
        <input type="checkbox" name="appointment_id[]" value="<?php echo $a['id']; ?>" 
        <?php echo (!$a['can_cancel'] || $remaining_cancels <=0) ? 'disabled' : ''; ?>>
    </td>
    <td><?php echo htmlspecialchars($a['student_name']); ?></td>
    <td><?php echo $a['date']; ?></td>
    <td><?php echo $a['time_slot']; ?></td>
    <td><?php echo $a['court_number']; ?></td>
</tr>
<?php endforeach; ?>
</table>

<button type="submit" name="cancel_appointment" <?php echo $remaining_cancels <=0 ? 'disabled' : ''; ?>>
    提交取消申请
</button>
</form>
<?php endif; ?>

<div style="text-align:center;margin-top:20px;">
    <a href="t_success.php" style="padding:8px 16px; background:#3498db; color:#fff; border-radius:4px; text-decoration:none;">返回主页</a>
</div>

</div>
</body>
</html>
