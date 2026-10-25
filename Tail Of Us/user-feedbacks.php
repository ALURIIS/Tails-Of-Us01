<?php
require_once __DIR__ . '/includes/feedback-store.php';
require_once __DIR__ . '/includes/layout.php';

$subjects = feedback_subjects();
$ratings  = feedback_ratings();

/* Only feedback whose author ticked "Show my feedback", newest first */
$all = array_values(array_filter(load_feedback(), function ($f) {
    return !empty($f['public']);
}));
usort($all, function ($a, $b) {
    return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
});

$total   = count($all);
$perPage = 6;
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$items   = array_slice($all, ($page - 1) * $perPage, $perPage);
$counts  = array_count_values(array_column($all, 'rating'));
$sent    = (string) ($_GET['sent'] ?? '');

page_open('User Feedbacks');
?>
<main id="main" tabindex="-1">
<div class="container fb-page">

  <section class="fb-banner" aria-labelledby="uf-title">
    <h1 id="uf-title">User Feedbacks</h1>
    <p>See what other guests think about their stay at Tails of Us.</p>
    <p><a class="btn" href="feedback.html">Leave Feedback</a></p>
  </section>

<?php if ($sent === 'public' || $sent === 'private'): ?>
  <output class="sent-notice">Thank you! Your feedback has been received<?= $sent === 'public' ? ' and is shown below.' : '. As you asked, it will not be shown publicly.' ?></output>
<?php endif; ?>

<?php if ($total > 0): ?>
  <p class="tally"><?= $total ?> guest<?= $total === 1 ? '' : 's' ?> shared feedback: <?= (int) ($counts['very-satisfied'] ?? 0) ?> very satisfied, <?= (int) ($counts['satisfied'] ?? 0) ?> satisfied, <?= (int) ($counts['not-satisfied'] ?? 0) ?> not satisfied.</p>

  <ul class="list-plain review-list">
<?php foreach ($items as $f):
    $f += ['name' => '', 'date' => '', 'subject' => '', 'message' => '', 'rating' => ''];
    $time = strtotime($f['date']); ?>
    <li class="card review">
      <p class="review-meta"><strong><?= h(display_name($f['name'])) ?></strong> · <time datetime="<?= h(substr($f['date'], 0, 10)) ?>"><?= $time ? h(date('j F Y', $time)) : '' ?></time></p>
      <p class="badge badge-<?= h($f['rating']) ?>"><?= h($ratings[$f['rating']] ?? '') ?></p>
      <h2><?= h($subjects[$f['subject']] ?? 'Feedback') ?></h2>
      <p><?= nl2br(h($f['message'])) ?></p>
    </li>
<?php endforeach; ?>
  </ul>

<?php if ($pages > 1): ?>
  <nav class="fb-pager" aria-label="Feedback pages">
<?php if ($page > 1): ?>
    <a class="page-arrow" href="user-feedbacks.php?page=<?= $page - 1 ?>" aria-label="Previous page" title="Previous page">←</a>
<?php else: ?>
    <span class="page-arrow page-arrow-off" aria-hidden="true">←</span>
<?php endif; ?>
    <p class="pager-status">Page <?= $page ?> of <?= $pages ?></p>
<?php if ($page < $pages): ?>
    <a class="page-arrow" href="user-feedbacks.php?page=<?= $page + 1 ?>" aria-label="Next page" title="Next page">→</a>
<?php else: ?>
    <span class="page-arrow page-arrow-off" aria-hidden="true">→</span>
<?php endif; ?>
  </nav>
<?php endif; ?>

<?php else: ?>
  <p class="card empty">No feedback is shown yet. Be the first to <a href="feedback.html">share your experience</a>.</p>
<?php endif; ?>

</div>
</main>

<?php page_close(); ?>
