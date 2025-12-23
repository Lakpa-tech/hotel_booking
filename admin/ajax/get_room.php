<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$room_id = $_GET['id'] ?? 0;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if ($room) {
    echo json_encode([
        'success' => true,
        'room' => $room
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
}