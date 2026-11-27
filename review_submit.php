<?php
// Save a review (rating + comment) for a delivered order. One review per order.
require_once 'includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('orders.php');
verify_csrf();

$oid     = (int)($_POST['order_id'] ?? 0);
$rating  = (int)($_POST['rating'] ?? 0);
$comment = clean($_POST['comment'] ?? '');
$back    = 'track.php?id=' . $oid . '#review';

$st = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
$st->execute([$oid, $_SESSION['user_id']]);
$order = $st->fetch();

if (!$order)                           { set_flash('error', 'Order not found.'); redirect('orders.php'); }
if ($order['status'] !== 'Delivered')  { set_flash('error', 'You can review an order only after it is delivered.'); redirect($back); }
if ($rating < 1 || $rating > 5)        { set_flash('error', 'Please choose a star rating (1 to 5).'); redirect($back); }
if (mb_strlen($comment) > 500)         { set_flash('error', 'Comment can be 500 characters at most.'); redirect($back); }

$ex = $pdo->prepare("SELECT 1 FROM reviews WHERE order_id = ?");
$ex->execute([$oid]);
if ($ex->fetchColumn()) { set_flash('error', 'You already reviewed this order.'); redirect($back); }

$pdo->prepare("INSERT INTO reviews (order_id, user_id, rating, comment) VALUES (?, ?, ?, ?)")
    ->execute([$oid, $_SESSION['user_id'], $rating, $comment]);
set_flash('success', 'Thank you for your review!');
redirect($back);
