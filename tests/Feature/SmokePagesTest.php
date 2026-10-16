<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke otomatis: semua halaman menu utama harus 200 untuk super_admin
 * (menggantikan smoke manual; regresi UI/route tertangkap di suite).
 */
class SmokePagesTest extends TestCase
{
    public function test_semua_halaman_menu_utama_200(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);

        $urls = [
            '/',
            // Organisasi & identitas
            'org/holdings', 'org/entities', 'org/branches', 'org/warehouses',
            'users', 'roles', 'profile', 'notifications', 'files',
            // CRM & master
            'crm/customers', 'crm/leads', 'crm/opportunities', 'crm/suppliers',
            'master/products', 'master/categories',
            // Sales & POS
            'sales/quotes', 'sales/orders', 'sales/deliveries', 'sales/invoices',
            'sales/returns', 'sales/creditnotes', 'sales/pos',
            // Inventory & purchasing
            'inventory/batches', 'inventory/receipts', 'inventory/stock',
            'purchasing/requisitions', 'purchasing/orders', 'purchasing/vinvoices',
            'purchasing/appayments', 'purchasing/returns',
            // Finance
            'finance/journals', 'finance/ledger', 'finance/trial', 'finance/pl', 'finance/aging',
            'finance/bankrec', 'finance/budget', 'finance/closing', 'finance/expenses',
            'finance/commissions', 'finance/intercompany',
            // Laporan & HR & governance & marketing
            'reports/sales', 'reports/purchase', 'reports/inventory', 'reports/aging',
            'reports/import/customers', 'reports/import/products',
            'hr/employees', 'hr/attendance', 'hr/payroll',
            'gov/approvals', 'gov/delegations',
            'marketing/campaigns', 'marketing/vouchers',
            // Produksi & studio & thrift & rental
            'mfg/boms', 'mfg/designs', 'mfg/workorders',
            'studio/projects', 'thrift/items', 'thrift/lots',
            'rental/catalogs', 'rental/reservations',
            // Pet
            'pet/owners', 'pet/pets', 'pet/vet', 'pet/boarding', 'pet/grooming',
            'pet/transport', 'pet/transport/tracking',
        ];

        $failed = [];
        foreach ($urls as $u) {
            $status = $this->get($u)->getStatusCode();
            if ($status !== 200) {
                $failed[] = $u.' → '.$status;
            }
        }
        $this->assertSame([], $failed, "Halaman gagal: \n".implode("\n", $failed));
        $this->assertGreaterThanOrEqual(60, count($urls), 'Cakupan smoke minimal 60 halaman.');
    }
}
