# Pyx Tracker — React SPA

Frontend for **Pyx Tracker** (inventory, RTS, costing, POS, waybill stamper, etc.).  
System overview, roles, and roadmap: [`../README.md`](../README.md).

## Commands

```bash
npm install
npm run dev          # http://localhost:5173 — API proxied to XAMPP /rts/api
npm run build:prod   # production bundle → app/dist/
```

## Production

Apache serves the built app at:

`http://localhost/rts/app/dist/`

Router basename in production: `/rts/app/dist` (see `src/app/routes.ts`).

## Structure

- `src/app/pages/` — screens by role (exec, honey, csr, pos, …)
- `src/app/lib/` — API clients (`api.ts`, `waybillApi.ts`, …)
- `src/app/components/` — layout, sidebar, shared UI
- `public/.htaccess` — SPA deep-link fallback (copied to `dist/` on build)
