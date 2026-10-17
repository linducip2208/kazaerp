<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Laporan enterprise + impor: endpoint 200, ekspor CSV/PDF aktual,
 * impor supplier + template, tanpa migrate:fresh di luar TestCase.
 */
class ReportsEnterpriseTest extends TestCase
{
    protected function authed()
    {
        $u = $this->makeUser('module_operator');
        $this->actingAs($u);

        return $this->withSession(['erp_context' => $this->ctx()]);
    }

    protected function seedDocs(): void
    {
        $cust = DB::table('customers')->where(['legal_entity_id' => $this->eid, 'code' => 'TC-001'])->value('id');
        $supp = DB::table('suppliers')->where(['legal_entity_id' => $this->eid, 'code' => 'TS-001'])->value('id');
        $prod = DB::table('products')->where(['legal_entity_id' => $this->eid, 'sku' => 'T-001'])->value('id');
        DB::table('invoices')->insert([
            'legal_entity_id' => $this->eid, 'branch_id' => $this->bid, 'number' => 'INV-ENT-001',
            'date' => '2026-10-01', 'customer_id' => $cust, 'due_date' => '2026-10-15',
            'paid_total' => 5000, 'pay_status' => 'partial', 'status' => 'posted',
            'subtotal' => 15000, 'tax' => 0, 'grand_total' => 15000, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('purchase_orders')->insert([
            'legal_entity_id' => $this->eid, 'branch_id' => $this->bid, 'number' => 'PO-ENT-001',
            'date' => '2026-10-02', 'supplier_id' => $supp, 'status' => 'ordered',
            'subtotal' => 20000, 'tax' => 2000, 'grand_total' => 22000, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('stock_ledgers')->insert([
            'warehouse_id' => $this->wh, 'product_id' => $prod, 'qty' => 10,
            'unit_cost' => 10000, 'ref_type' => 'opening', 'ref_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_sales_report_page_filters_summary()
    {
        $this->seedDocs();
        $r = $this->authed()->get(route('reports.sales', ['from' => '2026-10-01', 'to' => '2026-10-31']));
        $r->assertOk()->assertSee('INV-ENT-001')->assertSee('Rp');
        // ringkasan menghormati filter tanggal: q di luar rentang tidak ikut
        $r2 = $this->authed()->get(route('reports.sales', ['from' => '2025-01-01', 'to' => '2025-01-31']));
        $r2->assertOk()->assertDontSee('INV-ENT-001');
    }

    public function test_sales_csv_pdf_export_full_result()
    {
        $this->seedDocs();
        $csv = $this->authed()->get(route('reports.sales', ['export' => 'csv']));
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('nomor,tanggal,pelanggan,total,bayar,status', $csv->getContent());
        $this->assertStringContainsString('INV-ENT-001', $csv->getContent());
        $pdf = $this->authed()->get(route('reports.sales', ['export' => 'pdf']));
        $pdf->assertOk();
        $this->assertStringContainsString('pdf', $pdf->headers->get('content-type'));
        $this->assertTrue(strlen($pdf->getContent()) > 1000, 'PDF tidak boleh kosong');
    }

    public function test_purchase_report_fixed_columns_and_export()
    {
        $this->seedDocs();
        $r = $this->authed()->get(route('reports.purchase'));
        $r->assertOk()->assertSee('PO-ENT-001')->assertSee('Supplier Uji');
        $csv = $this->authed()->get(route('reports.purchase', ['export' => 'csv']));
        $csv->assertOk();
        $this->assertStringContainsString('nomor,tanggal,supplier,subtotal,pajak,total,status', $csv->getContent());
        $this->assertStringContainsString('PO-ENT-001', $csv->getContent());
        $pdf = $this->authed()->get(route('reports.purchase', ['export' => 'pdf']));
        $pdf->assertOk();
    }

    public function test_inventory_and_aging_reports_export()
    {
        $this->seedDocs();
        $this->authed()->get(route('reports.inventory'))->assertOk()->assertSee('T-001');
        $csv = $this->authed()->get(route('reports.inventory', ['export' => 'csv']));
        $csv->assertOk();
        $this->assertStringContainsString('waktu,sku,produk,gudang,qty,ref_type,ref_id', $csv->getContent());
        $this->authed()->get(route('reports.aging'))->assertOk();
        $csv2 = $this->authed()->get(route('reports.aging', ['export' => 'csv']));
        $csv2->assertOk();
        $this->assertStringContainsString('gudang,sku,produk,tersedia,mutasi_terakhir,umur_hari', $csv2->getContent());
    }

    public function test_finance_reports_csv_export()
    {
        foreach (['finance.ledger', 'finance.trial', 'finance.pl', 'finance.aging'] as $name) {
            $this->authed()->get(route($name))->assertOk();
            $csv = $this->authed()->get(route($name, ['export' => 'csv']));
            $csv->assertOk();
            $this->assertStringContainsString('text/csv', $csv->headers->get('content-type'), $name);
        }
    }

    public function test_import_suppliers_and_templates()
    {
        // template
        $t = $this->authed()->get(route('reports.import.template', 'suppliers'));
        $t->assertOk();
        $this->assertStringContainsString('code,name', $t->getContent());
        $this->authed()->get(route('reports.import.template', 'customers'))->assertOk();
        $this->authed()->get(route('reports.import.template', 'products'))->assertOk();

        // impor valid + duplikat dilewati
        $csv = "code,name,phone,email,bank_account\nS-NEW-1,Supplier Baru,0811,x@baru.test,999\nTS-001,Duplikat,0811,,,\n";
        $file = UploadedFile::fake()->createWithContent('sup.csv', $csv);
        $r = $this->authed()->post(route('reports.import.suppliers.store'), ['file' => $file]);
        $r->assertSessionHas('ok');
        $this->assertDatabaseHas('suppliers', ['legal_entity_id' => $this->eid, 'code' => 'S-NEW-1']);
        $this->assertEquals(1, DB::table('suppliers')->where(['legal_entity_id' => $this->eid, 'code' => 'TS-001'])->count());

        // header tidak valid ditolak
        $bad = UploadedFile::fake()->createWithContent('bad.csv', "foo,bar\n1,2\n");
        $r2 = $this->authed()->post(route('reports.import.suppliers.store'), ['file' => $bad]);
        $r2->assertSessionHasErrors('msg');

        // halaman impor bisa dibuka
        $this->authed()->get(route('reports.import.suppliers'))->assertOk();
        $this->authed()->get(route('reports.import.customers'))->assertOk();
        $this->authed()->get(route('reports.import.products'))->assertOk();
    }

    public function test_import_pages_linked_from_index()
    {
        $this->authed()->get(route('crm.customers.index'))->assertOk()->assertSee('Impor CSV');
        $this->authed()->get(route('crm.suppliers.index'))->assertOk()->assertSee('Impor CSV');
        $this->authed()->get(route('master.products.index'))->assertOk()->assertSee('Impor CSV');
    }
}
