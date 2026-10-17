<?php
return [
    'timezone' => 'Asia/Jakarta',
    'currency' => 'IDR',
    'locale' => 'id',
    // Kebijakan waktu: DB menyimpan UTC (TIMESTAMP Laravel), tampil Asia/Jakarta.
    'db_stores_utc' => true,
    'display_timezone' => 'Asia/Jakarta',
    // Metode HPP default: PENDING_BUSINESS_DECISION — Finance harus menyetujui.
    // Nilai 'none' = valuasi sederhana (stok x harga), bukan klaim FIFO/average penuh.
    'cogs_method' => env('KAZA_COGS_METHOD', 'none'), // none|fifo|average (butuh persetujuan Finance)
    'doc_number' => [
        'pattern' => '{ENTITY}/{BRANCH}/{TYPE}/{YYYY}/{MM}/{SEQ:5}',
        'reset' => 'monthly', // per entitas+cabang+tipe+periode
    ],
    // Mode demo AMAN: default mati. DemoSeeder + demo:seed menolak production
    // kecuali DEMO_ENABLED=true (dan --force untuk command).
    'demo_enabled' => env('DEMO_ENABLED', false),
];
