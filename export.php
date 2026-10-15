<?php
require __DIR__ . '/bootstrap.php'; require_login();
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="loandesk-leads-'.date('Y-m-d').'.csv"');
$out=fopen('php://output','w');fputcsv($out,['Lead ID','Name','Phone','Email','City','Employment','Monthly Income','Loan Type','Requested Amount','Source','Status','Assigned To','Next Follow-up','Consent','Created At']);
$stmt=$pdo->query('SELECT l.*,u.name assigned_name FROM leads l LEFT JOIN users u ON u.id=l.assigned_to ORDER BY l.created_at DESC');
while($r=$stmt->fetch()){fputcsv($out,[$r['id'],$r['full_name'],$r['phone'],$r['email'],$r['city'],$r['employment_type'],$r['monthly_income'],$r['loan_type'],$r['requested_amount'],$r['source'],$r['status'],$r['assigned_name'],$r['next_follow_up'],$r['consent_status'],$r['created_at']]);}fclose($out);exit;
