---
type: cause
area: backend-config
tags: [cause, db]
---

# Cause: Manual DB name switching between vhosts

One shared codebase used a single static DB name in runtime config, so developers had to manually change it when moving between Apache port `80` and `8080`.
