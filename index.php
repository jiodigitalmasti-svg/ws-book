<?php
// index.php - Main Facebook Feed
require_once __DIR__ . '/db.php';
requireLogin();

$currentUser = getCurrentUser($db);

// Fetch all posts with author information and like/comment counts
$query = "
    SELECT p.*, 
           u.username, u.full_name, u.avatar_color,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = :current_user_id) as user_liked,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute(['current_user_id' => $currentUser['id']]);
$posts = $stmt->fetchAll();

// Prepare comments helper
$commentStmt = $db->prepare("
    SELECT c.*, u.username, u.full_name, u.avatar_color 
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facebook</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- Top Navigation Bar -->
  <header class="navbar">
    <a href="index.php" class="brand">
      <div class="brand-icon">f</div>
      <span>facebook</span>
    </a>
    
    <div class="nav-links">
      <a href="profile.php" class="nav-user" title="View your profile">
        <div class="avatar-circle" style="background-color: <?= htmlspecialchars($currentUser['avatar_color']) ?>; width: 32px; height: 32px; font-size: 14px;">
          <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
        </div>
        <span><?= htmlspecialchars(explode(' ', $currentUser['full_name'])[0]) ?></span>
      </a>
      <a href="logout.php" class="btn-logout" title="Log Out">Log Out</a>
    </div>
  </header>

  <!-- Feed Main Container -->
  <main class="container">
    
    <!-- Create Post Card -->
    <div class="card create-post">
      <form action="actions.php" method="POST">
        <input type="hidden" name="action" value="create_post">
        <input type="hidden" name="return_url" value="index.php">
        
        <div class="create-post-header">
          <div class="avatar-circle" style="background-color: <?= htmlspecialchars($currentUser['avatar_color']) ?>;">
            <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
          </div>
          <textarea 
            name="content" 
            placeholder="What's on your mind, <?= htmlspecialchars(explode(' ', $currentUser['full_name'])[0]) ?>?" 
            required
            rows="3"
          ></textarea>
        </div>

        <div class="create-post-actions">
          <span style="font-size: 13px; color: var(--fb-subtext);">Share an update or idea</span>
          <button type="submit" class="btn-primary">Post</button>
        </div>
      </form>
    </div>

    <!-- Posts Feed Stream -->
    <?php if (empty($posts)): ?>
      <div class="card" style="text-align: center; padding: 40px 20px; color: var(--fb-subtext);">
        <p style="font-size: 18px; font-weight: 600; margin-bottom: 6px;">No posts in your feed yet.</p>
        <p style="font-size: 14px;">Be the first one to share an update!</p>
      </div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <?php 
          $commentStmt->execute([$post['id']]);
          $comments = $commentStmt->fetchAll();
          $timeAgo = date('M j, Y \a\t g:i A', strtotime($post['created_at']));
        ?>
        <article class="card" id="post-<?= $post['id'] ?>">
          <!-- Post Author Header -->
          <div class="post-header">
            <a href="profile.php?user=<?= urlencode($post['username']) ?>" class="post-author">
              <div class="avatar-circle" style="background-color: <?= htmlspecialchars($post['avatar_color']) ?>;">
                <?= strtoupper(substr($post['full_name'], 0, 1)) ?>
              </div>
              <div>
                <div class="author-name"><?= htmlspecialchars($post['full_name']) ?></div>
                <div class="post-time">@<?= htmlspecialchars($post['username']) ?> · <?= htmlspecialchars($timeAgo) ?></div>
              </div>
            </a>

            <?php if ($post['user_id'] == $currentUser['id']): ?>
              <form action="actions.php" method="POST" onsubmit="return confirm('Delete this post?');" style="margin: 0;">
                <input type="hidden" name="action" value="delete_post">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <input type="hidden" name="return_url" value="index.php">
                <button type="submit" style="background: none; border: none; color: #888; cursor: pointer; padding: 6px 10px; font-size: 13px; border-radius: 4px;" title="Delete post">✕ Delete</button>
              </form>
            <?php endif; ?>
          </div>

          <!-- Post Content -->
          <div class="post-content"><?= htmlspecialchars($post['content']) ?></div>

          <!-- Post Counts / Stats -->
          <div class="post-stats">
            <div>
              <span style="display: inline-flex; align-items: center; gap: 4px;">
                <span style="background: var(--fb-blue); color: #fff; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">👍</span>
                <span><?= (int)$post['likes_count'] ?></span>
              </span>
            </div>
            <div>
              <span><?= (int)$post['comments_count'] ?> comments</span>
            </div>
          </div>

          <!-- Post Action Buttons (Like, Comment) -->
          <div class="post-bar">
            <form action="actions.php" method="POST" style="flex: 1;">
              <input type="hidden" name="action" value="toggle_like">
              <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
              <input type="hidden" name="return_url" value="index.php">
              <button type="submit" class="btn-action <?= $post['user_liked'] ? 'liked' : '' ?>" style="width: 100%;">
                <span>👍</span>
                <span><?= $post['user_liked'] ? 'Liked' : 'Like' ?></span>
              </button>
            </form>

            <button type="button" class="btn-action" onclick="document.getElementById('comment-input-<?= $post['id'] ?>').focus();">
              <span>💬</span>
              <span>Comment</span>
            </button>
          </div>

          <!-- Comments Section -->
          <div class="comments-section">
            <!-- Add Comment Form -->
            <form action="actions.php" method="POST" class="comment-form">
              <input type="hidden" name="action" value="add_comment">
              <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
              <input type="hidden" name="return_url" value="index.php">
              <div class="avatar-circle" style="background-color: <?= htmlspecialchars($currentUser['avatar_color']) ?>; width: 32px; height: 32px; font-size: 13px;">
                <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
              </div>
              <input 
                type="text" 
                id="comment-input-<?= $post['id'] ?>"
                name="comment" 
                class="comment-input" 
                placeholder="Write a comment..." 
                required 
                autocomplete="off"
              >
              <button type="submit" class="btn-primary" style="padding: 6px 14px; font-size: 14px; min-height: 36px; border-radius: 18px;">Send</button>
            </form>

            <!-- Comments List -->
            <?php if (!empty($comments)): ?>
              <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                  <div class="comment-item">
                    <div class="avatar-circle" style="background-color: <?= htmlspecialchars($comment['avatar_color']) ?>; width: 30px; height: 30px; font-size: 12px;">
                      <?= strtoupper(substr($comment['full_name'], 0, 1)) ?>
                    </div>
                    <div class="comment-bubble">
                      <a href="profile.php?user=<?= urlencode($comment['username']) ?>" class="comment-user">
                        <?= htmlspecialchars($comment['full_name']) ?>
                      </a>
                      <div class="comment-text"><?= htmlspecialchars($comment['content']) ?></div>
                      <div class="comment-time"><?= date('M j, g:i a', strtotime($comment['created_at'])) ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>

  </main>

</body>
</html>
