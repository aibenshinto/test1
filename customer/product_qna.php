<?php
session_name('CUSTOMERSESSID');
session_start();
include '../db_connect.php';

if (!isset($_GET['id'])) {
    echo "Product not found.";
    exit;
}

$product_id = intval($_GET['id']);
$isLoggedInCustomer = isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer';

// Handle question submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['question']) && $isLoggedInCustomer) {
    $question = trim($_POST['question']);
    if (!empty($question)) {
        $stmt = $conn->prepare("INSERT INTO product_questions (product_id, customer_id, question) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $product_id, $_SESSION['user_id'], $question);
        $stmt->execute();
    }
}

// Get product info
$stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// Get Q&A
$qstmt = $conn->prepare("SELECT pq.*, u.username AS staff_name FROM product_questions pq LEFT JOIN users u ON pq.staff_id = u.id WHERE pq.product_id = ? ORDER BY pq.created_at DESC");
$qstmt->bind_param("i", $product_id);
$qstmt->execute();
$qresult = $qstmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Q&A for <?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f4; }
        .container { max-width: 700px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; }
        .qa-box { padding: 10px; border-bottom: 1px solid #ccc; }
        .qa-box p { margin: 5px 0; }
        .ask-box textarea { width: 100%; padding: 10px; margin: 10px 0; }
        .ask-box button { padding: 10px 16px; background: #2d89e6; color: #fff; border: none; border-radius: 6px; }
        .back-link { display: inline-block; margin-top: 20px; color: #333; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h2>Q&A - <?php echo htmlspecialchars($product['name']); ?></h2>

    <?php if ($isLoggedInCustomer): ?>
        <form method="post" class="ask-box">
            <textarea name="question" rows="3" placeholder="Ask your question..." required></textarea>
            <button type="submit">Submit</button>
        </form>
    <?php else: ?>
        <p><em>Please log in as a customer to ask questions.</em></p>
    <?php endif; ?>

    <hr>

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

    <a href="product_details.php?id=<?php echo $product_id; ?>" class="back-link">← Back to Product</a>
</div>

</body>
</html>
