<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ImportController extends Controller {
    public function customers() { return view('modules.import.customers'); }
    public function customersStore(Request $r) {
        $r->validate(['file'=>'required|file|mimes:csv,txt|max:2048']);
        $path = $r->file('file')->getRealPath();
        $h = fopen($path,'r'); $head = fgetcsv($h); $ok=0; $errs=[];
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $ln=1;
        while (($row=fgetcsv($h))!==false) {
            $ln++; $d = array_combine($head,$row);
            if (empty($d['code'])||empty($d['name'])) { $errs[]="Baris $ln: code/nama wajib"; continue; }
            if (DB::table('customers')->where(['legal_entity_id'=>$eid,'code'=>$d['code']])->exists()) { $errs[]="Baris $ln: kode {$d['code']} duplikat"; continue; }
            DB::table('customers')->insert(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,'code'=>$d['code'],'name'=>$d['name'],'phone'=>$d['phone'] ?? null,'email'=>$d['email'] ?? null,'tier'=>'retail','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            $ok++;
        }
        fclose($h);
        return back()->with('ok',"Import selesai: $ok berhasil, ".count($errs)." gagal.")->with('import_errors',$errs);
    }
    public function products() { return view('modules.import.products'); }
    public function productsStore(Request $r) {
        $r->validate(['file'=>'required|file|mimes:csv,txt|max:2048']);
        $path = $r->file('file')->getRealPath();
        $h = fopen($path,'r'); $head = fgetcsv($h); $ok=0; $errs=[];
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $ln=1;
        while (($row=fgetcsv($h))!==false) {
            $ln++; $d = array_combine($head,$row);
            if (empty($d['sku'])||empty($d['name'])) { $errs[]="Baris $ln: sku/nama wajib"; continue; }
            if (!is_numeric($d['sell_price'] ?? null)) { $errs[]="Baris $ln: harga tidak valid"; continue; }
            if (DB::table('products')->where(['legal_entity_id'=>$eid,'sku'=>$d['sku']])->exists()) { $errs[]="Baris $ln: sku duplikat"; continue; }
            DB::table('products')->insert(['legal_entity_id'=>$eid,'sku'=>$d['sku'],'name'=>$d['name'],'kind'=>'goods','cost_price'=>$d['cost_price'] ?? 0,'sell_price'=>$d['sell_price'],'is_sellable'=>true,'is_purchasable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            $ok++;
        }
        fclose($h);
        return back()->with('ok',"Import selesai: $ok berhasil, ".count($errs)." gagal.")->with('import_errors',$errs);
    }
}
