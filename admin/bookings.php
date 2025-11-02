<?php
// bookings.php
require_once __DIR__ . '/includes/auth.php'; // must set $conn = new mysqli(...)

$currentPage = 'bookings';
$pageTitle   = 'Bookings';

/* ---------------- Controller: read filters + fetch data ---------------- */
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function badge($status){
    $map = [
        'pending'  => 'badge badge-pending',
        'approved' => 'badge badge-approved',
        'rejected' => 'badge badge-rejected',
        'returned' => 'badge badge-returned',
    ];
    $cls = $map[$status] ?? 'badge';
    return '<span class="'.$cls.'">'.h(ucfirst($status)).'</span>';
}

$search  = trim($_GET['q'] ?? '');
$status  = trim($_GET['status'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 7;
$offset  = ($page - 1) * $perPage;

/* Build WHERE & params */
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    // search in ref_code, user_name, item_name, item_code
    $where[] = "(b.ref_code LIKE ? OR u.name LIKE ? OR i.name LIKE ? OR i.sku LIKE ?)";
    for ($i = 0; $i < 4; $i++) { $types .= 's'; $params[] = "%{$search}%"; }
}
if ($status !== '') {
    $where[] = "b.status = ?";
    $types   .= 's';
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* Count total */
$sqlCount = "
    SELECT COUNT(*) AS c
    FROM bookings b
    LEFT JOIN inventory_items i ON i.item_id = b.item_id
    LEFT JOIN users u ON u.id = b.user_id
    $whereSql
";
$stmt = $conn->prepare($sqlCount);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$total = 0;
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

/* Fetch page */
$sqlList = "
    SELECT
        b.ref_code, b.qty, b.start_at, b.end_at, b.status, b.purpose, b.admin_note,
        i.name AS item_name, i.sku AS item_code,
        u.name AS user_name, u.matrics_number, u.phone_number
    FROM bookings b
    LEFT JOIN inventory_items i ON i.item_id = b.item_id
    LEFT JOIN users u ON u.id = b.user_id
    $whereSql
    ORDER BY b.start_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sqlList);
if ($types !== '') {
    $types2   = $types . 'ii';
    $params2  = $params;
    $params2[] = $perPage;
    $params2[] = $offset;
    $stmt->bind_param($types2, ...$params2);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$rows   = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pages = max(1, (int)ceil($total / $perPage));

/* Helper to keep filters in links */
$buildLink = function(int $p) use ($search, $status){
    return 'bookings.php?' . http_build_query(array_filter(['q'=>$search,'status'=>$status,'page'=>$p]));
};

$showStart = $total > 0 ? ($offset + 1) : 0;
$showEnd   = $total > 0 ? min($offset + count($rows), $total) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Bookings | iReserve</title>
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=6">
    <link rel="stylesheet" href="/ireserve/assets/css/inventory.css?v=2">
    <link rel="stylesheet" href="/ireserve/assets/css/bookings.css?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>
            <div class="details">
                <div class="inventoryTable">
                    <div class="cardHeader">
                        <h3>Bookings</h3>
                    </div>

<div class="filterRow" style="margin-bottom:12px;">
    <form method="get" style="display:flex; gap:10px; flex-wrap:wrap;">
        <input type="text" name="q" placeholder="Search ref, user, item..." value="<?=h($search)?>">
        <select name="status">
            <option value="">All status</option>
            <?php foreach (['pending','approved','rejected','returned'] as $s): ?>
                <option value="<?=$s?>" <?=$status===$s?'selected':''?>><?=ucfirst($s)?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Apply</button>
        <?php if ($search!=='' || $status!==''): ?>
            <a class="btn" style="background:#9aa6ff;text-decoration:none" href="bookings.php">Reset</a>
        <?php endif; ?>
    </form>
    <div style="margin-left:auto; font-size:14px; color:#777;">
        Total: <strong><?=$total?></strong>
    </div>
</div>

<div class="tableWrap">
<table width="100%">
    <thead>
        <tr>
            <td>No.</td>
            <td>Item</td>
            <td>User</td>
            <td>Qty</td>
            <td>Start</td>
            <td>End</td>
            <td>Status</td>
            <td style="text-align:right;">Actions</td>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="8" style="text-align:center; padding:28px; color:#777;">No bookings found.</td></tr>
    <?php else: $rowNum = $offset + 1; foreach ($rows as $r): ?>
        <tr>
            <td><strong><?=$rowNum++?></strong></td>
            <td><?=h($r['item_name'])?> <small style="color:#888;">(<?=h($r['item_code'])?>)</small></td>
            <td><?=h($r['user_name'])?><br><small style="color:#888;"><?=h($r['matrics_number'])?> · <?=h($r['phone_number'])?></small></td>
            <td><?=intval($r['qty'])?></td>
            <td><?=date('d M Y, H:i', strtotime($r['start_at']))?></td>
            <td><?=date('d M Y, H:i', strtotime($r['end_at']))?></td>
            <td><?=badge($r['status'])?></td>
            <td style="text-align:right;">
                <div class="action-btn">
                    <button class="btn-sm btn-view" data-view='<?=h(json_encode($r))?>'>View</button>
                    <?php if ($r['status']==='pending'): ?>
                        <button class="btn-sm btn-approve" data-ref="<?=h($r['ref_code'])?>" data-action="approved">Approve</button>
                        <button class="btn-sm btn-reject"  data-ref="<?=h($r['ref_code'])?>" data-action="rejected">Reject</button>
                    <?php elseif ($r['status']==='approved'): ?>
                        <button class="btn-sm btn-return"  data-ref="<?=h($r['ref_code'])?>" data-action="returned">Mark Returned</button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<div class="pagination">
    <div class="pagination-info">Showing <?=$showStart?>–<?=$showEnd?> of <?=$total?></div>
    <div class="pagination-links">
        <?php if ($pages > 1): ?>
            <?php
                // Prev
                echo ($page > 1)
                    ? '<a class="page-link prev" href="'.$buildLink($page-1).'">&laquo; Prev</a>'
                    : '<span class="page-link disabled">&laquo; Prev</span>';

                $window = 2;
                $first = 1; $last = $pages;
                $startP = max($first+1, $page - $window);
                $endP   = min($last-1,  $page + $window);

                echo $page === $first ? '<span class="page-link active">1</span>'
                                      : '<a class="page-link" href="'.$buildLink(1).'">1</a>';

                if ($startP > $first + 1) echo '<span class="ellipsis">…</span>';

                for ($p = $startP; $p <= $endP; $p++) {
                    if ($p <= $first || $p >= $last) continue;
                    echo $p == $page ? '<span class="page-link active">'.$p.'</span>'
                                     : '<a class="page-link" href="'.$buildLink($p).'">'.$p.'</a>';
                }

                if ($endP < $last - 1) echo '<span class="ellipsis">…</span>';

                if ($last > 1) {
                    echo $page === $last ? '<span class="page-link active">'.$last.'</span>'
                                         : '<a class="page-link" href="'.$buildLink($last).'">'.$last.'</a>';
                }

                echo ($page < $pages)
                    ? '<a class="page-link next" href="'.$buildLink($page+1).'">Next &raquo;</a>'
                    : '<span class="page-link disabled">Next &raquo;</span>';
            ?>
        <?php endif; ?>
    </div>
</div>

            </div><!-- content -->
        </div><!-- inventoryTable -->
    </div><!-- details -->
</div><!-- main -->
</div><!-- container -->

<!-- Modal -->
<div class="modal-backdrop" id="mb"></div>
<div class="modal" id="md">
    <header id="md-title"><h3>Booking Details</h3></header>
    <div class="body" id="md-body"></div>
    <div class="actions"><button id="md-close">Close</button></div>
</div>

<script src="/ireserve/assets/js/admin.js?v=3"></script>
</body>
</html>