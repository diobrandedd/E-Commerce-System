# RTS system

## Upload storage (outside the project folder)

To update the PHP code (git pull / branch switch) without losing or conflicting with uploaded photos and spreadsheets, point storage to a folder **outside** this repository.

1. Copy `config/config.sample.php` to `config/config.php` if needed.
2. Set in `config/config.php`:

```php
'storage' => [
    'root' => 'C:/xampp/htdocs/rts_storage',  // outside the rts/ git folder
    'web_base' => '/rts_storage',             // URL path under htdocs
],
```

3. Create on disk (Apache will serve files from here):

- `rts_storage/rts_documentation/` — RTS parcel photos/videos  
- `rts_storage/deliveries_documentation/` — pre-dispatch delivery photos  
- `rts_storage/sp_sort/` — SP Sort Excel exports  

4. **Move existing files** (one-time), if you already uploaded into the project:

- From `rts/assets/rts_documentation/*` → `rts_storage/rts_documentation/`
- From `rts/assets/deliveries_documentation/*` → `rts_storage/deliveries_documentation/`
- From `rts/assets/sp_sort/*` → `rts_storage/sp_sort/`

Filenames in the database stay the same; only the folder on disk changes.

Leave `storage.root` empty to keep using `assets/` inside the project (not recommended for git workflows).
