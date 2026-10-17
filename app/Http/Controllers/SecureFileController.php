<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Penyimpanan privat: upload + download SELALU lewat otorisasi server.
 * Tidak ada URL publik langsung ke file pelanggan/medis/desain.
 */
class SecureFileController extends Controller
{
    protected array $disk = ['driver' => 'local', 'root' => null];

    protected function privateDisk()
    {
        return Storage::build(['driver' => 'local', 'root' => storage_path('app/private')]);
    }

    /** Apakah user boleh mengakses file milik owner_type/owner_id. */
    public static function canAccess($user, string $ownerType, int $ownerId): bool
    {
        if ($user->hasRole('super_admin', 'holding_owner', 'executive', 'auditor')) {
            return true;
        }
        // File pelanggan: hanya bila customer dalam entitas/cakupan user
        if (str_contains($ownerType, 'Customer') || str_contains($ownerType, 'PetOwner') || str_contains($ownerType, 'Studio')) {
            $custId = $ownerId;
            if (str_contains($ownerType, 'PetOwner')) {
                $custId = DB::table('pet_owners')->where('id', $ownerId)->value('customer_id');
                if (! $custId) {
                    return $user->hasPermission('pet.view');
                }
            }
            if (str_contains($ownerType, 'Studio')) {
                $custId = DB::table('studio_projects')->where('id', $ownerId)->value('customer_id');
            }
            if ($custId) {
                $c = DB::table('customers')->where('id', $custId)->first();
                if (! $c) {
                    return false;
                }

                return (int) $c->legal_entity_id === (int) $user->legal_entity_id || $user->inScope('legal_entity', (int) $c->legal_entity_id);
            }
        }
        // Rekam medis: peran klinis saja
        if (str_contains($ownerType, 'Consultation') || str_contains($ownerType, 'Pet')) {
            return $user->hasPermission('pet.treat');
        }
        // Karyawan: HR atau pemilik (user terkait)
        if (str_contains($ownerType, 'Employee')) {
            if ($user->hasPermission('hr.view')) {
                return true;
            }
            $empUser = DB::table('employees')->where('id', $ownerId)->value('user_id');

            return $empUser && (int) $empUser === (int) $user->id;
        }

        return $user->hasPermission('crm.view');
    }

    public function index()
    {
        $u = auth()->user();
        $q = DB::table('attachments')->orderByDesc('id');
        if (! $u->hasRole('super_admin', 'holding_owner', 'executive', 'auditor')) {
            // Non-privileged: hanya lampiran yang dapat diakses user ini
            // (file karyawan milik sendiri + file pelanggan dalam entitas/cakupan user).
            $eids = array_unique(array_merge([$u->legal_entity_id], $u->scopeIds('legal_entity')));
            $eids = array_values(array_filter($eids));
            $custIds = $eids
                ? DB::table('customers')->whereIn('legal_entity_id', $eids)->pluck('id')->all()
                : [];
            $ownerIds = DB::table('employees')->where('user_id', $u->id)->pluck('id')->all();
            $petOwnerIds = $custIds
                ? DB::table('pet_owners')->whereIn('customer_id', $custIds)->pluck('id')->all()
                : [];
            $studioIds = $custIds
                ? DB::table('studio_projects')->whereIn('customer_id', $custIds)->pluck('id')->all()
                : [];
            $q->where(function ($w) use ($custIds, $ownerIds, $petOwnerIds, $studioIds) {
                $w->where(function ($x) use ($ownerIds) {
                    $x->where('owner_type', 'like', '%Employee%');
                    if ($ownerIds) {
                        $x->whereIn('owner_id', $ownerIds);
                    } else {
                        $x->whereRaw('1 = 0');
                    }
                })->orWhere(function ($x) use ($custIds) {
                    $x->where('owner_type', 'like', '%Customer%');
                    if ($custIds) {
                        $x->whereIn('owner_id', $custIds);
                    } else {
                        $x->whereRaw('1 = 0');
                    }
                })->orWhere(function ($x) use ($petOwnerIds) {
                    $x->where('owner_type', 'like', '%PetOwner%');
                    if ($petOwnerIds) {
                        $x->whereIn('owner_id', $petOwnerIds);
                    } else {
                        $x->whereRaw('1 = 0');
                    }
                })->orWhere(function ($x) use ($studioIds) {
                    $x->where('owner_type', 'like', '%Studio%');
                    if ($studioIds) {
                        $x->whereIn('owner_id', $studioIds);
                    } else {
                        $x->whereRaw('1 = 0');
                    }
                });
            });
        }
        $rows = $q->paginate(20);

        return view('modules.files.index', compact('rows'));
    }

    public function store(Request $r)
    {
        $r->validate([
            'owner_type' => 'required|max:120', 'owner_id' => 'required|integer|min:1',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,csv,xls,xlsx,doc,docx,zip',
        ]);
        if (! self::canAccess($r->user(), $r->owner_type, (int) $r->owner_id)) {
            abort(403);
        }
        $f = $r->file('file');
        $safe = uniqid().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $f->getClientOriginalName());
        $path = $this->privateDisk()->putFileAs(date('Y/m'), $f, $safe);
        $id = DB::table('attachments')->insertGetId([
            'owner_type' => $r->owner_type, 'owner_id' => $r->owner_id,
            'file_path' => 'private/'.$path, 'file_name' => $f->getClientOriginalName(),
            'mime' => $f->getMimeType(), 'size' => $f->getSize(), 'version' => 1,
            'uploaded_by' => $r->user()->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('ok', 'Berkas tersimpan privat (ID '.$id.').');
    }

    public function download($id)
    {
        $a = DB::table('attachments')->where('id', $id)->firstOrFail();
        if (! self::canAccess(auth()->user(), $a->owner_type, (int) $a->owner_id)) {
            abort(403);
        }
        $rel = preg_replace('#^private/#', '', $a->file_path);
        if (! $this->privateDisk()->exists($rel)) {
            abort(404);
        }
        \App\Services\AuditService::log('download', 'attachments', (int) $id, []);

        return response()->download($this->privateDisk()->path($rel), $a->file_name);
    }
}
