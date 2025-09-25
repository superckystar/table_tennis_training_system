<?php
session_start();
require 'conn.php'; // 数据库连接文件

$username = $_SESSION['username'] ?? '';
if (!$username) {
    header("Location: login_t.php");
    exit();
}

// 获取学生ID
$stmt = $conn->prepare("SELECT id FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id);
$stmt->fetch();
$stmt->close();

// 获取当月学生已取消次数
$current_month = date('Y-m');
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE student_id=? AND cancel_requested_by='student' AND DATE_FORMAT(request_time,'%Y-%m')=?
");
$stmt->bind_param("is", $student_id, $current_month);
$stmt->execute();
$stmt->bind_result($cancel_count);
$stmt->fetch();
$stmt->close();
$remaining_cancels = max(0, 3 - $cancel_count);

// 提交取消申请
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cancel_id'])) {
    $cancel_id = intval($_POST['cancel_id']);

    // 检查是否可取消
    $stmt = $conn->prepare("
        SELECT date, time_slot, status, paid 
        FROM appointments 
        WHERE id=? AND student_id=? AND status='confirmed' AND paid='yes'
    ");
    $stmt->bind_param("ii", $cancel_id, $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $appointment = $res->fetch_assoc();
    $stmt->close();

    if ($appointment) {
        $appointment_datetime = strtotime($appointment['date'] . ' ' . explode('-', $appointment['time_slot'])[0]);
        if ($appointment_datetime - time() >= 24*3600) {
            if ($remaining_cancels > 0) {
                // 更新状态为取消申请
                $stmt = $conn->prepare("
                    UPDATE appointments 
                    SET status='cancel_requested', cancel_requested_by='student' 
                    WHERE id=?
                ");
                $stmt->bind_param("i", $cancel_id);
                $stmt->execute();
                $stmt->close();
                echo "<script>alert('取消申请提交成功，请等待教练确认');window.location='s_cancel_appointment.php';</script>";
                exit();
            } else {
                echo "<script>alert('本月取消次数已用完，无法取消');</script>";
            }
        } else {
            echo "<script>alert('距离上课不足24小时，无法取消');</script>";
        }
    } else {
        echo "<script>alert('该预约不可取消');</script>";
    }
}

// 获取学生未来可取消预约
$stmt = $conn->prepare("
    SELECT a.id, a.date, a.time_slot, a.court_number, c.real_name 
    FROM appointments a 
    JOIN coaches c ON a.coach_id=c.id 
    WHERE a.student_id=? AND a.status='confirmed' AND a.paid='yes' AND a.date >= CURDATE()
    ORDER BY a.date DESC, a.time_slot DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$appointments = [];
while ($row = $res->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>取消预约</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f4f6f9;margin:0;padding:0;}
.container{max-width:900px;margin:40px auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;font-size:22px;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
th,td{border:1px solid #ddd;padding:8px;text-align:center;}
th{background:#3498db;color:#fff;}
button{padding:6px 12px;border:none;border-radius:6px;background:#e74c3c;color:#fff;cursor:pointer;}
button:disabled{background:#ccc;cursor:not-allowed;}
</style>
</head>
<body>
<div class="container">
<h2>取消预约（本月剩余可取消次数：<?php echo $remaining_cancels; ?>）</h2>

<?php if (empty($appointments)): ?>
<p>暂无可取消的预约。</p>
<?php else: ?>
<table>
<tr>
<th>日期</th>
<th>时间段</th>
<th>教练</th>
<th>球台号</th>
<th>操作</th>
</tr>
<?php foreach($appointments as $a):
    $appointment_datetime = strtotime($a['date'] . ' ' . explode('-', $a['time_slot'])[0]);
    $can_cancel = ($appointment_datetime - time() >= 24*3600) && ($remaining_cancels > 0);
?>
<tr>
    <td><?php echo $a['date']; ?></td>
    <td><?php echo $a['time_slot']; ?></td>
    <td><?php echo htmlspecialchars($a['real_name']); ?></td>
    <td><?php echo $a['court_number']; ?></td>
    <td>
        <form method="post" style="margin:0;">
            <input type="hidden" name="cancel_id" value="<?php echo $a['id']; ?>">
            <button type="submit" <?php echo $can_cancel ? '' : 'disabled'; ?>>取消预约</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<div style="text-align:center;margin-top:20px;">
    <a href="s_success.php" style="padding:8px 16px;background:#3498db;color:#fff;border-radius:4px;text-decoration:none;">返回主页</a>
</div>
</div>
</body>
</html>
