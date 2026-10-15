<?php
namespace App\Http\Controllers\Concerns;
use Illuminate\Support\Facades\DB;
trait DocLines {
    protected function computeLines($products, array $lines): array {
        $sub=0; $rows=[];
        foreach ($lines as $l) {
            $p = $products[$l['product_id']] ?? null;
            if (!$p) throw new \RuntimeException('Produk tidak valid.');
            $lt=(float)$l['qty']*(float)$l['price']; $sub+=$lt;
            $rows[]=['product_id'=>$p->id,'snapshot_name'=>$p->name,'qty'=>$l['qty'],'price'=>$l['price'],
                'discount'=>0,'tax_rate'=>11,'line_total'=>$lt*1.11,'created_at'=>now(),'updated_at'=>now()];
        }
        $tax=$sub*0.11;
        return [$rows,$sub,$tax,$sub+$tax];
    }
}
