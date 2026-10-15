<?php
declare(strict_types=1);
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    exit('Missing config.php. Copy config.example.php to config.php and set database credentials first.');
}
$config = require $configPath;
date_default_timezone_set($config['timezone'] ?? 'Asia/Kolkata');
$error = '';
$done = false;
try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','manager','agent') NOT NULL DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS loan_products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        min_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        max_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        min_income DECIMAL(14,2) NOT NULL DEFAULT 0,
        tenure_months INT UNSIGNED NOT NULL DEFAULT 12,
        indicative_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(160) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        email VARCHAR(190) NULL,
        city VARCHAR(100) NULL,
        employment_type ENUM('Salaried','Self-employed','Business owner','Other') NOT NULL DEFAULT 'Salaried',
        monthly_income DECIMAL(14,2) NOT NULL DEFAULT 0,
        loan_type VARCHAR(100) NOT NULL,
        requested_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        source VARCHAR(100) NOT NULL DEFAULT 'Manual',
        status ENUM('New','Contacted','Qualified','Documents Pending','Submitted','Sanctioned','Disbursed','Rejected','Closed') NOT NULL DEFAULT 'New',
        assigned_to INT UNSIGNED NULL,
        next_follow_up DATE NULL,
        notes TEXT NULL,
        consent_status ENUM('Not recorded','Granted','Withdrawn') NOT NULL DEFAULT 'Not recorded',
        created_by INT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_leads_status(status),
        INDEX idx_leads_phone(phone),
        INDEX idx_leads_created(created_at),
        CONSTRAINT fk_leads_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_leads_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS activities (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        lead_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NULL,
        activity_type VARCHAR(80) NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_activities_lead(lead_id),
        CONSTRAINT fk_activity_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
        CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        lead_id INT UNSIGNED NULL,
        assigned_to INT UNSIGNED NULL,
        due_date DATE NULL,
        priority ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
        status ENUM('Open','Completed') NOT NULL DEFAULT 'Open',
        notes TEXT NULL,
        created_by INT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tasks_due(due_date,status),
        CONSTRAINT fk_task_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
        CONSTRAINT fk_task_assignee FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_task_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($count > 0) {
            $error = 'An administrator already exists. Delete install.php and sign in.';
        } else {
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
                $error = 'Enter a name, valid email, and password with at least 12 characters.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $pdo->exec("INSERT INTO loan_products (name,min_amount,max_amount,min_income,tenure_months,indicative_rate) VALUES
                    ('Personal Loan',50000,2500000,20000,36,14.5),
                    ('Business Loan',100000,10000000,30000,48,16.0),
                    ('Home Loan',500000,50000000,35000,240,8.75)");
                $done = true;
            }
        }
    }
} catch (Throwable $e) {
    $error = 'Setup failed. Check your database name, host, username, password, and database permissions. Details: ' . $e->getMessage();
}
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install LoanDesk LOS</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="auth-page"><main class="auth-card"><div class="brand-mark">LD</div><h1>Set up LoanDesk LOS</h1><p class="muted">Connect the database and create your first administrator.</p>
<?php if ($done): ?><div class="alert success">Installation complete. <strong>Delete install.php now</strong>, then <a href="login.php">sign in</a>.</div>
<?php else: ?><?php if ($error): ?><div class="alert error"><?=h($error)?></div><?php endif; ?>
<form method="post" class="form-stack"><label>Administrator name<input name="name" required maxlength="120" autocomplete="name"></label><label>Administrator email<input type="email" name="email" required autocomplete="email"></label><label>Password (minimum 12 characters)<input type="password" name="password" required minlength="12" autocomplete="new-password"></label><button class="btn primary full" type="submit">Install application</button></form><?php endif; ?></main></body></html>
