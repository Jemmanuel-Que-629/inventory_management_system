<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
$adminId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$userId = (int)($_POST['id'] ?? 0);
if(!$userId){ echo json_encode(['success'=>false,'message'=>'Missing user id']); exit; }
require_once __DIR__ . '/../../database/db_connection.php';

// Check admin role
$roleStmt = $conn->prepare('SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$roleStmt->bind_param('i', $adminId);
$roleStmt->execute();
$roleRes = $roleStmt->get_result();
$roleRow = $roleRes->fetch_assoc();
$roleStmt->close();
if(!$roleRow || strtolower($roleRow['role_name']) !== 'admin') { echo json_encode(['success'=>false,'message'=>'Admin only']); exit; }

try {
    if ($action === 'edit') {
        $fields = ['f_name','m_name','l_name','email','employee_id','cp_number','extensions','address','status'];
        $data = [];
        foreach($fields as $f){ $data[$f] = isset($_POST[$f]) ? trim($_POST[$f]) : null; }
        if(!$data['f_name'] || !$data['l_name'] || !$data['email']){ echo json_encode(['success'=>false,'message'=>'Required fields missing']); exit; }
        $stmt = $conn->prepare('UPDATE users SET f_name=?, m_name=?, l_name=?, email=?, employee_id=?, cp_number=?, extensions=?, address=?, status=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('sssssssssi', $data['f_name'],$data['m_name'],$data['l_name'],$data['email'],$data['employee_id'],$data['cp_number'],$data['extensions'],$data['address'],$data['status'],$userId);
        if(!$stmt->execute()){ throw new Exception('Update failed'); }
        $stmt->close();
        echo json_encode(['success'=>true,'message'=>'User updated','refresh'=>true]);
    } elseif ($action === 'archive') {
        if (!isset($_POST['confirm'])) { echo json_encode(['success'=>false,'message'=>'Confirmation required']); exit; }
        $stmt = $conn->prepare('UPDATE users SET status="inactive", updated_at=NOW() WHERE id=?');
        $stmt->bind_param('i', $userId);
        if(!$stmt->execute()){ throw new Exception('Archive failed'); }
        $stmt->close();
        echo json_encode(['success'=>true,'message'=>'User archived','refresh'=>true]);
    } elseif ($action === 'status') {
        $status = $_POST['status'] ?? '';
        if(!in_array($status,['active','inactive','suspended'])){ echo json_encode(['success'=>false,'message'=>'Invalid status']); exit; }
        $stmt = $conn->prepare('UPDATE users SET status=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('si', $status, $userId);
        if(!$stmt->execute()){ throw new Exception('Status update failed'); }
        $stmt->close();
        echo json_encode(['success'=>true,'message'=>'Status updated','refresh'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
    }
} catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
$conn->close();
