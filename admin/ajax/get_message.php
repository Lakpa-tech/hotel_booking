<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$message_id = $_GET['id'] ?? 0;

if (!$message_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
$stmt->execute([$message_id]);
$message = $stmt->fetch();

if ($message) {
    // Mark as read if it's new
    if ($message['status'] === 'new') {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$message_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => [
            'name' => htmlspecialchars($message['name']),
            'email' => htmlspecialchars($message['email']),
            'subject' => htmlspecialchars($message['subject']),
            'message' => htmlspecialchars($message['message']),
            'status' => ucfirst($message['status']),
            'created_at' => formatDate($message['created_at'])
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Message not found']);
}