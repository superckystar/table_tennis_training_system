<?php
session_start();
require 'conn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// ��ȡѧ����Ϣ
$stmt = $conn->prepare("SELECT id, real_name, campus FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id, $real_name, $campus);
$stmt->fetch();
$stmt->close();

// ��ȡ match_id
if (!isset($_GET['match_id']) || !is_numeric($_GET['match_id'])) {
    die("��������");
}

$match_id = intval($_GET['match_id']);

// ��ѯ�ó�������Ϣ
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
    die("δ�ҵ��ñ�������δ�μӴ˱�����");
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>��ս����</title>
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
<h2>������ս����</h2>
<p style="text-align:center;">
    ����: <?php echo $match['event_date']; ?> |
    ���: <?php echo $match['group_type']; ?> |
    У��: <?php echo $match['campus']; ?>
</p>

<table>
    <tr>
        <th>�ִ�</th>
        <th>ѡ��1</th>
        <th>ѡ��2</th>
        <th>����</th>
        <th>״̬</th>
    </tr>
    <tr>
        <td><?php echo $match['round_no']; ?></td>
        <td><?php echo $match['player1_name'] ?? '����'; ?></td>
        <td><?php echo $match['player2_name'] ?? '����'; ?></td>
        <td><?php echo $match['court_number'] ?? '����'; ?></td>
        <td><?php echo $match['status']; ?></td>
    </tr>
</table>

<div style="text-align:center;margin-top:20px;">
    <a class="button" href="s_view_match.php">�����ҵı����б�</a>
</div>

</body>
</html>
