# Approved Proposal — Pyx Tracker

Source: `C:\Users\Admin\Downloads\pyxtrackerdocs (1).docx`

Treat this file as the approved content baseline for capstone documentation. Do not silently broaden or replace these details. Verify implementation-specific claims against the current project before describing them as completed.

## Proponents

- Cayunda, Klint Russel R.
- Delos Santos, Brent R.
- Fin, Noli III M.

## Proposed title

**Pyx Tracker: Web-based Enterprise Operations Management System**

## Area of investigation

Pyx Food Products, Zone 1, Prk. Vicente Fin, Brgy. Magsaysay, Polomolok, South Cotabato, Philippines

## Cited problems

This study seeks to answer the following problems:

1. Manual parcel verification, inventory deduction, and packing-material monitoring increase the risk of stock discrepancies, shipment delays, and incomplete shipment documentation, negatively affecting warehouse efficiency and order fulfillment.
2. Inventory is recorded manually using paper-based records and disconnected spreadsheets. Employees encounter erasures, inconsistent stock entries, and counting miscalculations. Recorded inventory often differs from actual stock, causing overcounting, undercounting, and inefficient monitoring across raw materials, packaging, supplies, and finished goods.
3. The absence of an automated costing mechanism may cause inaccurate production-cost computation, inconsistent packaging-expense tracking, and unreliable selling-price decisions, affecting financial decision-making and business profitability.
4. Orders from Shopee, VIP (website and Facebook orders), and other channels are manually organized, while waybills are manually matched to order lists. Employees spend significant time verifying parcel contents, increasing packing errors, shipment delays, and fulfillment inefficiency.
5. Manual recording of retail and walk-in transactions delays sales processing and inventory updates, resulting in disorganized sales records, delayed stock deductions, and inefficient store operations.
6. The absence of an automated Return-to-Sender (RTS) process makes it difficult to track returned or cancelled parcels, restore sellable inventory accurately, and maintain reliable return history, causing stock inconsistencies and inefficient return handling.

## Function of the proposed system

Pyx Tracker is a centralized, role-based web platform that unifies inventory, production, multi-channel fulfillment, retail sales, and returns management for Pyx Food Products.

The platform:

- Consolidates honey and department production workflows from warehouse stock-in through bottling and pouch finishing.
- Tracks packaging materials and supplies with role-appropriate stock-in, deduction, and approval actions.
- Processes online orders through VIP and Shopee SPX preparation tools, PDF and Excel waybill stamping, batch finalization, and parcel records.
- Connects parcel records to a deliveries module in which staff scan tracking numbers, capture before-shipping photos, deduct finished goods and packing materials, and update parcel status from `To Pack` to `Shipped`.
- Provides an executive-only costing hub for manufacturing cost of goods sold (COGS), parcel packaging tiers, and selling prices.
- Provides an integrated POS module for walk-in honey and department-product sales, real-time inventory deduction, receipts, and accounts-receivable credits.
- Records RTS cases, synchronizes sellable and damaged inventory pools, and supports `Use RTS` deductions for POS and packaging.
- Provides dashboards, sales reports, supplier records, and staff management for executive and production-manager oversight.

## Approved features

### 1. Parcel Processing and Inventory Synchronization

Uses QR-code and barcode scanning to verify parcels, automatically deduct finished-goods inventory and packing materials, capture Before Shipping Images as shipment proof, and update order status from `To Pack` to `Shipped`.

### 2. Integrated Inventory Management System

Centralizes raw materials, packaging materials, supplies, and finished goods; tracks the honey pipeline from warehouse to production to packaging and department production for Paragis Tea, BRC, and Jar Cacao; records stock movements; and maintains real-time on-hand balances across warehouse, online, and retail channels.

### 3. Product Costing and Pricing Calculator

Automatically computes manufacturing production costs, fulfillment packaging expenses, and selling prices. It uses separate formulas for product COGS and parcel packaging tiers to support consistent and accurate pricing decisions.

### 4. Multi-Platform Order Processing and Waybill Management

Centralizes orders from multiple selling platforms, including VIP and Shopee SPX; organizes orders for fulfillment through batch workflows; and automates waybill matching, stamping, and master Excel generation.

