"""Fix: convert RuntimeException in transactions to friendly validation errors."""
import os
BASE = r"D:\project laravel\kazaerp"
def patch(rel, old, new):
    p = os.path.join(BASE, rel)
    s = open(p, encoding="utf-8").read()
    assert old in s, f"ANCHOR MISSING in {rel}: {old[:70]}"
    assert s.count(old) == 1, f"ANCHOR NOT UNIQUE in {rel}"
    open(p, "w", encoding="utf-8", newline="\n").write(s.replace(old, new))
    print("patched", rel)

CATCH = """        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }"""

# ---- InvoiceController::store ----
patch("app/Http/Controllers/Sales/InvoiceController.php",
"""        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""",
"""        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""")
patch("app/Http/Controllers/Sales/InvoiceController.php",
"""            return $inv->id;
        });
        AuditService::log('create','invoices',(int)$id,[]);""",
"""            return $inv->id;
        });
""" + CATCH + """
        AuditService::log('create','invoices',(int)$id,[]);""")

# ---- InvoiceController::pay ----
patch("app/Http/Controllers/Sales/InvoiceController.php",
"""        $inv = $this->baseQuery()->findOrFail($id);
        DB::transaction(function() use ($r,$inv) {""",
"""        $inv = $this->baseQuery()->findOrFail($id);
        try {
        DB::transaction(function() use ($r,$inv) {""")
patch("app/Http/Controllers/Sales/InvoiceController.php",
"""                    [['coa_id'=>$coa('1000'),'debit'=>(float)$r->amount,'credit'=>0],['coa_id'=>$coa('1200'),'debit'=>0,'credit'=>(float)$r->amount]],'pay-'.$number);
            }
        });
        return back()->with('ok','Pembayaran dicatat. Status lunas dihitung dari total, bukan dari form.');""",
"""                    [['coa_id'=>$coa('1000'),'debit'=>(float)$r->amount,'credit'=>0],['coa_id'=>$coa('1200'),'debit'=>0,'credit'=>(float)$r->amount]],'pay-'.$number);
            }
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Pembayaran dicatat. Status lunas dihitung dari total, bukan dari form.');""")

# ---- SalesOrderController::store ----
patch("app/Http/Controllers/Sales/SalesOrderController.php",
"""        $cust = \\App\\Models\\Master\\Customer::findOrFail($r->customer_id);
        if ((int)$cust->legal_entity_id !== (int)$eid) abort(403,'Pelanggan beda entitas.');
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""",
"""        $cust = \\App\\Models\\Master\\Customer::findOrFail($r->customer_id);
        if ((int)$cust->legal_entity_id !== (int)$eid) abort(403,'Pelanggan beda entitas.');
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""")
patch("app/Http/Controllers/Sales/SalesOrderController.php",
"""            return $so->id;
        });
        AuditService::log('create','sales_orders',(int)$id,[]);""",
"""            return $so->id;
        });
""" + CATCH + """
        AuditService::log('create','sales_orders',(int)$id,[]);""")

# ---- SalesOrderController::approve + cancel (setStatus throws) ----
patch("app/Http/Controllers/Sales/SalesOrderController.php",
"""    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->setStatus($row,'draft','approved','Disetujui');""",
"""    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        try {
        $this->setStatus($row,'draft','approved','Disetujui');""")
patch("app/Http/Controllers/Sales/SalesOrderController.php",
"""            DB::table('stock_reservations')->insert(['warehouse_id'=>$wh,'product_id'=>$l->product_id,'ref_type'=>'sales_order','ref_id'=>$row->id,'qty'=>$l->qty,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
            }
        }
        return back()->with('ok','SO disetujui + stok direservasi.');""",
"""            DB::table('stock_reservations')->insert(['warehouse_id'=>$wh,'product_id'=>$l->product_id,'ref_type'=>'sales_order','ref_id'=>$row->id,'qty'=>$l->qty,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
            }
        }
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','SO disetujui + stok direservasi.');""")

# ---- PurchaseOrderController::store ----
patch("app/Http/Controllers/Purchasing/PurchaseOrderController.php",
"""        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""",
"""        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""")
patch("app/Http/Controllers/Purchasing/PurchaseOrderController.php",
"""            ApprovalService::request('purchase_order',$po->id,$eid,$grand);
            return $po->id;
        });
        return redirect()->route('purchasing.orders.show',$id)->with('ok','PO dibuat + approval diminta.');""",
"""            ApprovalService::request('purchase_order',$po->id,$eid,$grand);
            return $po->id;
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('purchasing.orders.show',$id)->with('ok','PO dibuat + approval diminta.');""")

