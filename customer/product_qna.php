<?php
require_once '../session_manager.php';
include '../db_connect.php';

if (!isset($_GET['id'])) {
    echo "Item not found.";
    exit;
}

$item_id = $_GET['id'];
$isLoggedInCustomer = isCustomer();

// Handle question submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['question']) && $isLoggedInCustomer) {
    $question = trim($_POST['question']);
    if (!empty($question)) {
        $customer_id = getCurrentUserId();
        $stmt = $conn->prepare("INSERT INTO product_questions (item_id, customer_id, question) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $item_id, $customer_id, $question);
        
        if ($stmt->execute()) {
            $success_message = "Your question has been submitted successfully!";
        } else {
            $error_message = "Failed to submit question. Please try again.";
        }
    } else {
        $error_message = "Please enter a question.";
    }
}

// Get item info
$stmt = $conn->prepare("SELECT Item_name FROM tbl_item WHERE Item_id = ?");
$stmt->bind_param("s", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    echo "Item not found.";
    exit;
}

// Get Q&A
$qstmt = $conn->prepare("SELECT pq.*, s.name AS staff_name FROM product_questions pq LEFT JOIN staff s ON pq.staff_id = s.id WHERE pq.item_id = ? ORDER BY pq.created_at DESC");
$qstmt->bind_param("s", $item_id);
$qstmt->execute();
$qresult = $qstmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Q&A for <?php echo htmlspecialchars($item['Item_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f4; }
        .container { max-width: 700px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; }
        .qa-box { padding: 10px; border-bottom: 1px solid #ccc; }
        .qa-box p { margin: 5px 0; }
        .ask-box textarea { width: 100%; padding: 10px; margin: 10px 0; }
        .ask-box button { padding: 10px 16px; background: #2d89e6; color: #fff; border: none; border-radius: 6px; }
        .back-link { display: inline-block; margin-top: 20px; color: #333; text-decoration: none; }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="container">
    <h2>Q&A - <?php echo htmlspecialchars($item['Item_name']); ?></h2>

    <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($isLoggedInCustomer): ?>
        <form method="post" class="ask-box">
            <textarea name="question" rows="3" placeholder="Ask your question..." required></textarea>
            <button type="submit">Submit</button>
        </form>
    <?php else: ?>
        <p><em>Please log in as a customer to ask questions.</em></p>
    <?php endif; ?>

    <hr>

    <?php if ($qresult->num_rows > 0): ?>
        <?php while ($qa = $qresult->fetch_assoc()): ?>
            <div class="qa-box">
                <p><strong>Q:</strong> <?php echo htmlspecialchars($qa['question']); ?></p>
                <?php if ($qa['answer']): ?>
                    <p><strong>A (<?php echo htmlspecialchars($qa['staff_name']); ?>):</strong> <?php echo htmlspecialchars($qa['answer']); ?></p>
                <?php else: ?>
                    <p><em>Waiting for staff reply...</em></p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p><em>No questions yet. Be the first to ask!</em></p>
    <?php endif; ?>

    <a href="product_details.php?id=<?php echo $item_id; ?>" class="back-link">← Back to Item</a>
</div>

</body>
</html>
