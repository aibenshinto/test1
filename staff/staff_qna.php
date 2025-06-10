<?php
session_name('ADMINSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo "Access denied.";
    exit;
}

$staff_id = $_SESSION['user_id'];

// Handle answer submission via normal POST (not AJAX)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['answer']) && isset($_POST['question_id'])) {
    $answer = trim($_POST['answer']);
    $qid = intval($_POST['question_id']);
    $msg = '';
    $msg_class = '';
    if ($answer === "") {
        $msg = "Answer cannot be empty.";
        $msg_class = 'msg-error';
    } else {
        $check = $conn->prepare("SELECT * FROM product_questions WHERE id = ? AND answer IS NULL");
        $check->bind_param("i", $qid);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            $update = $conn->prepare("UPDATE product_questions SET answer = ?, staff_id = ?, answered_at = NOW() WHERE id = ?");
            $update->bind_param("sii", $answer, $staff_id, $qid);
            if ($update->execute()) {
                $msg = "Answer submitted successfully.";
                $msg_class = 'msg-success';
            } else {
                $msg = "Failed to save answer: " . $conn->error;
                $msg_class = 'msg-error';
            }
        } else {
            $msg = "Question not found or already answered.";
            $msg_class = 'msg-error';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Q&A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: #333;
            min-height: 100vh;
        }
        .dashboard {
            display: flex;
            width: 100vw;
            min-height: 100vh;
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
        .qna-container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
        }
        .question-box {
            background: #f9f9f9;
            border-left: 5px solid #2d89e6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .question-box h4 {
            margin: 0 0 10px;
            color: #2d89e6;
        }
        .question-box p {
            margin: 0 0 10px;
        }
        .question-box textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            resize: vertical;
            margin-bottom: 10px;
            font-family: inherit;
        }
        .question-box button {
            background-color: #2d89e6;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
        }
        .question-box button:hover {
            background-color: #1c6dd0;
        }
        .no-questions {
            text-align: center;
            font-size: 18px;
            color: gray;
            margin-top: 40px;
        }
        .msg {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 6px;
            font-size: 16px;
        }
        .msg-success {
            background: #e6f7ff;
            color: #2d89e6;
            border: 1px solid #2d89e6;
        }
        .msg-error {
            background: #ffe6e6;
            color: #d0021b;
            border: 1px solid #d0021b;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
      <h2>Staff Panel</h2>
      <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?></p>
      <ul>
        <li><a href="add_product.php">Add Product</a></li>
        <li><a href="view_orders.php">Manage Orders</a></li>
        <li><a href="staff_qna.php">Q&A</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>
    <main class="main-content">
      <?php if (isset($msg) && $msg): ?>
        <div class="msg <?= $msg_class ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php renderUnansweredQuestions($conn); ?>
    </main>
</div>
</body>
</html>
<?php
// Function to render unanswered questions
function renderUnansweredQuestions($conn) {
    $stmt = $conn->prepare("SELECT pq.*, p.name AS product_name FROM product_questions pq JOIN products p ON pq.product_id = p.id WHERE pq.answer IS NULL ORDER BY pq.created_at ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    ?>
    <div class="qna-container">
        <h2>Unanswered Product Questions</h2>
        <?php if ($result->num_rows === 0): ?>
            <div class="no-questions">🎉 All questions are answered!</div>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="question-box">
                <h4>Product: <?= htmlspecialchars($row['product_name']) ?></h4>
                <p><strong>Question:</strong> <?= htmlspecialchars($row['question']) ?></p>
                <form class="answer-form" method="post" action="staff_qna.php" data-question-id="<?= $row['id'] ?>">
                    <textarea name="answer" rows="3" placeholder="Write your answer here..." required></textarea>
                    <input type="hidden" name="question_id" value="<?= $row['id'] ?>">
                    <button type="submit">Submit Answer</button>
                </form>
            </div>
        <?php endwhile; ?>
    </div>
<?php
}
?>
