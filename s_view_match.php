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

// 获取该学生报名的月赛（event_date + group_type）
$stmt = $conn->prepare("SELECT event_date, group_type 
                        FROM monthly_registrations 
                        WHERE student_id=? ORDER BY event_date DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>我的比赛</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f4f4f9;padding:20px;}
h2{text-align:center;}
table{width:90%;margin:auto;border-collapse:collapse;background:#fff;box-shadow:0 0 6px rgba(0,0,0,0.1);}
th, td{border:1px solid #ddd;padding:10px;text-align:center;}
th{background:#007bff;color:white;}
a.button{display:inline-block;padding:6px 12px;background:#28a745;color:white;text-decoration:none;border-radius:4px;}
a.button:hover{background:#218838;}
</style>
</head>
<body>
<h2>我的月赛比赛</h2>

<?php if(count($registrations) > 0): ?>
    <?php foreach($registrations as $reg): ?>
        <h3><?php echo htmlspecialchars($reg['event_date']) . " 组别：" . htmlspecialchars($reg['group_type']); ?></h3>
        <?php
            // 获取该赛事的比赛列表
            $stmt = $conn->prepare("SELECT mm.id AS match_id, mm.round_no, mm.court_number, mm.status,
                                           s1.real_name AS player1_name, s2.real_name AS player2_name
                                    FROM monthly_matches mm
                                    LEFT JOIN students s1 ON mm.player1_id = s1.id
                                    LEFT JOIN students s2 ON mm.player2_id = s2.id
                                    WHERE mm.event_date=? AND mm.group_type=? AND mm.campus=?
                                    ORDER BY mm.round_no ASC");
            $stmt->bind_param("sss", $reg['event_date'], $reg['group_type'], $campus);
            $stmt->execute();
            $matches_result = $stmt->get_result();
            $stmt->close();
        ?>
        <table>
            <tr>
                <th>轮次</th>
                <th>场地号</th>
                <th>对手</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            <?php if($matches_result->num_rows > 0): ?>
                <?php while($match = $matches_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $match['round_no']; ?></td>
                        <td><?php echo $match['court_number'] ?? '待定'; ?></td>
                        <td>
                            <?php
                                $opponent = ($match['player1_name'] == $real_name) ? $match['player2_name'] : $match['player1_name'];
                                echo $opponent ?? '待定';
                            ?>
                        </td>
                        <td><?php echo $match['status']; ?></td>
                        <td>
                            <a class="button" href="s_view_op.php?match_id=<?php echo $match['match_id']; ?>">查看对战表</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">该赛事暂无比赛安排。</td></tr>
            <?php endif; ?>
        </table>
        <br>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;">你还没有报名任何比赛。</p>
<?php endif; ?>
<div style="text-align:center; margin-top:20px;">
    <form action="s_success.php" method="get">
        <button type="submit" style="background:#3498db; color:#fff; padding:8px 16px; border:none; border-radius:6px; cursor:pointer;">
            返回主页
        </button>
    </form>
</div>

</body>
</html>
