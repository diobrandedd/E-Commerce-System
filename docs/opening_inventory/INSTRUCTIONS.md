# Opening inventory — staff forms (Option B)

Plain-English sheets for printing and data entry. Hidden `_Import_*` sheets are for IT import only.

## Workbook sheets

| Sheet | Who uses it | Print? |
|-------|-------------|--------|
| **START HERE** | Everyone — read first | Optional |
| **Staff - Users** | HR / executive | Yes |
| **Staff - Delivery** | Delivery team | Yes |
| **Staff - Suppliers** | Purchasing | Yes |
| **Staff - Stock Count** | Dept leads (filter by department) | Yes |
| **Staff - Finished Goods** | Production / honey / CSR staging | Yes |
| **_Import_Keys** | IT only (hidden) | No |
| **_Import_Users_SPA** | IT only (hidden) | No |

## Colors

| Color | Meaning |
|-------|---------|
| **Blue row** | Section title — do not write here |
| **Gray cell** | Pre-filled — do not change |
| **Yellow cell** | **You fill in here** |

## Workflow

1. Print the **Staff -** sheets each team needs.
2. Floor staff count and write on **paper** (yellow columns only).
3. Data-entry team types into **yellow cells** in Excel.
4. Send completed file to IT for import (dry-run first).

## Print tips

- **Stock Count:** filter by **Department** column before printing (Honey, Paragis Tea, etc.).
- **Suppliers:** one sheet has 3 sections (supplier list → honey prices → raw prices).
- **Finished Goods:** 3 sections (dept products → CSR staging → honey bottles).

## What staff write (plain English)

### Staff - Users
- What to do: Add new person / Keep this person / Remove person
- Login username, full name, employee ID (becomes first password)
- Job role and department (dropdowns)

### Staff - Delivery
- Same idea for the delivery photo app only

### Staff - Suppliers
- Short supplier code (example `H001`) — reuse in price tables below
- Honey prices: type (Light/Amber/Dark), price per gallon
- Raw prices: product (Paragis / BRC / Jar Cacao), price per unit

### Staff - Stock Count
- **How many on hand?** — physical count today
- **Cost per unit** — if known
- Optional: supplier code, batch #, date counted

### Staff - Finished Goods
- **How many on shelf?** + **Selling price** for dept products
- Honey bottles: color + size + count

## Cutover date

Fill in **START HERE** sheet:
- Count date
- Month (YYYY-MM)

## When done

Send the `.xlsx` file to IT. Import maps friendly labels back to the database automatically.

## Regenerate template (IT)

```bat
c:\xampp\php\php.exe tools\export_opening_inventory_data.php
python tools\build_opening_inventory_staff_xlsx.py
```

Output: `docs/opening_inventory/PyxTracker_Opening_Inventory_YYYY-MM-DD.xlsx`
