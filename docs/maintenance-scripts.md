# Maintenance & Utility Scripts

One-off scripts that perform bulk data operations. These are **not accessible from the UI** (menu links are commented out) and should be run deliberately with care, as they make irreversible database changes.

---

## `batch-ace-info.php`

**Purpose:** Bulk anonymizes client information on ACE (Accessibility, Accommodation and Adaptive Computer Technology) triage records.

**What it does:**

1. Queries all records in `tbltriage` belonging to catalogue IDs 1–4 (ACE service categories)
2. Skips records for service ID 46 (WS CoE services)
3. For each remaining record:
   - Overwrites `clientlname`, `clientfname`, `clientemail`, `clientphone` in `tbltriage` with generic AAACT contact details
   - Replaces the original description in `tblcommlog` with a generic placeholder string (`batch_ace_no_details` from the lang file)
4. Redirects to the index page with `?status=batchsuccess`

**When to use:** Before sharing or archiving a dataset — strips personally identifiable client information from a bulk set of ACE requests.

**Future plan:** This script will be replaced by a configurable superadmin UI tool. See [docs/future/008-superadmin-bulk-anonymize.md](future/008-superadmin-bulk-anonymize.md).

**Caution:**
- **Irreversible** — there is no undo. Back up the database first.
- The menu link is intentionally commented out in `appmenu.php` and `template/menu.php` to prevent accidental execution.
- Contains a **SQL injection vulnerability** (`$requestid` is interpolated directly into UPDATE queries). Do not expose this endpoint publicly. See [docs/future/005-code-quality-refactoring.md](future/005-code-quality-refactoring.md) for the remediation plan.

**How to run (deliberately):**

Temporarily uncomment the menu link in `appmenu.php`, or navigate directly:

```
https://<your-domain>/batch-ace-info.php?lang=en
```
