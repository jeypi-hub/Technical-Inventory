<?php
require_once __DIR__ . '/../config/database.php';

class Mailer
{
    private static ?array $cfg = null;
    private static array $log = [];  

    public static function cfg(): array
    {
        if (self::$cfg === null) {
            self::$cfg = require __DIR__ . '/../config/mail.php';
        }
        return self::$cfg;
    }

    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $cfg = self::cfg();

        // Always log so it's easy to debug even when SMTP is off
        $entry = date('Y-m-d H:i:s') . " → {$to} :: {$subject}\n";
        @file_put_contents(__DIR__ . '/../assets/upload/mail.log', $entry, FILE_APPEND);

        if (empty($cfg['enabled']) || !$to) return false;

        $subject = ($cfg['subject_prefix'] ?? '') . $subject;
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: ' . ($cfg['from_name'] ?? 'ACLC') . ' <' . $cfg['from'] . '>' . "\r\n";
        $headers .= 'Reply-To: ' . $cfg['from'] . "\r\n";

        return @mail($to, $subject, $htmlBody, $headers);
    }

    public static function notifyDecision(int $userId, string $decision, array $req): void
    {
        $stmt = db()->prepare('SELECT name, email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if (!$u || empty($u['email'])) return;

        $verb   = $decision === 'approved' ? 'APPROVED' : 'REJECTED';
        $color  = $decision === 'approved' ? '#16a34a'  : '#dc2626';
        $item   = htmlspecialchars($req['equipment_name'] ?? 'your requested item');
        $sn     = htmlspecialchars($req['serial_number'] ?? '');
        $subject= "Your borrow request was {$verb}";

        $html = "<!doctype html><html><body style='font-family:Segoe UI,Arial,sans-serif;background:#f4f5fb;padding:24px'>
          <div style='max-width:560px;margin:auto;background:#fff;border-radius:16px;padding:28px;box-shadow:0 6px 24px rgba(0,0,0,.08)'>
            <h2 style='margin:0 0 8px;color:{$color}'>Request {$verb}</h2>
            <p>Hi " . htmlspecialchars($u['name']) . ",</p>
            <p>Your borrow request for <strong>{$item}</strong> " .
              ($sn ? "(SN: {$sn})" : '') .
              " has been <strong style='color:{$color}'>{$verb}</strong>.</p>
            <p style='color:#6b6f80;font-size:12px;margin-top:24px'>ACLC College — Technical Equipment Inventory System</p>
          </div></body></html>";

        self::send($u['email'], $subject, $html);
    }
}
