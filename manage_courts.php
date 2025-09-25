<?php
require 'conn.php'; // 数据库连接

// 获取所有校区
$campuses = [];
$result = $conn->query("SELECT name FROM campuses ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $campuses[] = $row['name'];
}

// 选中的校区
$selected_campus = isset($_POST['campus']) ? $_POST['campus'] : '';

// 当前校区的球桌
$courts = [];

// 新增球桌
if ($selected_campus && isset($_POST['add_court'])) {
    $stmt = $conn->prepare("SELECT MAX(court_number) FROM courts WHERE campus=?");
    $stmt->bind_param("s", $selected_campus);
    $stmt->execute();
    $stmt->bind_result($max_court);
    $stmt->fetch();
    $stmt->close();

    $new_court_number = $max_court ? $max_court + 1 : 1;

    $stmt = $conn->prepare("INSERT INTO courts (campus, court_number) VALUES (?, ?)");
    $stmt->bind_param("si", $selected_campus, $new_court_number);
    $stmt->execute();
    $stmt->close();
}

// 删除球桌
if ($selected_campus && isset($_POST['delete_court'])) {
    $court_id = intval($_POST['delete_court']);
    $stmt = $conn->prepare("DELETE FROM courts WHERE id=?");
    $stmt->bind_param("i", $court_id);
    $stmt->execute();
    $stmt->close();
}

// 获取该校区所有球桌
if ($selected_campus) {
    $stmt = $conn->prepare("SELECT * FROM courts WHERE campus=? ORDER BY court_number ASC");
    $stmt->bind_param("s", $selected_campus);
    $stmt->execute();
    $result = $stmt->get_result();
    $courts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>球桌管理</title>
<style>
body { font-family:"Microsoft YaHei",Arial; background:#f4f6f9; margin:0; padding:0; }
.container { max-width:800px; margin:50px auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#333; margin-bottom:20px; }
form { margin-bottom:20px; text-align:center; }
select, button { padding:8px 12px; margin:5px; border-radius:6px; border:1px solid #ccc; }
button { background:#3498db; color:#fff; border:none; cursor:pointer; transition:background 0.3s; }
button:hover { background:#2980b9; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
table th, table td { border:1px solid #ddd; padding:10px; text-align:center; }
table th { background:#3498db; color:#fff; }
</style>
</head>
<body>
<div class="container">
<h2>球桌管理</h2>

<!-- 选择校区 -->
<form method="post">
    <label>选择校区:</label>
    <select name="campus" onchange="this.form.submit()">
        <option value="">--请选择校区--</option>
        <?php foreach($campuses as $campus): ?>
            <option value="<?php echo htmlspecialchars($campus); ?>" <?php if($campus==$selected_campus) echo 'selected'; ?>>
                <?php echo htmlspecialchars($campus); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if($selected_campus): ?>
<h3>当前校区: <?php echo htmlspecialchars($selected_campus); ?></h3>

<!-- 新增球桌 -->
<form method="post" style="text-align:center; margin-bottom:20px;">
    <input type="hidden" name="campus" value="<?php echo htmlspecialchars($selected_campus); ?>">
    <button type="submit" name="add_court">新增球桌</button>
</form>

<!-- 球桌列表 -->
<?php if(!empty($courts)): ?>
<table>
<tr>
<th>编号</th>
<th>操作</th>
</tr>
<?php foreach($courts as $court): ?>
<tr>
<td><?php echo htmlspecialchars($court['court_number']); ?></td>
<td>
    <form method="post" style="display:inline;">
        <input type="hidden" name="campus" value="<?php echo htmlspecialchars($selected_campus); ?>">
        <button type="submit" name="delete_court" value="<?php echo $court['id']; ?>" onclick="return confirm('确定删除该球桌吗？')">删除</button>
    </form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p style="text-align:center; color:#e74c3c;">当前校区没有球桌</p>
<?php endif; ?>

<?php endif; ?>

<div style="text-align:center; margin-top:20px;">
    <a href="sa_success.html" style="text-decoration:none;"><button>返回首页</button></a>
</div>

</div>
</body>
</html>
