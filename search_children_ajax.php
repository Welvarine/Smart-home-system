
<?php
include __DIR__ . '/php/db_connect.php';

$search = $_GET['q'] ?? '';

$children = [];
if($search != '') {
    $stmt = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE role='child' AND (full_name LIKE ? OR email LIKE ?) LIMIT 10");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $children[] = ['id'=>$row['user_id'], 'name'=>$row['full_name'], 'email'=>$row['email']];
    }
}

echo json_encode($children);
