"""Fix part 3: secondary docs, vendor invoice, SO cancel."""
import os, glob as _g
BASE = r"D:\project laravel\kazaerp"
def patch(rel, old, new):
    p = os.path.join(BASE, rel)
    s = open(p, encoding="utf-8").read()
    assert old in s, f"ANCHOR MISSING in {rel}: {old[:70]}"
    assert s.count(old) == 1, f"ANCHOR NOT UNIQUE in {rel}"
    open(p, "w", encoding="utf-8", newline="\n").write(s.replace(old, new))
    print("patched", rel)

for _f in _g.glob(os.path.join(BASE, "app/Http/Controllers/Sales/*.php")):
    if os.path.basename(_f) not in ("QuotationController.php","DeliveryOrderController.php","CreditNoteController.php","SalesReturnController.php"):
        continue
    _s = open(_f, encoding="utf-8").read()
    _old = "        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {"
    _tail = "            return $h->id;\n        });"
    if _old in _s and "} catch (\\Throwable $e) {" not in _s:
        _s = _s.replace(_old, "        try {\n" + _old)
        assert _tail in _s, _f
        _s = _s.replace(_tail, "            return $h->id;\n        });\n        } catch (\\Throwable $e) {\n            return back()->withErrors(['msg' => $e->getMessage()])->withInput();\n        }")
        open(_f, "w", encoding="utf-8", newline="\n").write(_s)
        print("wrapped secondary", os.path.basename(_f))

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

patch("app/Http/Controllers/Sales/SalesOrderController.php",
"""        if ($row->status==='invoiced') abort(422,'SO yang sudah menjadi invoice tidak dapat dibatalkan langsung.');
        $this->setStatus($row,$row->status,'cancelled','Dibatalkan');""",
"""        if ($row->status==='invoiced') abort(422,'SO yang sudah menjadi invoice tidak dapat dibatalkan langsung.');
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
