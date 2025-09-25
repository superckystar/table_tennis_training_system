<?php
require 'conn.php';  // 包含数据库连接

// 根据请求类型返回教练员数据
$type = $_POST['type'] ?? 'search';  // 默认为按条件查询

if ($type == 'all') {
    // 查询所有教练员
    $stmt = $conn->prepare("SELECT id, real_name, gender, age, campus, photo, achievements FROM coaches WHERE status = 'approved'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $coaches = [];
    while ($coach = $result->fetch_assoc()) {
        $coaches[] = $coach;
    }
    echo json_encode($coaches);
} else {
    // 按条件查询教练员
    $coach_name = $_POST['coach_name'] ?? '';
    $coach_gender = $_POST['coach_gender'] ?? '';
    $coach_age = $_POST['coach_age'] ?? '';

    $sql = "SELECT id, real_name, gender, age, campus, photo, achievements FROM coaches WHERE status = 'approved'";

    if ($coach_name) {
        $sql .= " AND real_name LIKE ?";
    }
    if
