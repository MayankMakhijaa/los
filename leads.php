<?php
require __DIR__ . '/bootstrap.php'; require_login();
$q=trim($_GET['q']??'');$status=trim($_GET['status']??'');$type=trim($_GET['type']??'');
$sql="SELECT l.*,u.name assigned_name FROM leads l LEFT JOIN users u ON u.id=l.assigned_to WHERE 1=1";$params=[];
if($q!==''){$sql.=" AND (l.full_name LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)";$like='%'.$q.'%';array_push($params,$like,$like,$like);}
if($status!=='' && in_array($status,['New','Contacted','Qualified','Documents Pending','Submitted','Sanctioned','Disbursed','Rejected','Closed'],true)){$sql.=" AND l.status=?";$params[]=$status;}
if($type!==''){$sql.=" AND l.loan_type=?";$params[]=$type;}
$sql.=" ORDER BY l.created_at DESC LIMIT 500";$stmt=$pdo->prepare($sql);$stmt->execute($params);$leads=$stmt->fetchAll();
$products=$pdo->query("SELECT name FROM loan_products WHERE active=1 ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);$flash=get_flash();require __DIR__.'/partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">CUSTOMER PIPELINE</p><h1>Leads</h1><p class="muted">Capture, qualify and move every enquiry forward.</p></div><div class="heading-actions"><a class="btn secondary" href="export.php">↓ Export CSV</a><a class="btn primary" href="lead-form.php">＋ Add lead</a></div></div>
<?php if($flash):?><div class="alert <?=h($flash['type'])?>"><?=h($flash['message'])?></div><?php endif;?>
<section class="panel"><form class="filter-bar" method="get"><div class="search-field"><span>⌕</span><input name="q" value="<?=h($q)?>" placeholder="Search name, phone or email"></div><select name="status"><option value="">All statuses</option><?php foreach(['New','Contacted','Qualified','Documents Pending','Submitted','Sanctioned','Disbursed','Rejected','Closed'] as $s):?><option <?=($status===$s?'selected':'')?> value="<?=h($s)?>"><?=h($s)?></option><?php endforeach;?></select><select name="type"><option value="">All loan types</option><?php foreach($products as $p):?><option <?=($type===$p?'selected':'')?> value="<?=h($p)?>"><?=h($p)?></option><?php endforeach;?></select><button class="btn secondary">Filter</button><?php if($q||$status||$type):?><a class="text-link" href="leads.php">Reset</a><?php endif;?></form>
<div class="table-wrap"><table><thead><tr><th>Customer</th><th>Contact</th><th>Loan type</th><th>Requested</th><th>Source</th><th>Status</th><th>Follow-up</th><th></th></tr></thead><tbody>
<?php if(!$leads):?><tr><td colspan="8" class="empty">No matching leads found.</td></tr><?php endif;?>
<?php foreach($leads as $l):?><tr><td><a class="customer-link" href="lead-view.php?id=<?=(int)$l['id']?>"><?=h($l['full_name'])?></a><small><?=h($l['city']?:'City not set')?></small></td><td><?=h($l['phone'])?><small><?=h($l['email']?:'No email')?></small></td><td><?=h($l['loan_type'])?></td><td><?=money($l['requested_amount'])?></td><td><?=h($l['source'])?></td><td><span class="status status-<?=h(strtolower(str_replace(' ','-',$l['status'])))?>"><?=h($l['status'])?></span></td><td><?=h($l['next_follow_up']?:'—')?></td><td><a class="icon-action" href="lead-form.php?id=<?=(int)$l['id']?>" title="Edit lead">↗</a></td></tr><?php endforeach;?>
</tbody></table></div><div class="table-foot"><span>Showing <?=count($leads)?> leads (maximum 500 per view)</span></div></section>
<?php require __DIR__.'/partials/footer.php';?>
