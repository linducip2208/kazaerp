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

Entity scope: `user_scopes(scope_type=legal_entity|branch)`. Branch manager terikat cabang; super_admin/holding_owner/executive/auditor lintas (auditor read-only via viewer-like + audit.view — PENDING: batasi ke read di semua modul).
