<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
class FinancePostingTest extends TestCase {
    public function test_jurnal_harus_balance_dan_idempotent(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $coaCash = DB::table('coas')->where(['legal_entity_id'=>$this->eid,'code'=>'1000'])->value('id');
        $coaRev = DB::table('coas')->where(['legal_entity_id'=>$this->eid,'code'=>'4000'])->value('id');
        // tidak balance => error
        $this->post('/finance/journals',['date'=>'2026-10-15','description'=>'x',
            'lines'=>[['coa_id'=>$coaCash,'debit'=>100,'credit'=>0],['coa_id'=>$coaRev,'debit'=>0,'credit'=>50]]])->assertSessionHasErrors();
        // idempotency key sama => 1 jurnal
        $id1 = \App\Services\PostingService::postJournal(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid,'date'=>'2026-10-15','number'=>'J-TEST-1','description'=>'t','source_type'=>'test','source_id'=>1],
            [['coa_id'=>$coaCash,'debit'=>100,'credit'=>0],['coa_id'=>$coaRev,'debit'=>0,'credit'=>100]],'idem-1');
        $id2 = \App\Services\PostingService::postJournal(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid,'date'=>'2026-10-15','number'=>'J-TEST-1b','description'=>'t','source_type'=>'test','source_id'=>1],
            [['coa_id'=>$coaCash,'debit'=>100,'credit'=>0],['coa_id'=>$coaRev,'debit'=>0,'credit'=>100]],'idem-1');
        $this->assertEquals($id1,$id2);
        // reversal membuat jurnal lawan, bukan hapus
        \App\Services\PostingService::reverse($id1,'koreksi');
        $this->assertEquals('reversed', DB::table('journals')->where('id',$id1)->value('status'));
        // periode tutup menolak posting
        DB::table('fiscal_periods')->where('legal_entity_id',$this->eid)->update(['is_closed'=>true]);
        $this->expectException(\RuntimeException::class);
        \App\Services\PostingService::postJournal(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid,'date'=>'2026-10-15','number'=>'J-TEST-2','description'=>'t'],
            [['coa_id'=>$coaCash,'debit'=>10,'credit'=>0],['coa_id'=>$coaRev,'debit'=>0,'credit'=>10]],'idem-2');
    }
    public function test_segregation_of_duties(): void {
        $staff = $this->makeUser('finance_staff');
        $this->actingAs($staff)->withSession(['erp_context'=>$this->ctx()]);
        // beri hak menyetujui agar sampai ke guard SoD (bukan ditolak di middleware)
        $rid = DB::table('roles')->where('name','finance_staff')->value('id');
        $pid = DB::table('permissions')->where('name','governance.approve')->value('id');
        DB::table('permission_role')->updateOrInsert(['role_id'=>$rid,'permission_id'=>$pid],[]);
        $ap = DB::table('approvals')->insertGetId(['doc_type'=>'expense','doc_id'=>1,'level'=>1,'status'=>'pending','requested_by'=>$staff->id,'created_at'=>now(),'updated_at'=>now()]);
        $this->post('/gov/approvals/'.$ap,['decision'=>'approved'])->assertSessionHasErrors();
        // orang lain boleh menyetujui
        $other = $this->makeUser('finance_controller');
        $this->actingAs($other)->withSession(['erp_context'=>$this->ctx()]);
        $rid2 = DB::table('roles')->where('name','finance_controller')->value('id');
        DB::table('permission_role')->updateOrInsert(['role_id'=>$rid2,'permission_id'=>$pid],[]);
        $this->post('/gov/approvals/'.$ap,['decision'=>'approved'])->assertRedirect();
        $this->assertEquals('approved', DB::table('approvals')->where('id',$ap)->value('status'));
    }
}
