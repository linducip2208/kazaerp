<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $u = auth()->user();
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $bid = $ctx['branch_id'] ?? $u->branch_id;
        $inv = DB::table('invoices')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid));
        $salesMonth = (clone $inv)->where('date', '>=', now()->startOfMonth()->toDateString())->sum('grand_total');
        $receivable = (clone $inv)->whereIn('pay_status', ['unpaid', 'partial'])->sum(DB::raw('grand_total - paid_total'));
        $payable = DB::table('vendor_invoices')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->whereIn('status', ['approved', 'partial', 'draft', 'hold'])->sum(DB::raw('grand_total - paid_total'));
        $lowStock = DB::table('stock_balances')->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->when($eid, fn ($q) => $q->where('products.legal_entity_id', $eid))
            ->whereRaw('qty_on_hand - qty_reserved <= 5')->count();
        $pendingApprovals = DB::table('approvals')->where('status', 'pending')->count();
        // Catatan skema: approvals tidak punya legal_entity_id → hitungan global (bukan per entitas).
        $pendingList = DB::table('approvals')->where('status', 'pending')->orderByDesc('id')->limit(5)->get();
        $lowItems = DB::table('stock_balances')->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->when($eid, fn ($q) => $q->where('products.legal_entity_id', $eid))
            ->whereRaw('qty_on_hand - qty_reserved <= 5')
            ->selectRaw('products.sku, products.name, warehouses.code as wh, (stock_balances.qty_on_hand - stock_balances.qty_reserved) as avail')
            ->orderBy('avail')->limit(5)->get();
        $orders = DB::table('sales_orders')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->where('status', '!=', 'cancelled')->orderByDesc('id')->limit(8)->get();
        $perEntity = collect();
        if ($u->hasRole('super_admin', 'holding_owner', 'executive')) {
            $perEntity = DB::table('invoices')->join('legal_entities', 'legal_entities.id', '=', 'invoices.legal_entity_id')
                ->selectRaw('legal_entities.code, legal_entities.name, SUM(invoices.grand_total) as total')
                ->where('invoices.date', '>=', now()->startOfMonth()->toDateString())
                ->groupBy('legal_entities.code', 'legal_entities.name')->orderByDesc('total')->get();
        }
        // Tren 6 bulan (grafik lokal Chart.js)
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $trend['labels'][] = $m->translatedFormat('M Y');
            $trend['values'][] = (float) DB::table('invoices')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))
                ->where('date', '>=', $m->copy()->startOfMonth()->toDateString())
                ->where('date', '<=', $m->copy()->endOfMonth()->toDateString())->sum('grand_total');
        }
        // Finance
        $fin = null;
        if ($u->hasPermission('finance.view')) {
            $fin = [
                'cash' => (float) DB::table('cash_accounts')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->sum('balance'),
                'unposted' => DB::table('journals')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->where('status', 'draft')->count()
                    + DB::table('vendor_invoices')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->where('status', 'draft')->count(),
                'overdue_ar' => (float) DB::table('invoices')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))
                    ->whereRaw('grand_total - paid_total > 0')->where('due_date', '<', now()->toDateString())->sum(DB::raw('grand_total - paid_total')),
                'period' => DB::table('fiscal_periods')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->orderByDesc('start_date')->first(),
            ];
        }
        // Operations
        $ops = null;
        if ($u->hasPermission('inventory.view') || $u->hasPermission('sales.view')) {
            $ops = [
                'open_so' => DB::table('sales_orders')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->whereIn('status', ['draft', 'approved'])->count(),
                'groom_queue' => DB::table('grooming_orders')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->whereIn('status', ['queued', 'in_progress'])->count(),
                'boarding_in' => DB::table('boarding_reservations')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->where('status', 'checked_in')->count(),
                'pending_po' => DB::table('purchase_orders')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->whereIn('status', ['draft', 'approved', 'partial'])->count(),
            ];
        }
        // HR
        $hr = null;
        if ($u->hasPermission('hr.view')) {
            $hr = [
                'active_emp' => DB::table('employees')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->where('status', 'active')->count(),
                'present_today' => DB::table('attendances')->join('employees', 'employees.id', '=', 'attendances.employee_id')
                    ->when($eid, fn ($q) => $q->where('employees.legal_entity_id', $eid))->where('attendances.date', now()->toDateString())->count(),
                'pending_leave' => DB::table('leave_requests')->join('employees', 'employees.id', '=', 'leave_requests.employee_id')
                    ->when($eid, fn ($q) => $q->where('employees.legal_entity_id', $eid))->where('leave_requests.status', 'pending')->count(),
                'payroll_draft' => DB::table('payrolls')->when($eid, fn ($q) => $q->where('legal_entity_id', $eid))->where('status', 'draft')->count(),
            ];
        }

        $entityCode = $eid ? DB::table('legal_entities')->where('id', $eid)->value('code') : null;

        return view('dashboard', compact('salesMonth', 'receivable', 'payable', 'lowStock', 'pendingApprovals', 'pendingList', 'lowItems', 'orders', 'perEntity', 'eid', 'bid', 'entityCode', 'trend', 'fin', 'ops', 'hr'));
    }

    public function health()
    {
        return response()->json(['ok' => true, 'time' => now()->toIso8601String()]);
    }
}
