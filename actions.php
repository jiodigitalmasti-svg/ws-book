<?php
// actions.php - Handles post creation, likes, comments, and post deletion
require_once __DIR__ . '/db.php';
requireLogin();

$user = getCurrentUser($db);
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$returnUrl = $_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Sanitize return url to local pages only
if (!in_array(parse_url($returnUrl, PHP_URL_PATH), ['index.php', 'profile.php', '/index.php', '/profile.php'])) {
    $returnUrl = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CREATE POST
    if ($action === 'create_post') {
        $content = trim($_POST['content'] ?? '');
        if (!empty($content)) {
            $stmt = $db->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
            $stmt->execute([$user['id'], $content]);
        }
        header("Location: " . $returnUrl);
        exit;
    }

    // 2. TOGGLE LIKE
    if ($action === 'toggle_like') {
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId > 0) {
            // Check if already liked
            $check = $db->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
            $check->execute([$postId, $user['id']]);
            $existing = $check->fetch();

            if ($existing) {
                $del = $db->prepare("DELETE FROM likes WHERE id = ?");
                $del->execute([$existing['id']]);
            } else {
                $ins = $db->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
                $ins->execute([$postId, $user['id']]);
            }
        }
        header("Location: " . $returnUrl . "#post-" . $postId);
        exit;
    }

    // 3. ADD COMMENT
    if ($action === 'add_comment') {
        $postId = (int)($_POST['post_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($postId > 0 && !empty($comment)) {
            $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$postId, $user['id'], $comment]);
        }
        header("Location: " . $returnUrl . "#post-" . $postId);
        exit;
    }

    // 4. DELETE POST
    if ($action === 'delete_post') {
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId > 0) {
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$postId, $user['id']]);
        }
        header("Location: " . $returnUrl);
        exit;
    }

    // 5. UPDATE BIO
    if ($action === 'update_bio') {
        $bio = trim($_POST['bio'] ?? '');
        $stmt = $db->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt->execute([$bio, $user['id']]);
        header("Location: profile.php");
        exit;
    }
}

header("Location: index.php");
exit;
