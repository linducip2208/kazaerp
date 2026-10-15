# Operations Runbook

- Backup: `mysqldump -u root kazaerp | gzip > backup-$(date +%F).sql.gz` + arsip `storage/app/private`; simpan offsite; retensi 30 harian + 12 bulanan (minta persetujuan).
- Restore: buat DB kosong → `gunzip < backup.sql.gz | mysql kazaerp` → `php artisan migrate --force` (verifikasi) → cek `IMPLEMENTATION_STATUS`.
- Log: `storage/logs/laravel.log`; antrean gagal: `php artisan queue:failed` / `queue:retry`.
- DB maintenance: `ANALYZE/OPTIMIZE` berkala; pantau `stock_balances` vs `stock_ledgers` (rekonsiliasi: sum ledger vs saldo).
- Insiden: bekukan posting (maintenance mode), snapshot DB, periksa audit_logs + login_activities, reversal (jangan hapus).
- Closing bulanan: rekonsiliasi bank → review unposted → tutup periode → backup.
- Recovery: RTO target 4 jam, RPO 24 jam (sesuaikan + uji).
