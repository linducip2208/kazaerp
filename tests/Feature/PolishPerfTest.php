<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Regresi polish Paket 1: halaman berat non-dashboard bebas N+1.
 * Baseline diukur 18 Okt 2026 (MySQL kazaerp_testing, data TestCase).
 * Bila ambang dilampaui → indikasi eager loading hilang / otorisasi bocor query.
 */
class PolishPerfTest extends TestCase
{
    protected function countQueries(string $method, string $uri): array
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });
        $r = $this->$method($uri);
        DB::flushQueryLog();

        return [$r->status(), $n];
    }

    public function test_index_pelanggan_query_terbatas(): void
    {
        [$status, $n] = $this->countQueries('get', '/crm/customers');
        $this->assertEquals(200, $status);
        $this->assertLessThan(40, $n, "Index pelanggan berat: {$n} query.");
    }

    public function test_index_sales_order_query_terbatas(): void
    {
        [$status, $n] = $this->countQueries('get', '/sales/orders');
        $this->assertEquals(200, $status);
        $this->assertLessThan(40, $n, "Index SO berat: {$n} query.");
    }

    public function test_laporan_penjualan_query_terbatas(): void
    {
        [$status, $n] = $this->countQueries('get', '/reports/sales');
        $this->assertEquals(200, $status);
        $this->assertLessThan(40, $n, "Laporan penjualan berat: {$n} query.");
    }

    public function test_index_jurnal_query_terbatas(): void
    {
        [$status, $n] = $this->countQueries('get', '/finance/journals');
        $this->assertEquals(200, $status);
        $this->assertLessThan(40, $n, "Index jurnal berat: {$n} query.");
    }
}
