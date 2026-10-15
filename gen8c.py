"""Generator 8c: line models, relations, controller scope fix, helpers autoload."""
import os, re
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

LINES = [
 ("Sales","QuotationLine","quotation_lines",["quotation_id","product_id","snapshot_name","qty","price","discount","tax_rate","line_total"]),
 ("Sales","SalesOrderLine","sales_order_lines",["sales_order_id","product_id","variant_id","snapshot_name","qty","price","discount","tax_rate","line_total"]),
 ("Sales","DeliveryOrderLine","delivery_order_lines",["delivery_order_id","product_id","variant_id","snapshot_name","qty","price","discount","tax_rate","line_total"]),
 ("Sales","InvoiceLine","invoice_lines",["invoice_id","product_id","variant_id","snapshot_name","qty","price","discount","tax_rate","line_total"]),
 ("Sales","CreditNoteLine","credit_note_lines",["credit_note_id","product_id","variant_id","snapshot_name","qty","price","discount","tax_rate","line_total"]),
 ("Sales","SalesReturnLine","sales_return_lines",["sales_return_id","product_id","variant_id","snapshot_name","qty","price","discount","tax_rate","line_total"]),
 ("Purchasing","PurchaseRequisitionLine","purchase_requisition_lines",["purchase_requisition_id","product_id","qty","est_price"]),
 ("Purchasing","PurchaseOrderLine","purchase_order_lines",["purchase_order_id","product_id","qty","qty_received","price","line_total"]),
 ("Purchasing","VendorInvoiceLine","vendor_invoice_lines",["vendor_invoice_id","product_id","qty","price","line_total"]),
 ("Purchasing","PurchaseReturnLine","purchase_return_lines",["purchase_return_id","product_id","qty","price"]),
 ("Inventory","GoodsReceiptLine","goods_receipt_lines",["goods_receipt_id","product_id","batch_id","qty_ordered","qty_received","unit_cost"]),
 ("Inventory","GoodsIssueLine","goods_issue_lines",["goods_issue_id","product_id","batch_id","qty"]),
 ("Inventory","StockTransferLine","stock_transfer_lines",["stock_transfer_id","product_id","qty_sent","qty_received"]),
 ("Inventory","StockCountLine","stock_count_lines",["stock_count_id","product_id","qty_system","qty_actual"]),
 ("Inventory","StockAdjustmentLine","stock_adjustment_lines",["stock_adjustment_id","product_id","qty","unit_cost"]),
 ("Finance","BudgetLine","budget_lines",["budget_id","coa_id","amount"]),
 ("Hr","PayrollLine","payroll_lines",["payroll_id","employee_id","gross","deduction","net","breakdown"]),
 ("Mfg","BomLine","bom_lines",["bom_id","material_id","qty"]),
 ("Mfg","WorkOrderLine","work_order_lines",["work_order_id","material_id","qty_required","qty_issued"]),
]
for ns, cls, tbl, fill in LINES:
    fill_s = ", ".join([f"'{f}'" for f in fill])
    w(f"app/Models/{ns}/{cls}.php", f"""<?php
namespace App\\Models\\{ns};
use Illuminate\\Database\\Eloquent\\Model;
class {cls} extends Model {{
    protected $table = '{tbl}';
    protected $fillable = [{fill_s}];
    public function product() {{ return $this->belongsTo(\\App\\Models\\Master\\Product::class, 'product_id'); }}
}}
""")

