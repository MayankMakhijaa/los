<?php
require __DIR__ . '/bootstrap.php'; require_login();
$user = current_user();
$total = (int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
$new = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status='New'")->fetchColumn();
$qualified = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status='Qualified'")->fetchColumn();
$disbursed = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status='Disbursed'")->fetchColumn();
$volume = (float)$pdo->query("SELECT COALESCE(SUM(requested_amount),0) FROM leads WHERE status IN ('Sanctioned','Disbursed')")->fetchColumn();
$recent = $pdo->query("SELECT l.*,u.name assigned_name FROM leads l LEFT JOIN users u ON u.id=l.assigned_to ORDER BY l.created_at DESC LIMIT 8")->fetchAll();
$tasks = $pdo->query("SELECT t.*,l.full_name FROM tasks t LEFT JOIN leads l ON l.id=t.lead_id WHERE t.status='Open' ORDER BY t.due_date IS NULL,t.due_date LIMIT 6")->fetchAll();
$counts = $pdo->query("SELECT status,COUNT(*) total FROM leads GROUP BY status")->fetchAll();
$flash = get_flash();
require __DIR__ . '/partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">OVERVIEW</p><h1>Good <?=date('H')<12?'morning':(date('H')<17?'afternoon':'evening')?>, <?=h($user['name'])?></h1><p class="muted">Here's what's happening across your loan pipeline.</p></div><a class="btn primary" href="lead-form.php">＋ Add new lead</a></div>
<?php if($flash):?><div class="alert <?=h($flash['type'])?>"><?=h($flash['message'])?></div><?php endif;?>
<div class="stat-grid">
<div class="stat-card"><div class="stat-top"><span>Total leads</span><span class="stat-icon blue">↗</span></div><strong><?=number_format($total)?></strong><small>All captured enquiries</small></div>
<div class="stat-card"><div class="stat-top"><span>New leads</span><span class="stat-icon amber">✦</span></div><strong><?=number_format($new)?></strong><small>Awaiting first contact</small></div>
<div class="stat-card"><div class="stat-top"><span>Qualified</span><span class="stat-icon green">✓</span></div><strong><?=number_format($qualified)?></strong><small>Ready for next steps</small></div>
<div class="stat-card"><div class="stat-top"><span>Disbursed</span><span class="stat-icon violet">₹</span></div><strong><?=number_format($disbursed)?></strong><small>Marked as disbursed</small></div>
</div>
<div class="content-grid">
<section class="panel"><div class="panel-heading"><div><h2>Recent leads</h2><p class="muted">Latest customer enquiries</p></div><a class="text-link" href="leads.php">View all →</a></div>
<div class="table-wrap"><table><thead><tr><th>Customer</th><th>Loan type</th><th>Amount</th><th>Status</th><th>Owner</th></tr></thead><tbody>
<?php if(!$recent):?><tr><td colspan="5" class="empty">No leads yet. Add your first lead to get started.</td></tr><?php endif;?>
<?php foreach($recent as $l):?><tr><td><a class="customer-link" href="lead-view.php?id=<?=(int)$l['id']?>"><?=h($l['full_name'])?></a><small><?=h($l['phone'])?></small></td><td><?=h($l['loan_type'])?></td><td><?=money($l['requested_amount'])?></td><td><span class="status status-<?=h(strtolower(str_replace(' ','-',$l['status'])))?>"><?=h($l['status'])?></span></td><td><?=h($l['assigned_name']??'Unassigned')?></td></tr><?php endforeach;?>
</tbody></table></div></section>
<section class="panel"><div class="panel-heading"><div><h2>Follow-ups</h2><p class="muted">Tasks that need attention</p></div><a class="text-link" href="tasks.php">View all →</a></div>
<div class="task-list"><?php if(!$tasks):?><div class="empty">No open follow-up tasks.</div><?php endif;?><?php foreach($tasks as $t):?><div class="task-row"><span class="task-check"></span><div class="task-info"><strong><?=h($t['title'])?></strong><small><?=h($t['full_name']??'No linked lead')?></small></div><span class="task-date"><?=h($t['due_date']?:'No date')?></span></div><?php endforeach;?></div><a class="btn secondary full" href="tasks.php">＋ Create follow-up task</a></section>
</div>
<section class="panel pipeline-panel"><div class="panel-heading"><div><h2>Pipeline snapshot</h2><p class="muted">Lead count by current status</p></div><a class="text-link" href="leads.php">Manage pipeline →</a></div><div class="pipeline-grid"><?php $statusList=['New','Contacted','Qualified','Documents Pending','Submitted','Sanctioned','Disbursed','Rejected','Closed'];$map=[];foreach($counts as $c)$map[$c['status']]=(int)$c['total'];foreach($statusList as $s):?><div class="pipeline-item"><span class="pipeline-dot dot-<?=h(strtolower(str_replace(' ','-',$s)))?>"></span><span><?=h($s)?></span><strong><?=$map[$s]??0?></strong></div><?php endforeach;?></div></section>
<?php require __DIR__ . '/partials/footer.php'; ?>
