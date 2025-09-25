<?php
session_start();
require 'conn.php';  // 数据库连接

// 添加或修改校区
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_campus'])) {
    $campus_id = $_POST['campus_id'] ?? null;
    $name = $_POST['name'];
    $address = $_POST['address'];
    $contact_person = $_POST['contact_person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    $selected_admin_id = $_POST['selected_admin_id'] ?? null;
    $admin_username = $_POST['admin_username'] ?? null;
    $admin_password = $_POST['admin_password'] ?? null;
    $admin_name = $_POST['admin_name'] ?? null;
    $admin_phone = $_POST['admin_phone'] ?? null;

    $admin_id = null;

    // 如果新建管理员信息完整，则创建新管理员
    if (!empty($admin_username) && !empty($admin_password) && !empty($admin_name) && !empty($admin_phone)) {
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$/', $admin_password)) {
            echo "<script>alert('管理员密码必须为8-16位，包含字母、数字和特殊字符'); history.back();</script>";
            exit();
        }

        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO admins (username, password, name, phone, campus) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $admin_username, $hashed_password, $admin_name, $admin_phone,$name);
        $stmt->execute();
        $admin_id = $stmt->insert_id;
        $stmt->close();
    } elseif (!empty($selected_admin_id)) {
        $admin_id = $selected_admin_id;
    } else {
        echo "<script>alert('请为校区指定管理员（新建或选择已有管理员）'); history.back();</script>";
        exit();
    }

    // 修改校区
    if (!empty($campus_id)) {
        // 获取原管理员
        $stmt = $conn->prepare("SELECT admin_id FROM campuses WHERE id=?");
        $stmt->bind_param("i", $campus_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $old_admin_id = $row['admin_id'];
        $stmt->close();

        // 如果原管理员被替换，原管理员的校区改为暂无校区
        if ($old_admin_id != $admin_id) {
            $stmt = $conn->prepare("UPDATE admins SET campus='暂无校区' WHERE id=?");
            $stmt->bind_param("i", $old_admin_id);
            $stmt->execute();
            $stmt->close();
        }

        // 更新校区信息
        $stmt = $conn->prepare("UPDATE campuses SET name=?, address=?, contact_person=?, phone=?, email=?, admin_id=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $address, $contact_person, $phone, $email, $admin_id, $campus_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // 新增校区
        $stmt = $conn->prepare("INSERT INTO campuses (name, address, contact_person, phone, email, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $name, $address, $contact_person, $phone, $email, $admin_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: manage_campus.php");
    exit();
}

// 删除校区
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM campuses WHERE id=$id");
    header("Location: manage_campus.php");
    exit();
}

// 查询现有管理员
$admins = $conn->query("SELECT id, username FROM admins");

// 查询现有校区
$campuses = $conn->query("SELECT c.*, a.username as admin_name FROM campuses c LEFT JOIN admins a ON c.admin_id=a.id");
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>校区管理 - 乒乓培训机构管理系统</title>
<style>
body {font-family: Arial, sans-serif; background: #f4f6f9; margin:0; padding:0;}
.container {width: 90%; margin: 30px auto; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);}
h2, h3, h4 {text-align: center; color: #2c3e50;}
label {display:block; margin-top:10px; font-weight: bold;}
input, select {padding: 8px; margin: 5px 0; width: calc(100% - 20px);}
button {padding: 10px 20px; margin-top: 10px; background: #3498db; color: #fff; border: none; border-radius: 8px; cursor: pointer;}
button:hover {background: #2980b9;}
table {width: 100%; border-collapse: collapse; margin-top: 20px;}
th, td {border: 1px solid #ddd; padding: 12px; text-align: center;}
th {background: #3498db; color: #fff;}
a {color: #e74c3c; text-decoration: none;}
a:hover {text-decoration: underline;}
</style>
</head>
<body>
<div class="container">
<h2>校区管理</h2>

<form method="POST" action="">
    <h3 id="form-title">添加新校区</h3>
    <input type="hidden" name="campus_id" id="campus_id">

    <label>校区名称</label>
    <input type="text" name="name" id="name" placeholder="校区名称" required>

    <label>地址</label>
    <input type="text" name="address" id="address" placeholder="地址" required>

    <label>联系人</label>
    <input type="text" name="contact_person" id="contact_person" placeholder="联系人" required>

    <label>联系电话</label>
    <input type="text" name="phone" id="phone" placeholder="联系电话" required>

    <label>联系邮箱</label>
    <input type="email" name="email" id="email" placeholder="联系邮箱">

    <h4>校区管理员</h4>
    <label>从已有管理员选择</label>
    <select name="selected_admin_id" id="selected_admin_id">
        <option value="">--不选择--</option>
        <?php
        $admins->data_seek(0);
        while($row = $admins->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>"><?php echo $row['username']; ?></option>
        <?php endwhile; ?>
    </select>

    <h4>或新建管理员</h4>
    <label>用户名</label>
    <input type="text" name="admin_username" id="admin_username" placeholder="用户名">

    <label>密码</label>
    <input type="password" name="admin_password" id="admin_password" placeholder="密码 (8-16位，字母数字特殊符号)">

    <label>姓名</label>
    <input type="text" name="admin_name" id="admin_name" placeholder="真实姓名">

    <label>联系电话</label>
    <input type="text" name="admin_phone" id="admin_phone" placeholder="电话">

    <button type="submit" name="add_campus" id="submit_btn">提交</button>
    <button type="button" id="cancel_btn" style="display:none; margin-left:10px;" onclick="cancelEdit()">取消修改</button>
    <button type="button" onclick="window.location.href='sa_success.html'" style=" margin-bottom:20px;">
    返回主页
</button>

</form>

<h3>现有校区</h3>
<table>
<tr>
<th>ID</th><th>校区名称</th><th>地址</th><th>联系人</th><th>电话</th><th>邮箱</th><th>负责人</th><th>操作</th>
</tr>
<?php while($row = $campuses->fetch_assoc()): ?>
<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['address']; ?></td>
<td><?php echo $row['contact_person']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['admin_name'] ?? '暂无'; ?></td>
<td>
    <a href="#" onclick='editCampus(<?php echo json_encode($row); ?>)'>修改</a> |
    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('确定删除该校区？');">删除</a>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<script>
function editCampus(campus) {
    document.getElementById('form-title').innerText = '修改校区信息';
    document.getElementById('campus_id').value = campus.id;
    document.getElementById('name').value = campus.name;
    document.getElementById('address').value = campus.address;
    document.getElementById('contact_person').value = campus.contact_person;
    document.getElementById('phone').value = campus.phone;
    document.getElementById('email').value = campus.email;
    document.getElementById('selected_admin_id').value = campus.admin_id || '';
    document.getElementById('admin_username').value = '';
    document.getElementById('admin_password').value = '';
    document.getElementById('admin_name').value = '';
    document.getElementById('admin_phone').value = '';
    document.getElementById('submit_btn').innerText = '保存修改';
    document.getElementById('cancel_btn').style.display = 'inline-block';
}

function cancelEdit() {
    document.getElementById('form-title').innerText = '添加新校区';
    document.getElementById('campus_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('address').value = '';
    document.getElementById('contact_person').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('email').value = '';
    document.getElementById('selected_admin_id').value = '';
    document.getElementById('admin_username').value = '';
    document.getElementById('admin_password').value = '';
    document.getElementById('admin_name').value = '';
    document.getElementById('admin_phone').value = '';
    document.getElementById('submit_btn').innerText = '提交';
    document.getElementById('cancel_btn').style.display = 'none';
}
</script>

</body>
</html>
<?php $conn->close(); ?>
