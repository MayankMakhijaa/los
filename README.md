# LoanDesk LOS — PHP + MySQL

A Hostinger-friendly Loan Lead Origination System MVP built with PHP 8+, PDO, MySQL/MariaDB, HTML, CSS and vanilla JavaScript.

## Included
- First-run installer and admin account creation
- Login/logout with password hashing and session regeneration
- CSRF protection for POST forms
- Lead dashboard and lead management (create, edit, view, status updates, search/filter)
- Loan product configuration
- Follow-up task list and creation
- Lead activity timeline
- CSV lead export
- Prepared SQL statements and output escaping
- Responsive UI

## Requirements
- PHP 8.1+ (8.2 recommended)
- MySQL 5.7+ or MariaDB 10.4+
- PHP PDO MySQL extension
- HTTPS enabled in production

## Local setup
1. Copy `config.example.php` to `config.php`.
2. Create an empty MySQL database and user.
3. Put credentials in `config.php`.
4. Serve the folder through PHP/Apache and open `install.php`.
5. Create the first administrator.
6. Sign in and delete or rename `install.php` after installation.

## Hostinger deployment
1. Create a MySQL database and database user in hPanel.
2. Upload the project contents to `public_html/` or a subfolder through Git deployment / File Manager.
3. Copy `config.example.php` to `config.php`, then enter the database name, user, password, and host shown in hPanel. Hostinger's database host is not always `localhost`; use the value displayed in hPanel.
4. Visit `https://your-domain/install.php`, create the administrator, then delete `install.php`.
5. Ensure `config.php` is excluded from Git (already in `.gitignore`) and never commit credentials.
6. Enable HTTPS. In production, consider moving `config.php` outside the public web root if your hosting setup supports it.

## Security and product scope
This is a functional MVP, not a complete regulated lending platform. It does not make credit decisions, connect to lenders/credit bureaus, perform KYC, or guarantee eligibility. Use synthetic data while testing. Before production use with real customer information, add role/tenant isolation, secure document storage, retention controls, backup/restore procedures, privacy notices/consent workflows, audit review, and a security assessment.

## Git workflow
```bash
git init
git add .
git commit -m "Initial LoanDesk LOS MVP"
git branch -M main
git remote add origin YOUR_PRIVATE_GITHUB_REPOSITORY_URL
git push -u origin main
```
Keep `config.php` untracked. Deploy from the private repository through Hostinger's supported Git workflow.
