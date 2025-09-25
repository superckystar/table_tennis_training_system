<?php
session_start();
require 'conn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];
$message = "";

// 获取教练信息
$stmt = $conn->prepare("SELECT id, username, password, real_name, gender, age, campus, phone, email, photo, level FROM coaches WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$coach = $result->fetch_assoc();
$stmt->close();

// 处理更新信息
if (isset($_POST['update'])) {
    $new_username = trim($_POST['username']);
    $real_name = $_POST['real_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 检查用户名是否重复（排除自己）
    $stmt = $conn->prepare("SELECT COUNT(*) FROM coaches WHERE username=? AND id<>?");
    $stmt->bind_param("si", $new_username, $coach['id']);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if($count > 0){
        $message = "用户名已存在，请使用其他用户名";
    } else {
        // 如果有上传新照片
        $photo_path = $coach['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_path = "uploads/photos/" . uniqid() . "." . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
        }

        // 如果密码非空则校验复杂度
        if (!empty($password)) {
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$/', $password)) {
                $message = "密码必须为8-16位，包含字母、数字和特殊字符";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE coaches SET username=?, password=?, real_name=?, gender=?, age=?, phone=?, email=?, photo=? WHERE id=?");
                $stmt->bind_param("ssssisssi", $new_username, $password_hash, $real_name, $gender, $age, $phone, $email, $photo_path, $coach['id']);
                $stmt->execute();
                $stmt->close();
                $message = "更新成功！";
                $coach['password'] = $password_hash;
            }
        } else {
            // 不修改密码
            $stmt = $conn->prepare("UPDATE coaches SET username=?, real_name=?, gender=?, age=?, phone=?, email=?, photo=? WHERE id=?");
            $stmt->bind_param("sssisssi", $new_username, $real_name, $gender, $age, $phone, $email, $photo_path, $coach['id']);
            $stmt->execute();
            $stmt->close();
            $message = "更新成功！";
        }

        // 更新页面数据
        $coach['username'] = $new_username;
        $coach['real_name'] = $real_name;
        $coach['gender'] = $gender;
        $coach['age'] = $age;
        $coach['phone'] = $phone;
        $coach['email'] = $email;
        $coach['photo'] = $photo_path;
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>个人信息 - 教练</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f4f6f9;margin:0;padding:0;}
.container{max-width:700px;margin:40px auto;background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;}
form label{display:block;margin-top:12px;font-weight:bold;}
input,select{width:100%;padding:8px;margin-top:5px;border:1px solid #ccc;border-radius:6px;}
input:focus,select:focus{border-color:#28a745;outline:none;}
button{margin-top:20px;padding:10px 20px;border:none;border-radius:6px;background:#28a745;color:#fff;font-size:16px;cursor:pointer;transition:background 0.3s;}
button:hover{background:#218838;}
.photo-preview{margin-top:10px;width:120px;height:120px;object-fit:cover;border-radius:8px;}
.message{color:#27ae60;font-weight:bold;text-align:center;margin-top:15px;}
.error{color:#e74c3c;font-weight:bold;text-align:center;margin-top:15px;}
</style>
</head>
<body>
<div class="container">
<h2>个人信息 - 教练</h2>

<?php if ($message): ?>
<div class="<?php echo ($message=='更新成功！')?'message':'error'; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>用户名</label>
    <input type="text" name="username" value="<?php echo htmlspecialchars($coach['username']); ?>" required>

<label>密码（留空则不修改）</label>
<input type="password" name="password">
<div style="font-size:13px;color:#888;margin-top:3px;margin-bottom:12px;">8-16位，包含字母、数字和特殊字符</div>

    <label>真实姓名</label>
    <input type="text" name="real_name" value="<?php echo htmlspecialchars($coach['real_name']); ?>" required>

    <label>性别</label>
    <select name="gender" required>
        <option value="male" <?php if($coach['gender']=='male') echo 'selected'; ?>>男</option>
        <option value="female" <?php if($coach['gender']=='female') echo 'selected'; ?>>女</option>
    </select>

    <label>年龄</label>
    <input type="number" name="age" value="<?php echo htmlspecialchars($coach['age']); ?>" min="18">

    <label>校区（不可修改）</label>
    <input type="text" value="<?php echo htmlspecialchars($coach['campus']); ?>" disabled>

    <label>电话</label>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($coach['phone']); ?>" required>

    <label>邮箱</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($coach['email']); ?>">

    <label>等级（不可修改）</label>
    <input type="text" value="<?php echo htmlspecialchars($coach['level']); ?>" disabled>

    <label>照片</label>
    <input type="file" name="photo" accept="image/*">
    <img src="<?php echo htmlspecialchars($coach['photo']); ?>" class="photo-preview">

    <button type="submit" name="update">保存修改</button>
</form>

<div style="text-align:center;margin-top:20px;">
    <form action="t_success.php" method="get">
        <button type="submit" style="background:#e67e22;">返回教练主页</button>
    </form>
</div>

</div>
</body>
</html>
