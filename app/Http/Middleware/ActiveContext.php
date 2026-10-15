<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class ActiveContext {
    public function handle(Request $request, Closure $next) {
        $u = $request->user();
        if ($u && !session()->has('erp_context')) {
            session(['erp_context' => [
                'holding_id' => $u->holding_id,
                'legal_entity_id' => $u->legal_entity_id,
                'branch_id' => $u->branch_id,
            ]]);
        }
        return $next($request);
    }
}
