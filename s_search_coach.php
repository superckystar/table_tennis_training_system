<?php
session_start();
require 'conn.php';

if(!isset($_SESSION['username'])){
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取学生的校区
$stmt = $conn->prepare("SELECT campus FROM students WHERE username=?");
$stmt->bind_param("s",$username);
$stmt->execute();
$stmt->bind_result($campus);
$stmt->fetch();
$stmt->close();

$search_results = [];

// 从 GET 读取查询条件
$real_name = isset($_GET['real_name']) ? trim($_GET['real_name']) : '';
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$age = isset($_GET['age']) ? $_GET['age'] : '';
$browse = isset($_GET['browse']);
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// 按条件查询
if (!$browse && ($real_name !== '' || $gender !== '' || $age !== '')) {
    $conditions = [];
    $params = [];
    $types = "";

    if($real_name !== ""){
        $conditions[] = "real_name LIKE ?";
        $params[] = "%$real_name%";
        $types .= "s";
    }
    if($gender !== ""){
        $conditions[] = "gender = ?";
        $params[] = $gender;
        $types .= "s";
    }
    if($age !== ""){
        $conditions[] = "age = ?";
        $params[] = $age;
        $types .= "i";
    }

    if(count($conditions) > 0){
        $sql = "SELECT * FROM coaches WHERE campus=? AND " . implode(" AND ", $conditions);
        $stmt = $conn->prepare($sql);
        $types = "s" . $types;
        $params = array_merge([$campus], $params);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $search_results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// 浏览全部
if($browse){
    $sql = "SELECT * FROM coaches WHERE campus=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$campus);
    $stmt->execute();
    $result = $stmt->get_result();
    $search_results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>查询教练员</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f4f6f9;margin:0;padding:0;}
.container{max-width:1000px;margin:40px auto;background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;}
form{margin:20px 0;}
label{margin-right:10px;font-weight:bold;}
input,select{padding:6px 10px;margin-right:10px;border:1px solid #ccc;border-radius:6px;}
button{padding:8px 15px;border:none;border-radius:6px;background:#3498db;color:#fff;cursor:pointer;transition:all 0.3s;}
button:hover{background:#2980b9;}
.table{width:100%;border-collapse:collapse;margin-top:20px;}
.table th,.table td{border:1px solid #ddd;padding:10px;text-align:center;vertical-align:middle;}
.table th{background:#3498db;color:#fff;}
.photo{width:80px;height:80px;object-fit:cover;border-radius:8px;}
.notice{color:#27ae60;text-align:center;font-weight:bold;margin-top:10px;}
@media (max-width:800px){
    .container { padding: 16px; }
    .photo{width:60px;height:60px;}
    input,select{margin-bottom:8px;}
}
.disabled-btn{background:grey;color:#fff;cursor:not-allowed;}
</style>
</head>
<body>
<div class="container">
<h2>查询教练员</h2>

<form method="get" action="s_search_coach.php">
    <label>姓名:</label>
    <input type="text" name="real_name" value="<?php echo htmlspecialchars($real_name); ?>">
    <label>性别:</label>
    <select name="gender">
        <option value="">不限</option>
        <option value="male" <?php if($gender=='male') echo 'selected'; ?>>男</option>
        <option value="female" <?php if($gender=='female') echo 'selected'; ?>>女</option>
    </select>
    <label>年龄:</label>
    <input type="number" name="age" min="18" value="<?php echo htmlspecialchars($age); ?>">
    <button type="submit" name="search" value="1">按条件查询</button>
    <button type="submit" name="browse" value="1" style="background:#2ecc71;">浏览全部</button>
</form>

<?php if(!empty($search_results)): ?>
    <div class="notice">点击“选中教练”可申请教练，最多保持或通过申请两名教练。</div>
    <table class="table">
    <tr>
    <th>照片</th><th>姓名</th><th>性别</th><th>年龄</th><th>电话</th><th>邮箱</th><th>等级</th><th>获奖经历</th><th>操作</th>
    </tr>
    <?php foreach($search_results as $coach): ?>
    <tr id="coach_<?php echo (int)$coach['id']; ?>">
    <td><img src="<?php echo htmlspecialchars($coach['photo']); ?>" class="photo" alt="photo"></td>
    <td><?php echo htmlspecialchars($coach['real_name']); ?></td>
    <td><?php echo $coach['gender']=='male'?'男':'女'; ?></td>
    <td><?php echo htmlspecialchars($coach['age']); ?></td>
    <td><?php echo htmlspecialchars($coach['phone']); ?></td>
    <td><?php echo htmlspecialchars($coach['email']); ?></td>
    <td><?php echo htmlspecialchars($coach['level']); ?></td>
    <td style="text-align:left;max-width:300px;"><?php echo nl2br(htmlspecialchars($coach['achievements'])); ?></td>
    <td>
        <?php
        // 查询学生与教练关系状态
        $stmt = $conn->prepare("SELECT status, action_type FROM student_coach_relations WHERE student_id=(SELECT id FROM students WHERE username=?) AND coach_id=? ORDER BY request_date DESC LIMIT 1");
        $stmt->bind_param("si", $username, $coach['id']);
        $stmt->execute();
        $stmt->bind_result($rel_status, $rel_action);
        $stmt->fetch();
        $stmt->close();

        if ($rel_status === 'approved' && $rel_action === 'bind') {
            echo '<button type="button" class="disabled-btn">已绑定</button>';
        } elseif ($rel_status === 'pending' && $rel_action === 'bind') {
            echo '<button type="button" class="disabled-btn">正在审核</button>';
        } else {
            echo '<form action="apply_coach.php" method="post" style="margin:0;">';
            echo '<input type="hidden" name="coach_id" value="' . (int)$coach['id'] . '">';
            echo '<input type="hidden" name="real_name" value="' . htmlspecialchars($real_name) . '">';
            echo '<input type="hidden" name="gender" value="' . htmlspecialchars($gender) . '">';
            echo '<input type="hidden" name="age" value="' . htmlspecialchars($age) . '">';
            echo '<input type="hidden" name="browse" value="' . ($browse ? '1' : '') . '">';
            echo '<button type="submit" style="background:#e67e22;">选中教练</button>';
            echo '</form>';
        }
        ?>
    </td>
    </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>

<div style="text-align:center;margin-top:20px;">
    <form action="s_success.php" method="get">
        <button type="submit" style="background:#e67e22;">返回学生主页</button>
    </form>
</div>

</div>

<?php if($msg): ?>
<script>
    alert("<?php echo addslashes($msg); ?>");
</script>
<?php endif; ?>
</body>
</html>