### 5. Point of Sale System

Digitizes walk-in retail transactions through PIN-based POS login, automatically records sales, deducts inventory with RTS-versus-non-RTS stock-source selection, generates receipts, and maintains transaction and sales history.

### 6. Return-to-Sender Management

Uses tracking-number lookup and barcode scanning to process returned or cancelled parcels, record RTS cases with line items, synchronize sellable and damaged inventory pools, restore stock automatically, and maintain return history and dashboard counts.

## Approved technologies

### Front end

- React
- TypeScript
- Tailwind CSS

### Back end and data processing

- PHP
- MySQL
- Python

## Software engineering model

Use the **Agile Software Development Model**. Describe it as an iterative and flexible methodology in which the team designs, implements, tests, and improves each module incrementally while incorporating user feedback and adapting to changing requirements.

Apply the model to the integrated modules for:

- Order processing
- Inventory management
- Parcel processing
- Product costing
- Point of Sale
- Return-to-Sender management

Do not state that a testing or feedback activity was completed unless supporting evidence is available.

## Reasons for the system

1. Automate parcel processing through QR-code and barcode scanning to verify tracking numbers, deduct product and packing-material inventory, capture Before Shipping Images, and update parcel status, reducing fulfillment errors and shipment delays.
2. Maintain accurate synchronized inventory across honey production, department manufacturing, packaging materials, supplies, online orders, POS sales, and RTS returns, minimizing stock discrepancies.
3. Improve pricing accuracy through an automated costing and pricing calculator that separates manufacturing COGS from fulfillment packaging costs, supporting reliable profit and pricing decisions.
4. Simplify multi-platform order processing by centralizing VIP and Shopee SPX preparation, waybill stamping, and batch finalization, reducing manual verification time and packing errors.
5. Enhance walk-in sales through an integrated POS system that records transactions, updates inventory in real time, and generates sales reports for operational monitoring.
6. Simplify RTS handling through scan-based case recording, inventory-pool synchronization, and automated stock restoration, reducing manual adjustments and return-tracking errors.

## Importance of the study

The system is expected to improve Pyx Food Products' operations by automating order processing, inventory management, parcel fulfillment, product costing, retail sales, and RTS management. It seeks to minimize manual tasks, reduce processing errors, improve inventory accuracy, and enhance operational efficiency.

### Sustainable Development Goal alignment

- **SDG 8 — Decent Work and Economic Growth:** Reduce repetitive manual encoding, spreadsheet switching, and paper-based parcel logs so warehouse, customer-service, and production staff can focus on higher-value work.
- **SDG 9 — Industry, Innovation and Infrastructure:** Digitize inventory pipelines, fulfillment tools, costing, POS, and RTS through one web-based infrastructure tailored to Pyx Food Products.
- **SDG 12 — Responsible Consumption and Production:** Improve inventory accuracy and production traceability from raw materials through finished goods to reduce overproduction, stock waste, incorrect packing, and untracked returns.

## Target users and beneficiaries

- Executives
- HR personnel
- Staff
- Customers

Where project evidence supports more specific operational roles, retain these approved groups and explain the subroles rather than replacing them.

## Traceability matrix

| Problem | Approved response |
|---|---|
| Manual parcel verification, deduction, and shipment documentation | Parcel Processing and Inventory Synchronization |
| Fragmented paper and spreadsheet inventory | Integrated Inventory Management System |
| Inaccurate costing and pricing | Product Costing and Pricing Calculator |
| Manual order organization and waybill matching | Multi-Platform Order Processing and Waybill Management |
| Manual retail and walk-in transactions | Point of Sale System |
| Untracked returns and inaccurate stock restoration | Return-to-Sender Management |

## Evidence constraints

- The proposal is authoritative for intended scope, terminology, and rationale.
- The repository is authoritative for current implementation details.
- User-provided test records, interviews, questionnaires, screenshots, diagrams, and deployment records are authoritative for empirical claims.
- When proposal and implementation differ, identify the discrepancy and ask the user which version the final manuscript should represent.
- Never fabricate statistics, respondents, evaluation scores, dates, citations, completed tests, or deployment outcomes.