# ---- PurchaseOrderController::approve ----
patch("app/Http/Controllers/Purchasing/PurchaseOrderController.php",
"""    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $ap = DB::table('approvals')->where(['doc_type'=>'purchase_order','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
        if ($ap) ApprovalService::decide($ap->id,'approved','OK');
        $this->setStatus($row,'draft','approved');
        return back()->with('ok','PO disetujui.');""",
"""    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        try {
            $ap = DB::table('approvals')->where(['doc_type'=>'purchase_order','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
            if ($ap) ApprovalService::decide($ap->id,'approved','OK');
            $this->setStatus($row,'draft','approved');
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','PO disetujui.');""")

# ---- ReceiptController::store ----
patch("app/Http/Controllers/Inventory/ReceiptController.php",
"""        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        DB::transaction(function() use ($r,$eid,$u) {""",
"""        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        try {
        DB::transaction(function() use ($r,$eid,$u) {""")
patch("app/Http/Controllers/Inventory/ReceiptController.php",
"""            if ($r->purchase_order_id) {
                $tot = DB::table('purchase_order_lines')->where('purchase_order_id',$r->purchase_order_id)->selectRaw('SUM(qty) t, SUM(qty_received) r')->first();
                DB::table('purchase_orders')->where('id',$r->purchase_order_id)->update(['status'=> ((float)$tot->r >= (float)$tot->t ? 'received' : 'partial')]);
            }
        });
        return redirect()->route('inventory.stock.index')->with('ok','Penerimaan dicatat ke ledger + saldo (mendukung partial).');""",
"""            if ($r->purchase_order_id) {
                $tot = DB::table('purchase_order_lines')->where('purchase_order_id',$r->purchase_order_id)->selectRaw('SUM(qty) t, SUM(qty_received) r')->first();
                DB::table('purchase_orders')->where('id',$r->purchase_order_id)->update(['status'=> ((float)$tot->r >= (float)$tot->t ? 'received' : 'partial')]);
            }
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('inventory.stock.index')->with('ok','Penerimaan dicatat ke ledger + saldo (mendukung partial).');""")

# ---- JournalController::store + reverse ----
patch("app/Http/Controllers/Finance/JournalController.php",
"""        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""",
"""        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""")
patch("app/Http/Controllers/Finance/JournalController.php",
"""            return PostingService::postJournal(['legal_entity_id'=>$eid,'branch_id'=>$bid,'date'=>$r->date,'number'=>$number,
                'description'=>$r->description,'source_type'=>'manual','source_id'=>null],$lines,'manual-'.$number);
        });
        return redirect()->route('finance.journals.index')->with('ok','Jurnal terposting (balance + idempotent).');""",
"""            return PostingService::postJournal(['legal_entity_id'=>$eid,'branch_id'=>$bid,'date'=>$r->date,'number'=>$number,
                'description'=>$r->description,'source_type'=>'manual','source_id'=>null],$lines,'manual-'.$number);
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('finance.journals.index')->with('ok','Jurnal terposting (balance + idempotent).');""")
patch("app/Http/Controllers/Finance/JournalController.php",
"""    public function reverse(Request $r, $id) {
        $r->validate(['reason'=>'required|max:255']);
        PostingService::reverse((int)$id, $r->reason);
        return back()->with('ok','Jurnal direversal (bukan dihapus).');""",
"""    public function reverse(Request $r, $id) {
        $r->validate(['reason'=>'required|max:255']);
        try {
            PostingService::reverse((int)$id, $r->reason);
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Jurnal direversal (bukan dihapus).');""")

# ---- Gov ApprovalController::decide ----
patch("app/Http/Controllers/Gov/ApprovalController.php",
"""    public function decide(Request $r, $id) {
        $r->validate(['decision'=>'required|in:approved,rejected','note'=>'nullable|max:500']);
        ApprovalService::decide((int)$id, $r->decision, $r->note);
        return back()->with('ok','Keputusan approval dicatat.');""",
"""    public function decide(Request $r, $id) {
        $r->validate(['decision'=>'required|in:approved,rejected','note'=>'nullable|max:500']);
        try {
            ApprovalService::decide((int)$id, $r->decision, $r->note);
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Keputusan approval dicatat.');""")

# ---- PosController::checkout ----
patch("app/Http/Controllers/Sales/PosController.php",
"""        $shift = DB::table('pos_shifts')->where(['cashier_id'=>$u->id,'status'=>'open'])->first();
        if (!$shift) return back()->withErrors(['msg'=>'Buka shift kasir terlebih dahulu.']);
        DB::transaction(function() use ($r,$eid,$bid,$u,$shift) {""",
"""        $shift = DB::table('pos_shifts')->where(['cashier_id'=>$u->id,'status'=>'open'])->first();
        if (!$shift) return back()->withErrors(['msg'=>'Buka shift kasir terlebih dahulu.']);
        try {
        DB::transaction(function() use ($r,$eid,$bid,$u,$shift) {""")
