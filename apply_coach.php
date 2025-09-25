<?php
session_start();
require 'conn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: s_search_coach.php");
    exit();
}

$coach_id = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;
$username = $_SESSION['username'];

$orig_real_name = isset($_POST['real_name']) ? trim($_POST['real_name']) : '';
$orig_gender = isset($_POST['gender']) ? $_POST['gender'] : '';
$orig_age = isset($_POST['age']) ? $_POST['age'] : '';
$orig_browse = isset($_POST['browse']) && $_POST['browse'] ? '1' : '';

function redirect_back($coach_id, $msg = '', $real_name = '', $gender = '', $age = '', $browse = '') {
    $params = [];
    if ($real_name !== '') $params['real_name'] = $real_name;
    if ($gender !== '') $params['gender'] = $gender;
    if ($age !== '') $params['age'] = $age;
    if ($browse === '1') $params['browse'] = '1';
    if ($msg !== '') $params['msg'] = $msg;

    $url = 's_search_coach.php';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    $url .= '#coach_' . intval($coach_id);
    header("Location: $url");
    exit();
}

// 学生 ID
$stmt = $conn->prepare("SELECT id FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id);
if (!$stmt->fetch()) {
    $stmt->close();
    redirect_back($coach_id, "未找到该学生账号。", $orig_real_name, $orig_gender, $orig_age, $orig_browse);
}
$stmt->close();

// 检查教练是否存在
$stmt = $conn->prepare("SELECT id FROM coaches WHERE id=?");
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    redirect_back($coach_id, "教练不存在。", $orig_real_name, $orig_gender, $orig_age, $orig_browse);
}
$stmt->close();

// 学生已选教练数量
$stmt = $conn->prepare("SELECT COUNT(*) FROM student_coach_relations WHERE student_id=? AND (status='approved' OR status='pending')");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($coach_count);
$stmt->fetch();
$stmt->close();
if ($coach_count >= 2) {
    redirect_back($coach_id, "申请失败：最多选择两位教练。", $orig_real_name, $orig_gender, $orig_age, $orig_browse);
}

// 教练学生数量
$stmt = $conn->prepare("SELECT COUNT(*) FROM student_coach_relations WHERE coach_id=? AND status='approved'");
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$stmt->bind_result($student_count);
$stmt->fetch();
$stmt->close();
if ($student_count >= 20) {
    redirect_back($coach_id, "申请失败：该教练已带满 20 名学生。", $orig_real_name, $orig_gender, $orig_age, $orig_browse);
}

// 是否已申请过
$stmt = $conn->prepare("SELECT status FROM student_coach_relations WHERE student_id=? AND coach_id=?");
$stmt->bind_param("ii", $student_id, $coach_id);
$stmt->execute();
$stmt->bind_result($existing_status);
if ($stmt->fetch()) {
    $stmt->close();
    redirect_back($coach_id, "你已申请过该教练，当前状态: " . $existing_status, $orig_real_name, $orig_gender, $orig_age, $orig_browse);
}
$stmt->close();

// 插入申请
$stmt = $conn->prepare("INSERT INTO student_coach_relations (student_id, coach_id, status, request_date) VALUES (?, ?, 'pending', NOW())");
$stmt->bind_param("ii", $student_id, $coach_id);
if ($stmt->execute()) {
    $stmt->close();
    redirect_back($coach_id, "申请成功，等待教练审核。", $orig_real_name, $orig_gender, $orig_age, $orig_browse);
} else {
    $err = $stmt->error;
    $stmt->close();
    redirect_back($coach_id, "申请失败: " . $err, $orig_real_name, $orig_gender, $orig_age, $orig_browse);
}
