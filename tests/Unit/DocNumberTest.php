<?php
namespace Tests\Unit;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
class DocNumberTest extends TestCase {
    public function test_sequence_naik_monotonik(): void {
        $a = \App\Services\DocNumberService::next('SO',$this->eid,$this->bid,'E','B');
        $b = \App\Services\DocNumberService::next('SO',$this->eid,$this->bid,'E','B');
        $this->assertNotEquals($a,$b);
        $this->assertStringContainsString('/SO/', $a);
    }
}
