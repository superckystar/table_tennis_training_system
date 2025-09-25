<?php
session_start();
require 'conn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取学生信息
$stmt = $conn->prepare("SELECT id, real_name, campus FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id, $real_name, $campus);
$stmt->fetch();
$stmt->close();

// 获取 match_id
if (!isset($_GET['match_id']) || !is_numeric($_GET['match_id'])) {
    die("参数错误！");
}

$match_id = intval($_GET['match_id']);

// 查询该场比赛信息
$stmt = $conn->prepare("
    SELECT mm.round_no, mm.court_number, mm.status,
           s1.real_name AS player1_name, s2.real_name AS player2_name,
           mm.event_date, mm.group_type, mm.campus
    FROM monthly_matches mm
    LEFT JOIN students s1 ON mm.player1_id = s1.id
    LEFT JOIN students s2 ON mm.player2_id = s2.id
    WHERE mm.id = ? AND (mm.player1_id = ? OR mm.player2_id = ?)
");
$stmt->bind_param("iii", $match_id, $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$match = $result->fetch_assoc();
$stmt->close();

if (!$match) {
    die("未找到该比赛或你未参加此比赛。");
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>对战详情</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f4f4f9;padding:20px;}
h2{text-align:center;}
table{width:80%;margin:auto;border-collapse:collapse;background:#fff;box-shadow:0 0 6px rgba(0,0,0,0.1);}
th, td{border:1px solid #ddd;padding:10px;text-align:center;}
th{background:#007bff;color:white;}
a.button{display:inline-block;padding:6px 12px;background:#3498db;color:white;text-decoration:none;border-radius:4px;}
a.button:hover{background:#2176b8;}
</style>
</head>
<body>
<h2>比赛对战详情</h2>
<p style="text-align:center;">
    日期: <?php echo $match['event_date']; ?> |
    组别: <?php echo $match['group_type']; ?> |
    校区: <?php echo $match['campus']; ?>
</p>

<table>
    <tr>
        <th>轮次</th>
        <th>选手1</th>
        <th>选手2</th>
        <th>场地</th>
        <th>状态</th>
    </tr>
    <tr>
        <td><?php echo $match['round_no']; ?></td>
        <td><?php echo $match['player1_name'] ?? '待定'; ?></td>
        <td><?php echo $match['player2_name'] ?? '待定'; ?></td>
        <td><?php echo $match['court_number'] ?? '待定'; ?></td>
        <td><?php echo $match['status']; ?></td>
    </tr>
</table>

<div style="text-align:center;margin-top:20px;">
    <a class="button" href="s_view_match.php">返回我的比赛列表</a>
</div>

</body>
</html>
