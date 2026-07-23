# ERPNext product sync setup

`php artisan erpnext:sync-products` pulls sellable Items from an external ERPNext
instance (https://docs.erpnext.com) and mirrors each one into a real Bagisto product —
same catalog, search, cart, and checkout as any product an admin adds by hand. Nothing
is faked: without credentials, the command and its schedule both no-op with a clear
message instead of pretending to sync.

## Required credentials

Add in `.env`:

```
ERPNEXT_BASE_URL=https://your-site.erpnext.com
ERPNEXT_API_KEY=your-api-key
ERPNEXT_API_SECRET=your-api-secret
```

Generate an API key/secret pair in ERPNext under **Settings → Users → (your user) →
API Access → Generate Keys**. The user needs read access to the `Item` and `Bin`
doctypes.

## How it works

- Fetches enabled Items (`disabled = 0`) from ERPNext's `Item` doctype: name, price
  (`standard_rate`), description, image, weight.
- Fetches on-hand stock from the `Bin` doctype, summed across every ERPNext warehouse.
- Every synced item is attributed to one dedicated system seller ("External Catalog"),
  kept separate from real vendor accounts, so the existing per-vendor checkout and
  delivery-logistics flow works unmodified.
- Re-running the command updates the existing product (price, name, stock) instead of
  duplicating it — matched via `marketplace_erpnext_products.item_code`.
- A single failed item (bad data, missing image) is logged and skipped; it never aborts
  the rest of the batch.

## Scheduling

Runs hourly via Laravel's scheduler once configured — but **shared hosting rarely has a
cron entry set up for `schedule:run`**. Add one in cPanel (or wherever cron jobs are
managed on your host):

```
* * * * * php /path-to-app/artisan schedule:run >> /dev/null 2>&1
```

Until that's in place, run `php artisan erpnext:sync-products` manually (SSH or a
scheduled task through your host's control panel) whenever you want the catalog to
pick up ERPNext changes.