patch("app/Http/Controllers/Sales/PosController.php",
"""            foreach ($lns as $ln) { if ($wh) StockService::move(['warehouse_id'=>$wh,'product_id'=>$ln['product_id'],'qty'=>-1*(float)$ln['qty'],'ref_type'=>'pos_sale','ref_id'=>$saleId]); }
        });
        return back()->with('ok','Transaksi POS berhasil.');""",
"""            foreach ($lns as $ln) { if ($wh) StockService::move(['warehouse_id'=>$wh,'product_id'=>$ln['product_id'],'qty'=>-1*(float)$ln['qty'],'ref_type'=>'pos_sale','ref_id'=>$saleId]); }
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Transaksi POS berhasil.');""")

# ---- PayrollController::store + approve ----
patch("app/Http/Controllers/Hr/PayrollController.php",
"""        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $id = DB::transaction(function() use ($r,$eid,$u) {""",
"""        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$u) {""")
patch("app/Http/Controllers/Hr/PayrollController.php",
"""            ApprovalService::request('payroll',$pid,$eid,$total);
            return $pid;
        });
        return redirect()->route('hr.payroll.index')->with('ok','Payroll dihitung dari komponen configurable (bukan aturan hardcode).');""",
"""            ApprovalService::request('payroll',$pid,$eid,$total);
            return $pid;
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('hr.payroll.index')->with('ok','Payroll dihitung dari komponen configurable (bukan aturan hardcode).');""")
patch("app/Http/Controllers/Hr/PayrollController.php",
"""        $ap = DB::table('approvals')->where(['doc_type'=>'payroll','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
        if ($ap) ApprovalService::decide($ap->id,'approved','OK');
        DB::table('payrolls')->where('id',$id)->update(['status'=>'approved','approved_by'=>auth()->id()]);
        return back()->with('ok','Payroll disetujui (terkunci dari perubahan diam-diam).');""",
"""        try {
            $ap = DB::table('approvals')->where(['doc_type'=>'payroll','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
            if ($ap) ApprovalService::decide($ap->id,'approved','OK');
            DB::table('payrolls')->where('id',$id)->update(['status'=>'approved','approved_by'=>auth()->id()]);
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Payroll disetujui (terkunci dari perubahan diam-diam).');""")

# ---- RequisitionController::store ----
patch("app/Http/Controllers/Purchasing/RequisitionController.php",
"""        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $id = DB::transaction(function() use ($r,$eid,$u,$ctx) {""",
"""        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$u,$ctx) {""")
patch("app/Http/Controllers/Purchasing/RequisitionController.php",
"""            ApprovalService::request('purchase_requisition',$rid,$eid,$total);
            return $rid;
        });
        return redirect()->route('purchasing.requisitions.index')->with('ok','Permintaan pembelian diajukan (darurat ditandai + butuh review).');""",
"""            ApprovalService::request('purchase_requisition',$rid,$eid,$total);
            return $rid;
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('purchasing.requisitions.index')->with('ok','Permintaan pembelian diajukan (darurat ditandai + butuh review).');""")

# ---- ExpenseController::store + approve ----
patch("app/Http/Controllers/Finance/ExpenseController.php",
"""        $row = (new ($this->model))::create($v);
        ApprovalService::request('expense',$row->id,(int)$row->legal_entity_id,(float)$row->amount);
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok','Expense dibuat + approval diminta.');""",
"""        try {
            $row = (new ($this->model))::create($v);
            ApprovalService::request('expense',$row->id,(int)$row->legal_entity_id,(float)$row->amount);
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok','Expense dibuat + approval diminta.');""")
patch("app/Http/Controllers/Finance/ExpenseController.php",
"""    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $ap = DB::table('approvals')->where(['doc_type'=>'expense','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
        if ($ap) ApprovalService::decide($ap->id,'approved','OK');
        $this->setStatus($row,'draft','approved');
        return back()->with('ok','Expense disetujui.');""",
"""    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        try {
            $ap = DB::table('approvals')->where(['doc_type'=>'expense','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
            if ($ap) ApprovalService::decide($ap->id,'approved','OK');
            $this->setStatus($row,'draft','approved');
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Expense disetujui.');""")

