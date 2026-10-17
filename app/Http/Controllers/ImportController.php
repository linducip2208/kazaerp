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
        if (!is_array($head) || !in_array('code', $head, true) || !in_array('name', $head, true)) {
            if (is_resource($h)) fclose($h);
            return back()->withErrors(['msg'=>'Header CSV tidak valid. Wajib ada kolom: code,name (opsional: phone,email).']);
        }
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $ln=1; $rows=0;
        while (($row=fgetcsv($h))!==false) {
            $ln++; if (++$rows > 2000) { $errs[]="Dibatasi 2000 baris per file; sisa baris diabaikan."; break; }
            if (count($row) !== count($head)) { $errs[]="Baris $ln: jumlah kolom tidak sesuai header"; continue; }
            $d = array_combine($head,$row);
            if (empty($d['code'])||empty($d['name'])) { $errs[]="Baris $ln: code/nama wajib"; continue; }
            if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) { $errs[]="Baris $ln: email tidak valid"; continue; }
            if (DB::table('customers')->where(['legal_entity_id'=>$eid,'code'=>$d['code']])->exists()) { $errs[]="Baris $ln: kode {$d['code']} duplikat"; continue; }
            DB::table('customers')->insert(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,'code'=>$d['code'],'name'=>$d['name'],'phone'=>$d['phone'] ?? null,'email'=>$d['email'] ?? null,'tier'=>'retail','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            $ok++;
        }
        fclose($h);
        \App\Services\AuditService::log('import','customers',0,['entity'=>$eid,'ok'=>$ok,'failed'=>count($errs)]);
        return back()->with('ok',"Import selesai: $ok berhasil, ".count($errs)." gagal.")->with('import_errors',$errs);
    }
    public function products() { return view('modules.import.products'); }
    public function productsStore(Request $r) {
        $r->validate(['file'=>'required|file|mimes:csv,txt|max:2048']);
        $path = $r->file('file')->getRealPath();
        $h = fopen($path,'r'); $head = fgetcsv($h); $ok=0; $errs=[];
        if (!is_array($head) || !in_array('sku', $head, true) || !in_array('name', $head, true) || !in_array('sell_price', $head, true)) {
            if (is_resource($h)) fclose($h);
            return back()->withErrors(['msg'=>'Header CSV tidak valid. Wajib ada kolom: sku,name,sell_price (opsional: cost_price).']);
        }
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $ln=1; $rows=0;
        while (($row=fgetcsv($h))!==false) {
            $ln++; if (++$rows > 2000) { $errs[]="Dibatasi 2000 baris per file; sisa baris diabaikan."; break; }
            if (count($row) !== count($head)) { $errs[]="Baris $ln: jumlah kolom tidak sesuai header"; continue; }
            $d = array_combine($head,$row);
            if (empty($d['sku'])||empty($d['name'])) { $errs[]="Baris $ln: sku/nama wajib"; continue; }
            if (!is_numeric($d['sell_price'] ?? null)) { $errs[]="Baris $ln: harga tidak valid"; continue; }
            if (DB::table('products')->where(['legal_entity_id'=>$eid,'sku'=>$d['sku']])->exists()) { $errs[]="Baris $ln: sku duplikat"; continue; }
            DB::table('products')->insert(['legal_entity_id'=>$eid,'sku'=>$d['sku'],'name'=>$d['name'],'kind'=>'goods','cost_price'=>$d['cost_price'] ?? 0,'sell_price'=>$d['sell_price'],'is_sellable'=>true,'is_purchasable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            $ok++;
        }
        fclose($h);
        \App\Services\AuditService::log('import','products',0,['entity'=>$eid,'ok'=>$ok,'failed'=>count($errs)]);
        return back()->with('ok',"Import selesai: $ok berhasil, ".count($errs)." gagal.")->with('import_errors',$errs);
    }
}
