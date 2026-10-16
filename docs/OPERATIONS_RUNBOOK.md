# Operations Runbook

- Backup: `mysqldump -u root kazaerp | gzip > backup-$(date +%F).sql.gz` + arsip `storage/app/private`; simpan offsite; retensi 30 harian + 12 bulanan (minta persetujuan). Alternatif teruji: `php artisan kaza:backup`. Restore telah diverifikasi 15 Okt 2026 (lihat TEST_RESULTS.md); uji berkala tiap kuartal.
- Restore: buat DB kosong → `gunzip < backup.sql.gz | mysql kazaerp` → `php artisan migrate --force` (verifikasi) → cek `IMPLEMENTATION_STATUS`.
- Log: `storage/logs/laravel.log`; antrean gagal: `php artisan queue:failed` / `queue:retry`.
- Queue worker: `php artisan queue:work --tries=3` (supervisor/systemd); job terjadwal `kaza:overdue-alerts` (scheduler 08:00) & `kaza:expiry-alerts` (08:30; dev tanpa worker: tambahkan `--sync`). Pantau `jobs`/`failed_jobs`.
- DB maintenance: `ANALYZE/OPTIMIZE` berkala; audit integritas: `php artisan kaza:audit-integrity` (saldo stok vs ledger, ledger yatim, balance jurnal, paid_total invoice vs pembayaran; exit 1 bila ada temuan → cocok untuk cron alerting).
- Insiden: bekukan posting (maintenance mode), snapshot DB, periksa audit_logs + login_activities, reversal (jangan hapus).
- Closing bulanan: rekonsiliasi bank → review unposted → tutup periode → backup.
- Recovery: RTO target 4 jam, RPO 24 jam (sesuaikan + uji).
