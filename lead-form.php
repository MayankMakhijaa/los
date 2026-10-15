<?php
require __DIR__ . '/bootstrap.php'; require_login();
$id=(int)($_GET['id']??0);$editing=$id>0;
$statuses=['New','Contacted','Qualified','Documents Pending','Submitted','Sanctioned','Disbursed','Rejected','Closed'];
$defaults=['full_name'=>'','phone'=>'','email'=>'','city'=>'','employment_type'=>'Salaried','monthly_income'=>'','loan_type'=>'Personal Loan','requested_amount'=>'','source'=>'Manual','status'=>'New','assigned_to'=>'','next_follow_up'=>'','consent_status'=>'Not recorded','notes'=>''];
$data=$defaults;
if($editing){$stmt=$pdo->prepare('SELECT * FROM leads WHERE id=?');$stmt->execute([$id]);$data=$stmt->fetch();if(!$data){http_response_code(404);exit('Lead not found.');}}
$users=$pdo->query('SELECT id,name FROM users ORDER BY name')->fetchAll();$products=$pdo->query('SELECT name FROM loan_products WHERE active=1 ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$fields=array_keys($defaults);foreach($fields as $f)$data[$f]=trim((string)($_POST[$f]??''));
 if($data['full_name']===''||$data['phone']===''||$data['loan_type']==='')$error='Name, mobile number and loan product are required.';
 elseif(!in_array($data['status'],$statuses,true))$error='Invalid pipeline status.';
 elseif($data['email']!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL))$error='Enter a valid email address.';
 else{
  $amount=max(0,(float)$data['requested_amount']);$income=max(0,(float)$data['monthly_income']);$assigned=$data['assigned_to']===''?null:(int)$data['assigned_to'];$follow=$data['next_follow_up']===''?null:$data['next_follow_up'];$email=$data['email']===''?null:$data['email'];
  if($editing){
   $stmt=$pdo->prepare('UPDATE leads SET full_name=?,phone=?,email=?,city=?,employment_type=?,monthly_income=?,loan_type=?,requested_amount=?,source=?,status=?,assigned_to=?,next_follow_up=?,consent_status=?,notes=? WHERE id=?');
   $stmt->execute([$data['full_name'],$data['phone'],$email,$data['city'],$data['employment_type'],$income,$data['loan_type'],$amount,$data['source'],$data['status'],$assigned,$follow,$data['consent_status'],$data['notes'],$id]);
   log_activity($pdo,$id,current_user()['id'],'Updated','Lead details updated. Current status: '.$data['status']);
   flash('Lead updated successfully.');
  }else{
   $stmt=$pdo->prepare('INSERT INTO leads (full_name,phone,email,city,employment_type,monthly_income,loan_type,requested_amount,source,status,assigned_to,next_follow_up,consent_status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
   $stmt->execute([$data['full_name'],$data['phone'],$email,$data['city'],$data['employment_type'],$income,$data['loan_type'],$amount,$data['source']?:'Manual',$data['status'],$assigned,$follow,$data['consent_status'],$data['notes'],current_user()['id']]);$id=(int)$pdo->lastInsertId();
   log_activity($pdo,$id,current_user()['id'],'Created','Lead created from '.$data['source'].'.');
   flash('Lead created successfully.');
  }
  header('Location: lead-view.php?id='.$id);exit;
 }
}
require __DIR__.'/partials/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">LEAD MANAGEMENT</p><h1><?=$editing?'Edit lead':'Add new lead'?></h1><p class="muted"><?=$editing?'Update customer and pipeline information.':'Capture a new loan enquiry.'?></p></div><a class="btn secondary" href="<?=$editing?'lead-view.php?id='.$id:'leads.php'?>">← Back</a></div>
<?php if($error):?><div class="alert error"><?=h($error)?></div><?php endif;?>
<form method="post" class="panel form-panel"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><div class="form-section"><h2>Customer information</h2><p class="muted">Basic contact and profile details.</p><div class="form-grid">
<label>Full name *<input name="full_name" required maxlength="160" value="<?=h($data['full_name'])?>"></label><label>Mobile number *<input name="phone" required maxlength="30" value="<?=h($data['phone'])?>" placeholder="+91"></label><label>Email address<input type="email" name="email" value="<?=h($data['email'])?>"></label><label>City<input name="city" maxlength="100" value="<?=h($data['city'])?>"></label>
<label>Employment type<select name="employment_type"><?php foreach(['Salaried','Self-employed','Business owner','Other'] as $v):?><option <?=($data['employment_type']===$v?'selected':'')?> value="<?=h($v)?>"><?=h($v)?></option><?php endforeach;?></select></label><label>Monthly income (₹)<input type="number" min="0" step="0.01" name="monthly_income" value="<?=h($data['monthly_income'])?>"></label></div></div>
<div class="form-section"><h2>Loan requirement</h2><p class="muted">Requested product and expected amount.</p><div class="form-grid"><label>Loan product *<select name="loan_type" required><?php foreach($products as $p):?><option <?=($data['loan_type']===$p?'selected':'')?> value="<?=h($p)?>"><?=h($p)?></option><?php endforeach;?><option <?=(!in_array($data['loan_type'],$products,true)?'selected':'')?> value="<?=h($data['loan_type'])?>"><?=h($data['loan_type'])?></option></select></label><label>Requested loan amount (₹)<input type="number" min="0" step="1000" name="requested_amount" value="<?=h($data['requested_amount'])?>"></label><label>Lead source<input name="source" maxlength="100" value="<?=h($data['source'])?>" placeholder="Website, referral, campaign…"></label><label>Customer consent<select name="consent_status"><?php foreach(['Not recorded','Granted','Withdrawn'] as $v):?><option <?=($data['consent_status']===$v?'selected':'')?> value="<?=h($v)?>"><?=h($v)?></option><?php endforeach;?></select></label></div></div>
<div class="form-section"><h2>Pipeline and follow-up</h2><div class="form-grid"><label>Pipeline status<select name="status"><?php foreach($statuses as $v):?><option <?=($data['status']===$v?'selected':'')?> value="<?=h($v)?>"><?=h($v)?></option><?php endforeach;?></select></label><label>Assigned to<select name="assigned_to"><option value="">Unassigned</option><?php foreach($users as $u):?><option <?=((string)$data['assigned_to']===(string)$u['id']?'selected':'')?> value="<?=(int)$u['id']?>"><?=h($u['name'])?></option><?php endforeach;?></select></label><label>Next follow-up date<input type="date" name="next_follow_up" value="<?=h($data['next_follow_up'])?>"></label><label class="span-2">Notes<textarea name="notes" rows="4" maxlength="10000" placeholder="Customer requirements, call summary, next action…"><?=h($data['notes'])?></textarea></label></div></div>
<div class="form-actions"><a class="btn secondary" href="<?=$editing?'lead-view.php?id='.$id:'leads.php'?>">Cancel</a><button class="btn primary" type="submit"><?=$editing?'Save changes':'Create lead'?></button></div></form>
<?php require __DIR__.'/partials/footer.php';?>
