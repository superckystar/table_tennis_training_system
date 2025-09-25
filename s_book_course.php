<?php
session_start();
require 'conn.php'; // 数据库连接文件

$username = $_SESSION['username'] ?? '';
if (!$username) die("请先登录");

// 获取学生ID和校区
$stmt = $conn->prepare("SELECT id, campus FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id, $campus);
$stmt->fetch();
$stmt->close();

// 获取学生已建立双选关系的教练
$coaches = [];
$stmt = $conn->prepare("
    SELECT c.id, c.real_name 
    FROM coaches c
    JOIN student_coach_relations r ON c.id=r.coach_id
    WHERE r.student_id=? AND r.status='approved'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $coaches[] = $row;
$stmt->close();

// 选中的教练ID
$selected_coach_id = intval($_GET['coach_id'] ?? 0);

// 生成未来一周日期
$days = [];
for($i=0;$i<7;$i++){
    $days[] = date("Y-m-d", strtotime("+$i day"));
}

// 时间段
$time_slots = ["08:00-09:00","09:00-10:00","10:00-11:00","11:00-12:00",
               "14:00-15:00","15:00-16:00","16:00-17:00","17:00-18:00"];

// 获取该校区所有球台
$courts = [];
$stmt = $conn->prepare("SELECT court_number FROM courts WHERE campus=?");
$stmt->bind_param("s",$campus);
$stmt->execute();
$res = $stmt->get_result();
while($r=$res->fetch_assoc()) $courts[]=$r['court_number'];
$stmt->close();

// 获取该校区未来一周的预约情况（按日期+时间段记录已占用球台，按教练区分）
$appointments = [];
if($selected_coach_id>0){
    $stmt = $conn->prepare("
        SELECT date, time_slot, court_number, coach_id
        FROM appointments 
        WHERE date>=CURDATE() AND campus=? AND status IN ('pending','confirmed')
    ");
    $stmt->bind_param("s", $campus);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $appointments[$row['date']][$row['time_slot']][$row['coach_id']][] = $row['court_number'];
    }
    $stmt->close();
}

// 处理预约提交
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['book_multiple'])) {
    $coach_id = intval($_POST['coach_id']);
    foreach($_POST as $key=>$val){
        if(strpos($key,'court_')===0 && $val !== ""){ 
            $suffix = substr($key,6); 
            $date = $_POST['date_'.$suffix];
            $time_slot = $_POST['slot_'.$suffix];
            $court_number = $val==='auto' ? null : intval($val);

            // 检查时间是否已过
            $slot_start_time = strtotime($date . ' ' . explode('-', $time_slot)[0]);
            if($slot_start_time <= time()){
                echo "<script>alert('{$date} {$time_slot}：该时间段已过，无法预约');</script>";
                continue;
            }

            // 自动分配球台
            if($court_number===null){
                $assigned = false;
                foreach($courts as $cnum){
                    if(!in_array($cnum, $appointments[$date][$time_slot][$coach_id] ?? [])){
                        $court_number = $cnum;
                        $assigned = true;
                        break;
                    }
                }
                if(!$assigned){
                    echo "<script>alert('{$date} {$time_slot}：无可用球台');</script>";
                    continue;
                }
            } else {
                // 手动选择球台，检查是否已被占用
                if(in_array($court_number, $appointments[$date][$time_slot][$coach_id] ?? [])){
                    echo "<script>alert('{$date} {$time_slot}：该球台已被占用');</script>";
                    continue;
                }
            }

            // 插入预约
            $stmt = $conn->prepare("
                INSERT INTO appointments (student_id, coach_id, campus, court_number, date, time_slot) 
                VALUES (?,?,?,?,?,?)
            ");
            $stmt->bind_param("iisiss", $student_id, $coach_id, $campus, $court_number, $date, $time_slot);
            $stmt->execute();
            $stmt->close();

            // 更新已占用球台数组，防止重复选择
            $appointments[$date][$time_slot][$coach_id][] = $court_number;
        }
    }
    echo "<script>alert('预约提交完成');window.location='s_book_course.php?coach_id=$coach_id';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>预约课程</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f4f6f9;margin:0;padding:0;}
.container{max-width:900px;margin:40px auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;font-size:22px;}
select,button{padding:3px 6px;margin:2px;border-radius:4px;border:1px solid #ccc;font-size:12px;}
button{background:#3498db;color:#fff;border:none;cursor:pointer;}
button:hover{background:#2980b9;}
.table-wrapper{overflow-x:auto;}
table{width:100%;border-collapse:collapse;margin-top:15px;table-layout: fixed;font-size:12px;}
th,td{border:1px solid #ddd;padding:4px;text-align:center;vertical-align:middle;word-wrap:break-word;}
th{background:#3498db;color:#fff;font-size:13px;}
.available{background:#2ecc71;color:#fff;}
.booked{background:#e74c3c;color:#fff;}
.full{background:#e67e22;color:#fff;font-weight:bold;}
</style>
</head>
<body>
<div class="container">
<h2>预约课程</h2>

<form method="get">
    <label>选择教练:</label>
    <select name="coach_id" onchange="this.form.submit()">
        <option value="">请选择教练</option>
        <?php foreach($coaches as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php if($selected_coach_id==$c['id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($c['real_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if($selected_coach_id>0): ?>
<form method="post">
<div class="table-wrapper">
<table>
<tr>
<th>时间/日期</th>
<?php foreach($days as $day): ?><th><?php echo $day; ?></th><?php endforeach; ?>
</tr>

<?php foreach($time_slots as $slot): ?>
<tr>
<th><?php echo $slot; ?></th>
<?php foreach($days as $day): 
    $slot_start_time = strtotime($day . ' ' . explode('-', $slot)[0]);
    $is_past = $slot_start_time <= time();

    // 判断该教练在该时间段是否已被预约
    $coach_booked = !empty($appointments[$day][$slot][$selected_coach_id]);
    $available_courts = array_diff($courts, $appointments[$day][$slot][$selected_coach_id] ?? []);
?>
<td class="<?php echo $is_past ? 'booked' : ($coach_booked ? 'full' : 'available'); ?>">
<?php if($is_past): ?>
    已过期
<?php elseif($coach_booked): ?>
    已满
<?php else: ?>
    <select name="court_<?php echo $day . '_' . str_replace(':','',$slot); ?>">
        <option value="">请选择球台</option>
        <option value="auto">自动分配</option>
        <?php foreach($available_courts as $cnum): ?>
            <option value="<?php echo $cnum; ?>"><?php echo $cnum; ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="date_<?php echo $day . '_' . str_replace(':','',$slot); ?>" value="<?php echo $day; ?>">
    <input type="hidden" name="slot_<?php echo $day . '_' . str_replace(':','',$slot); ?>" value="<?php echo $slot; ?>">
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
</div>
<br>
<input type="hidden" name="coach_id" value="<?php echo $selected_coach_id; ?>">
<button type="submit" name="book_multiple">提交预约</button>
</form>
<?php endif; ?>

<div style="text-align:center;margin-top:20px;">
    <a href="s_success.php" style="padding:8px 16px;background:#e67e22;color:#fff;border-radius:4px;text-decoration:none;">返回主页</a>
</div>
</div>
</body>
</html>
