<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Handle message actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $message_id = $_POST['message_id'] ?? 0;
    
    if ($action === 'mark_read' && $message_id) {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        if ($stmt->execute([$message_id])) {
            $success = 'Message marked as read';
        }
    } elseif ($action === 'mark_replied' && $message_id) {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'replied' WHERE id = ?");
        if ($stmt->execute([$message_id])) {
            $success = 'Message marked as replied';
        }
    } elseif ($action === 'delete' && $message_id) {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        if ($stmt->execute([$message_id])) {
            $success = 'Message deleted successfully';
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $filter;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("SELECT * FROM contact_messages $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">

</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-envelope"></i> Messages</h1>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Filter by Status</label>
                                <select class="form-select" name="filter">
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Messages</option>
                                    <option value="new" <?php echo $filter === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Read</option>
                                    <option value="replied" <?php echo $filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by name, email, subject, or message...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Messages -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($messages)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $message): ?>
                                        <tr class="<?php echo $message['status'] === 'new' ? 'table-warning' : ''; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                                <?php if ($message['status'] === 'new'): ?>
                                                    <span class="badge bg-primary ms-2">New</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($message['email']); ?></td>
                                            <td>
                                                <a href="#" onclick="showMessage(<?php echo $message['id']; ?>)" 
                                                   class="text-decoration-none">
                                                    <?php echo htmlspecialchars($message['subject']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $message['status'] === 'new' ? 'warning' : 
                                                        ($message['status'] === 'replied' ? 'success' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($message['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($message['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="showMessage(<?php echo $message['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($message['status'] === 'new'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="mark_read">
                                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-success" title="Mark as Read">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($message['status'] !== 'replied'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="mark_replied">
                                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-info" title="Mark as Replied">
                                                                <i class="fas fa-reply"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this message?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-envelope-open fa-4x text-muted mb-3"></i>
                                <h5>No Messages Found</h5>
                                <p class="text-muted">
                                    <?php if ($filter !== 'all' || $search): ?>
                                        No messages match your current filters.
                                        <br><a href="messages.php" class="btn btn-outline-primary mt-2">View All Messages</a>
                                    <?php else: ?>
                                        No contact messages have been received yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="messageContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showMessage(messageId) {
            fetch('ajax/get_message.php?id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('messageContent').innerHTML = `
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>From:</strong> ${data.message.name}<br>
                                    <strong>Email:</strong> ${data.message.email}
                                </div>
                                <div class="col-md-6">
                                    <strong>Date:</strong> ${data.message.created_at}<br>
                                    <strong>Status:</strong> <span class="badge bg-secondary">${data.message.status}</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <strong>Subject:</strong> ${data.message.subject}
                            </div>
                            <div class="mb-3">
                                <strong>Message:</strong>
                                <div class="border p-3 mt-2 bg-light">
                                    ${data.message.message.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        `;
                        new bootstrap.Modal(document.getElementById('messageModal')).show();
                    } else {
                        alert('Failed to load message details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading the message');
                });
        }
    </script>
</body>
</html>