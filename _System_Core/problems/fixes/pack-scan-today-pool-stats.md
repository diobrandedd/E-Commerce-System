---
date: 2026-09-07
type: fix
area: deliveries
---

# Fix: Pack scan today pool stats

- `honey_bottle_pack_session_today_stats($pdo, $userId)` sums item qty for the signed-in user’s confirmed sessions today plus open draft.
- Honey unique stickers use `COALESCE(item.pool, unit.pool, packaging)`; dept finished counts under Packaging.
- API `pack_today_stats` + Scan tab cards: **Today · Packaging** / **Today · RTS sellable**.