# ---- CrudController::update + destroy ----
patch("app/Http/Controllers/CrudController.php",
"""    public function update(Request $r, $id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->guardPosted($row);
        $v = $r->validate($this->rules);
        $before = $row->toArray();
        $row->update($v);
        AuditService::log('update', $row->getTable(), $row->id, ['before'=>$before,'after'=>$row->fresh()->toArray()]);
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok',$this->title.' berhasil diperbarui.');""",
"""    public function update(Request $r, $id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->guardPosted($row);
        $v = $r->validate($this->rules);
        try {
            $before = $row->toArray();
            $row->update($v);
            AuditService::log('update', $row->getTable(), $row->id, ['before'=>$before,'after'=>$row->fresh()->toArray()]);
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()])->withInput();
        }
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok',$this->title.' berhasil diperbarui.');""")
patch("app/Http/Controllers/CrudController.php",
"""    public function destroy($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->guardPosted($row);
        if (in_array('Illuminate\\\\Database\\\\Eloquent\\\\SoftDeletes', class_uses_recursive($row))) { $row->delete(); }""",
"""    public function destroy($id) {
        $row = $this->baseQuery()->findOrFail($id);
        try {
        $this->guardPosted($row);
        if (in_array('Illuminate\\\\Database\\\\Eloquent\\\\SoftDeletes', class_uses_recursive($row))) { $row->delete(); }""")
patch("app/Http/Controllers/CrudController.php",
"""            $row->delete();
        }
        AuditService::log('delete', $row->getTable(), (int)$id, []);
        return redirect()->route($this->routePrefix.'.index')->with('ok',$this->title.' dihapus.');""",
"""            $row->delete();
        }
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()]);
        }
        AuditService::log('delete', $row->getTable(), (int)$id, []);
        return redirect()->route($this->routePrefix.'.index')->with('ok',$this->title.' dihapus.');""")

print("fix part 2 done")

# ---- secondary doc controllers generated by loop: wrap store transaction ----
import glob as _g
for _f in _g.glob(os.path.join(BASE, "app/Http/Controllers/Sales/*.php")):
    _s = open(_f, encoding="utf-8").read()
    _old = """        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {"""
    if os.path.basename(_f) in ("QuotationController.php","DeliveryOrderController.php","CreditNoteController.php","SalesReturnController.php") and _old in _s:
        _s = _s.replace(_old, """        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {""")
        _tail = """            return $h->id;
        });"""
        assert _tail in _s, _f
        _s = _s.replace(_tail, """            return $h->id;
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }""")
        open(_f, "w", encoding="utf-8", newline="\n").write(_s)
        print("wrapped secondary", os.path.basename(_f))

# ---- VendorInvoiceController::store ----
patch("app/Http/Controllers/Purchasing/VendorInvoiceController.php",
"""        $id = DB::transaction(function() use ($r,$po,$eid,$u) {""",
"""        try {
        $id = DB::transaction(function() use ($r,$po,$eid,$u) {""")
patch("app/Http/Controllers/Purchasing/VendorInvoiceController.php",
"""            return $vid;
        });
        return redirect()->route('purchasing.vinvoices.index')->with('ok','Vendor invoice dicatat (three-way matching otomatis; exception => HOLD).');""",
"""            return $vid;
        });
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('purchasing.vinvoices.index')->with('ok','Vendor invoice dicatat (three-way matching otomatis; exception => HOLD).');""")

# ---- SalesOrderController::cancel ----
patch("app/Http/Controllers/Sales/SalesOrderController.php",
"""    public function cancel($id) {
        $row = $this->baseQuery()->findOrFail($id);
        if ($row->status==='invoiced') abort(422,'SO yang sudah menjadi invoice tidak dapat dibatalkan langsung.');
        $this->setStatus($row,$row->status,'cancelled','Dibatalkan');""",
"""    public function cancel($id) {
        $row = $this->baseQuery()->findOrFail($id);
        if ($row->status==='invoiced') abort(422,'SO yang sudah menjadi invoice tidak dapat dibatalkan langsung.');
        try {
        $this->setStatus($row,$row->status,'cancelled','Dibatalkan');""")
patch("app/Http/Controllers/Sales/SalesOrderController.php",
"""        if ($wh) { foreach (DB::table('sales_order_lines')->where('sales_order_id',$row->id)->get() as $l) { try { StockService::release($wh,$l->product_id,(float)$l->qty); } catch (\\Throwable $e) {} } }
        return back()->with('ok','SO dibatalkan, reservasi dilepas.');""",
"""        if ($wh) { foreach (DB::table('sales_order_lines')->where('sales_order_id',$row->id)->get() as $l) { try { StockService::release($wh,$l->product_id,(float)$l->qty); } catch (\\Throwable $e) {} } }
        } catch (\\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','SO dibatalkan, reservasi dilepas.');""")
print("fix part 3 done")
