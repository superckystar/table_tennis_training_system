<?php
session_start();
require 'conn.php';

// 未登录则跳转
if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取学生信息
$stmt = $conn->prepare("SELECT id, real_name, campus, balance FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id, $real_name, $campus, $balance);
$stmt->fetch();
$stmt->close();

// 工具函数：获取某月第四个周日
function getFourthSunday($year, $month) {
    $firstDay = new DateTime("$year-$month-01");
    $dayOfWeek = $firstDay->format('w'); // 周日=0
    $offset = (7 - $dayOfWeek) % 7; // 距离第一个周日的天数
    $firstSunday = clone $firstDay;
    $firstSunday->modify("+$offset days");
    $fourthSunday = clone $firstSunday;
    $fourthSunday->modify("+3 weeks");
    return $fourthSunday;
}

// 默认赛事费用
$fee = 30.00;

// 报名处理
if (isset($_POST['register'])) {
    $month = $_POST['month']; // 格式：Y-m
    $group_type = $_POST['group_type'];

    // 计算该月第四个周日
    list($y, $m) = explode("-", $month);
    $event_date_obj = getFourthSunday($y, $m);
    $event_date = $event_date_obj->format('Y-m-d');

    if ($balance < $fee) {
        $error_msg = "余额不足，请先充值！";
    } else {
        $conn->begin_transaction();
        try {
            // 检查是否已报名过该时间点（不区分组别）
            $stmt = $conn->prepare("SELECT id FROM monthly_registrations WHERE student_id=? AND event_date=?");
            $stmt->bind_param("is", $student_id, $event_date);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                throw new Exception("您本场赛事已报名，不能重复报名！");
            }
            $stmt->close();

            // 扣费
            $new_balance = $balance - $fee;
            $stmt = $conn->prepare("UPDATE students SET balance=? WHERE id=?");
            $stmt->bind_param("di", $new_balance, $student_id);
            $stmt->execute();
            $stmt->close();

            // 插入报名记录
            $stmt = $conn->prepare("INSERT INTO monthly_registrations (student_id, campus, event_date, group_type, paid, paid_at) VALUES (?, ?, ?, ?, 1, NOW())");
            $stmt->bind_param("isss", $student_id, $campus, $event_date, $group_type);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $success_msg = "报名成功！赛事日期：$event_date ，费用 $fee 元已扣除。";
            $balance = $new_balance;
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>月赛报名</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f0f2f5;margin:0;padding:0;}
.container{max-width:600px;margin:50px auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;}
form label{display:block;margin-top:10px;font-weight:bold;color:#444;}
form input, form select{width:100%;padding:10px;margin-top:5px;border:1px solid #ddd;border-radius:6px;font-size:14px;}
form input:focus, form select:focus{border-color:#28a745;outline:none;}
button{margin-top:20px;padding:10px 20px;border:none;border-radius:6px;background:#28a745;color:#fff;font-size:16px;cursor:pointer;transition:background 0.3s;}
button:hover{background:#218838;}
.success-msg{color:green;margin-top:10px;text-align:center;}
.error-msg{color:red;margin-top:10px;text-align:center;}
.return-btn{background:#3498db;margin-top:15px;}
.return-btn:hover{background:#2980b9;}
</style>
</head>
<body>
<div class="container">
<h2>月赛报名</h2>
<?php 
    if (isset($success_msg)) echo "<div class='success-msg'>$success_msg</div>"; 
    if (isset($error_msg)) echo "<div class='error-msg'>$error_msg</div>";
?>
<form method="post">
    <label>学员姓名：</label>
    <input type="text" value="<?php echo htmlspecialchars($real_name); ?>" disabled>

    <label>所属校区：</label>
    <input type="text" value="<?php echo htmlspecialchars($campus); ?>" disabled>

    <label>账户余额：</label>
    <input type="text" value="<?php echo htmlspecialchars($balance); ?> 元" disabled>

    <label>选择月份：</label>
    <select name="month" required>
        <option value="">请选择月份</option>
        <?php
        $now = new DateTime();
        for ($i = 0; $i < 6; $i++) {
            $monthValue = $now->format("Y-m");
            $monthLabel = $now->format("Y年m月");
            echo "<option value=\"$monthValue\">$monthLabel</option>";
            $now->modify("+1 month");
        }
        ?>
    </select>

    <label>选择组别：</label>
    <select name="group_type" required>
        <option value="">请选择组别</option>
        <option value="甲">甲组</option>
        <option value="乙">乙组</option>
        <option value="丙">丙组</option>
    </select>

    <label>报名费用：</label>
    <input type="text" value="<?php echo $fee; ?> 元" disabled>

    <button type="submit" name="register">确认报名</button>
</form>

<form action="s_success.php" method="get" style="text-align:center;">
    <button type="submit" class="return-btn">返回主页</button>
</form>
</div>
</body>
</html>