RELS = {
 "app/Models/Sales/Quotation.php": """
    public function customer() { return $this->belongsTo(\\App\\Models\\Master\\Customer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(\\App\\Models\\Sales\\QuotationLine::class, 'quotation_id'); }""",
 "app/Models/Sales/SalesOrder.php": """
    public function customer() { return $this->belongsTo(\\App\\Models\\Master\\Customer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(\\App\\Models\\Sales\\SalesOrderLine::class, 'sales_order_id'); }""",
 "app/Models/Sales/DeliveryOrder.php": """
    public function customer() { return $this->belongsTo(\\App\\Models\\Master\\Customer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(\\App\\Models\\Sales\\DeliveryOrderLine::class, 'delivery_order_id'); }""",
 "app/Models/Sales/Invoice.php": """
    public function customer() { return $this->belongsTo(\\App\\Models\\Master\\Customer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(\\App\\Models\\Sales\\InvoiceLine::class, 'invoice_id'); }
    public function payments() { return $this->hasMany(\\App\\Models\\Sales\\SalesPayment::class, 'invoice_id'); }""",
 "app/Models/Sales/CreditNote.php": """
    public function customer() { return $this->belongsTo(\\App\\Models\\Master\\Customer::class, 'customer_id'); }""",
 "app/Models/Sales/SalesReturn.php": """
    public function customer() { return $this->belongsTo(\\App\\Models\\Master\\Customer::class, 'customer_id'); }""",
 "app/Models/Purchasing/PurchaseOrder.php": """
    public function lines() { return $this->hasMany(\\App\\Models\\Purchasing\\PurchaseOrderLine::class, 'purchase_order_id'); }
    public function supplier() { return $this->belongsTo(\\App\\Models\\Master\\Supplier::class, 'supplier_id'); }""",
 "app/Models/Finance/Journal.php": """
    public function lines() { return $this->hasMany(\\App\\Models\\Finance\\JournalLine::class, 'journal_id'); }""",
 "app/Models/Hr/Payroll.php": """
    public function lines() { return $this->hasMany(\\App\\Models\\Hr\\PayrollLine::class, 'payroll_id'); }""",
 "app/Models/Pet/BoardingReservation.php": """
    public function pet() { return $this->belongsTo(\\App\\Models\\Pet\\Pet::class, 'pet_id'); }
    public function room() { return $this->belongsTo(\\App\\Models\\Pet\\BoardingRoom::class, 'boarding_room_id'); }""",
 "app/Models/Pet/Pet.php": """
    public function owner() { return $this->belongsTo(\\App\\Models\\Pet\\PetOwner::class, 'pet_owner_id'); }""",
 "app/Models/Pet/GroomingOrder.php": """
    public function pet() { return $this->belongsTo(\\App\\Models\\Pet\\Pet::class, 'pet_id'); }""",
 "app/Models/Rental/RentalReservation.php": """
    public function unit() { return $this->belongsTo(\\App\\Models\\Rental\\RentalUnit::class, 'rental_unit_id'); }
    public function customer() { return $this->belongsTo(\\App\\Models\\Master\\Customer::class, 'customer_id'); }""",
}
for rel, methods in RELS.items():
    p = os.path.join(BASE, rel)
    s = open(p, encoding="utf-8").read()
    s = s.rstrip()
    assert s.endswith("}")
    s = s[:-1] + methods + "\n}\n"
    open(p, "w", encoding="utf-8", newline="\n").write(s)
    print("patched", rel)

# fix entityField null -> legal_entity_id for tables that have it
HAVE_ENTITY = {"OwnerController","VetAppointmentController","GroomingOrderController","BoardingReservationController",
 "TransportOrderController","DesignController","BomController","WorkOrderController","LotController","ProjectController",
 "CatalogController","ReservationController","CampaignController","VoucherController"}
import glob
for f in glob.glob(os.path.join(BASE, "app/Http/Controllers/*/*.php")):
    s = open(f, encoding="utf-8").read()
    name = os.path.basename(f)
    if name in HAVE_ENTITY and "protected ?string $entityField = null;" in s:
        s = s.replace("protected ?string $entityField = null;", "protected ?string $entityField = 'legal_entity_id';")
        open(f, "w", encoding="utf-8", newline="\n").write(s)
        print("scope-fixed", name)

# composer helpers autoload
p = os.path.join(BASE, "composer.json")
s = open(p, encoding="utf-8").read()
if "app/Support/helpers.php" not in s:
    s = s.replace('"autoload": {', '"autoload": {\n        "files": ["app/Support/helpers.php"],')
    open(p, "w", encoding="utf-8", newline="\n").write(s)
    print("composer helpers registered")
print("gen8c done")
