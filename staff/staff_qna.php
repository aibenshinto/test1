<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Require product manager role to access this page
requireProductManager();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$message = '';

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id']) && isset($_POST['answer'])) {
    $question_id = intval($_POST['question_id']);
    $answer = trim($_POST['answer']);
    $staff_id = getCurrentUserId();
    
    if (!empty($answer)) {
        $stmt = $conn->prepare("UPDATE product_questions SET answer = ?, staff_id = ?, answered_at = NOW() WHERE id = ?");
        $stmt->bind_param("sii", $answer, $staff_id, $question_id);
        
        if ($stmt->execute()) {
            $message = "Answer submitted successfully!";
        } else {
            $message = "Error submitting answer.";
        }
    } else {
        $message = "Please provide an answer.";
    }
}

// Fetch all questions with item and customer details
$sql = "SELECT pq.*, i.Item_name as item_name, c.name as customer_name, c.email as customer_email, s.name as staff_name
        FROM product_questions pq
        JOIN tbl_item i ON pq.item_id = i.Item_id
        JOIN customers c ON pq.customer_id = c.id
        LEFT JOIN staff s ON pq.staff_id = s.id
        ORDER BY pq.created_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Q&A Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      height: 100vh;
      background: linear-gradient(135deg, #43cea2, #185a9d);
      color: #333;
    }

    .dashboard {
      display: flex;
      width: 100%;
    }

    .sidebar {
      background: white;
      padding: 20px;
      width: 250px;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .sidebar h2 {
      margin-bottom: 10px;
      color: #185a9d;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar li {
      margin: 10px 0;
    }

    .sidebar button, .sidebar a {
      background: #185a9d;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      cursor: pointer;
      width: 100%;
      text-align: left;
      transition: background 0.3s ease;
      display: block;
    }

    .sidebar button:hover, .sidebar a:hover {
      background: #0b3d72;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      background: rgba(255,255,255,0.95);
      overflow-y: auto;
    }

    .logout-link {
      margin-top: 20px;
      display: block;
      color: red;
    }

    .role-badge {
      background: #e8f4fd;
      color: #185a9d;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
      margin-left: 10px;
    }

    .section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .section h2 {
      color: #2d89e6;
      margin-bottom: 15px;
    }

    .question-card {
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      background: #f9f9f9;
    }

    .question-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .question-header h3 {
      margin: 0;
      color: #185a9d;
    }

    .question-meta {
      font-size: 14px;
      color: #666;
      margin-bottom: 10px;
    }

    .question-text {
      background: white;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 15px;
      border-left: 4px solid #2d89e6;
    }

    .answer-section {
      background: #e8f4fd;
      padding: 15px;
      border-radius: 6px;
      margin-top: 15px;
    }

    .answer-form {
      margin-top: 15px;
    }

    .answer-form textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      resize: vertical;
      min-height: 80px;
      font-family: inherit;
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin: 2px;
    }

    .btn-primary { background: #2d89e6; color: white; }
    .btn-success { background: #27ae60; color: white; }

    .message {
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }

    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .status-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-answered {
      background: #d4edda;
      color: #155724;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Product Manager Panel</h2>
      <p>Hello, <?= htmlspecialchars(getCurrentUsername()) ?> <span class="role-badge">Product Manager</span></p>
      <ul>
        <li><a href="staff_dashboard.php">Staff Dashboard</a></li>
        <li><a href="staff_products.php">Manage Products</a></li>
        <li><a href="add_product.php">Add Product</a></li>
        <li><a href="staff_qna.php">Customer Q&A</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>Customer Q&A Management</h2>
      <p>Answer customer questions about products.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="section">
        <h3>All Questions</h3>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($question = $result->fetch_assoc()): ?>
            <div class="question-card">
              <div class="question-header">
                <h3>Question #<?php echo $question['id']; ?></h3>
                <span class="status-badge <?php echo $question['answer'] ? 'status-answered' : 'status-pending'; ?>">
                  <?php echo $question['answer'] ? 'Answered' : 'Pending'; ?>
                </span>
              </div>
              
              <div class="question-meta">
                <strong>Item:</strong> <?php echo htmlspecialchars($question['item_name']); ?> |
                <strong>Customer:</strong> <?php echo htmlspecialchars($question['customer_name']); ?> 
                (<?php echo htmlspecialchars($question['customer_email']); ?>) |
                <strong>Asked:</strong> <?php echo date('M d, Y H:i', strtotime($question['created_at'])); ?>
              </div>
              
              <div class="question-text">
                <strong>Question:</strong><br>
                <?php echo nl2br(htmlspecialchars($question['question'])); ?>
              </div>
              
              <?php if ($question['answer']): ?>
                <div class="answer-section">
                  <strong>Answer:</strong><br>
                  <?php echo nl2br(htmlspecialchars($question['answer'])); ?>
                  <div style="margin-top: 10px; font-size: 14px; color: #666;">
                    <strong>Answered by:</strong> <?php echo htmlspecialchars($question['staff_name']); ?> |
                    <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($question['answered_at'])); ?>
                  </div>
                </div>
              <?php else: ?>
                <form method="post" class="answer-form">
                  <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                  <label for="answer_<?php echo $question['id']; ?>"><strong>Your Answer:</strong></label><br>
                  <textarea name="answer" id="answer_<?php echo $question['id']; ?>" placeholder="Type your answer here..." required></textarea><br>
                  <button type="submit" class="btn btn-primary">Submit Answer</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No questions found.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
