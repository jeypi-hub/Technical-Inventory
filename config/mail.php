<?php
/**
 * Mail configuration.
 * Emails are sent via PHP's built-in mail() function.
 *
 * ------------------------------------------------------------------
 *  XAMPP quick-setup for Gmail SMTP:
 *   1. Edit  xampp\sendmail\sendmail.ini
 *        [sendmail]
 *        smtp_server=smtp.gmail.com
 *        smtp_port=587
 *        smtp_ssl=tls
 *        auth_username=your.email@gmail.com
 *        auth_password=your-16-char-app-password
 *   2. Edit  xampp\php\php.ini  (uncomment/set)
 *        [mail function]
 *        SMTP=smtp.gmail.com
 *        smtp_port=587
 *        sendmail_from = your.email@gmail.com
 *        sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
 *   3. Restart Apache.
 *   4. Set  MAIL_ENABLED = true  below and update FROM address.
 * ------------------------------------------------------------------
 */
return [
    'enabled'   => false,                 // flip to true after configuring sendmail
    'from'      => 'no-reply@aclc.edu.ph',
    'from_name' => 'ACLC Technical Inventory',
    'subject_prefix' => '[ACLC Inventory] ',
];
