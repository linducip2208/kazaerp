# User Roles & Matrix

16 role baseline (dapat dikonfigurasi via `roles` + `role_user`): super_admin, holding_owner, executive,
entity_admin, finance_controller, finance_staff, hr_manager, purchasing_manager, inventory_manager,
branch_manager, sales_staff, cashier, operations_manager, module_operator, auditor, viewer.

Permission groups: org, users, roles, crm, sales (+pos), inventory, purchasing, finance, hr, pet, mfg,
thrift, studio, rental, marketing, reports, governance. Lihat `RolesPermissionsSeeder` untuk daftar 60+ permission.

## Matriks (ringkas; ✓ = diberikan default)
| Kemampuan | super_admin | holding_owner | branch_manager | cashier | module_operator | viewer |
|---|---|---|---|---|---|---|
| Semua data lintas entitas | ✓ | ✓ | — | — | — | view |
| Konteks switch | ✓ | ✓ | cabang sendiri | — | ✓ | — |
| CRM Kelola | ✓ | ✓ | ✓ | — | ✓ | view |
| Sales + approve/void | ✓ | ✓ | buat | — | ✓ | view |
| POS operate/shift | ✓ | — | — | ✓ | — | — |
| Inventory terima/kirim | ✓ | — | — | — | ✓ | view |
| Purchasing approve | ✓ | ✓ | — | — | — | view |
| Finance posting/approve/close | ✓ | — | — | — | — | view |
| Gaji (hr.salary) | ✓ | — | — | — | — | — |
| Payroll proses | ✓ | — | — | — | — | — |
| Approve governance | ✓ | ✓ | — | — | — | view |

Entity scope: `user_scopes(scope_type=legal_entity|branch)`. Branch manager terikat cabang; super_admin/holding_owner/executive/auditor lintas (auditor read-only: ditegakkan server-side oleh middleware `ReadOnlyRoles` — pengguna yang HANYA berperan auditor/viewer diblokir dari operasi tulis apa pun, walau permission tulis salah diberikan; self-service logout/profil/2FA/konteks tetap diizinkan).

## Pemetaan menu v0.4 (tanpa permission baru)
- Inventory → Batch & QC: `inventory.view` (lihat), `inventory.adjust` (release/karantina/reject).
- MFG Work Order issue/produce: `mfg.execute`.
- Finance → Komisi Penjualan: `finance.view` (lihat), `finance.approve` (approve/cancel).
- Pet & Transport → Tracking GPS (web + API positions): `pet.transport`.
- API POS: `pos.operate`/`pos.shift`; semua API butuh token ability `api.access` dari login.
