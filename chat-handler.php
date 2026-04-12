<?php
session_start();
include('includes/dbconn.php');
include('includes/check-login.php');
check_login();
header('Content-Type: application/json; charset=utf-8');

function ensureMessagesTable($mysqli)
{
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_role ENUM('student','admin') NOT NULL,
        sender_id INT NOT NULL,
        receiver_role ENUM('student','admin') NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $mysqli->query($sql);

    $columnCheck = $mysqli->query("SHOW COLUMNS FROM messages LIKE 'is_read'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $mysqli->query("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message");
    }
}

function jsonResponse($data)
{
    echo json_encode($data);
    exit;
}

ensureMessagesTable($mysqli);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$senderRole = isset($_POST['sender_role']) ? $_POST['sender_role'] : (isset($_GET['sender_role']) ? $_GET['sender_role'] : 'student');
$senderRole = $senderRole === 'admin' ? 'admin' : 'student';
$senderId = $senderRole === 'admin' ? 0 : (int) $_SESSION['id'];

if ($action === 'get_students') {
    $result = $mysqli->query(
        "SELECT id, CONCAT(COALESCE(firstName,''), ' ', COALESCE(middleName,''), ' ', COALESCE(lastName,'')) AS name, email, (SELECT message FROM messages WHERE (sender_role='student' AND sender_id=userregistration.id AND receiver_role='admin' AND receiver_id=0) OR (sender_role='admin' AND sender_id=0 AND receiver_role='student' AND receiver_id=userregistration.id) ORDER BY created_at DESC LIMIT 1) AS last_message, (SELECT COUNT(*) FROM messages WHERE sender_role='student' AND sender_id=userregistration.id AND receiver_role='admin' AND receiver_id=0 AND is_read=0) AS unread_count FROM userregistration WHERE id IN (SELECT DISTINCT CASE WHEN sender_role='student' THEN sender_id WHEN receiver_role='student' THEN receiver_id END FROM messages WHERE (sender_role='student' AND receiver_role='admin') OR (sender_role='admin' AND receiver_role='student')) ORDER BY firstName, lastName"
    );

    $students = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    jsonResponse(['success' => true, 'students' => $students]);
}

if ($action === 'get_unread_count') {
    if ($senderRole === 'admin') {
        $result = $mysqli->query("SELECT COUNT(*) AS unread FROM messages WHERE receiver_role='admin' AND receiver_id=0 AND is_read=0");
        $row = $result->fetch_assoc();
        jsonResponse(['success' => true, 'unread' => (int) $row['unread']]);
    }

    $studentId = (int) $_SESSION['id'];
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM messages WHERE receiver_role='student' AND receiver_id=? AND is_read=0");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $stmt->bind_result($unread);
    $stmt->fetch();
    $stmt->close();
    jsonResponse(['success' => true, 'unread' => (int) $unread]);
}

if ($action === 'load_messages') {
    $conversationWith = isset($_POST['conversation_with']) ? (int) $_POST['conversation_with'] : 0;
    $conversationWith = max(0, $conversationWith);

    if ($senderRole === 'admin' && $conversationWith <= 0) {
        jsonResponse(['success' => false, 'message' => 'Please select a student to load messages.']);
    }

    $studentId = $senderRole === 'admin' ? $conversationWith : (int) $_SESSION['id'];
    $adminId = 0;

    if ($senderRole === 'student') {
        $update = $mysqli->prepare("UPDATE messages SET is_read=1 WHERE receiver_role='student' AND receiver_id=? AND is_read=0");
        $update->bind_param('i', $studentId);
        $update->execute();
        $update->close();
    } else {
        $update = $mysqli->prepare("UPDATE messages SET is_read=1 WHERE sender_role='student' AND sender_id=? AND receiver_role='admin' AND receiver_id=0 AND is_read=0");
        $update->bind_param('i', $studentId);
        $update->execute();
        $update->close();
    }

    $stmt = $mysqli->prepare(
        "SELECT sender_role, message, created_at FROM messages WHERE (sender_role='student' AND sender_id=? AND receiver_role='admin' AND receiver_id=0) OR (sender_role='admin' AND sender_id=0 AND receiver_role='student' AND receiver_id=?) ORDER BY created_at ASC"
    );
    $stmt->bind_param('ii', $studentId, $studentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = [];
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    jsonResponse(['success' => true, 'messages' => $messages]);
}

if ($action === 'send_message') {
    $message = trim($_POST['message'] ?? '');
    $recipientRole = isset($_POST['recipient_role']) ? $_POST['recipient_role'] : 'admin';
    $recipientRole = $recipientRole === 'student' ? 'student' : 'admin';
    $recipientId = isset($_POST['recipient_id']) ? (int) $_POST['recipient_id'] : 0;

    if ($message === '') {
        jsonResponse(['success' => false, 'message' => 'Message cannot be empty.']);
    }

    if ($recipientRole === 'student' && $recipientId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Please select a student recipient.']);
    }

    if ($recipientRole === 'admin') {
        $recipientId = 0;
    }

    $stmt = $mysqli->prepare("INSERT INTO messages (sender_role, sender_id, receiver_role, receiver_id, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sisss', $senderRole, $senderId, $recipientRole, $recipientId, $message);
    $success = $stmt->execute();
    $stmt->close();

    jsonResponse(['success' => $success, 'message' => $success ? 'Message sent.' : 'Unable to send message.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.']);
