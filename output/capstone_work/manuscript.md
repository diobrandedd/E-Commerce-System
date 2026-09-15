# PYX TRACKER
Web-based Enterprise Operations Management System
@COVER

# CHAPTER I
## INTRODUCTION
### Background of the Study
This study presents Pyx Tracker: Web-based Enterprise Operations Management System, a centralized web application developed for Pyx Food Products. It connects inventory and production records with online order preparation, parcel documentation, product costing, retail sales, and Return-to-Sender management. The project provides authorized personnel with a common record of stock and transactions as goods move through production and selling channels. Enterprise operations management in this study refers to these defined functions rather than every administrative or accounting activity of the company.

Pyx Food Products is located at Zone 1, Prk. Vicente Fin, Brgy. Magsaysay, Polomolok, South Cotabato, Philippines. The proposal identifies honey, Paragis Tea, Black Rice Coffee, and Jar Cacao among the covered product lines. The business receives materials, prepares products, maintains finished goods, and serves online and walk-in customers. Honey passes through warehouse, production, and packaging stages; department products require monitoring of raw inputs and finished pouches or jars. Before integration, employees recorded inventory on paper and in disconnected spreadsheets. They manually organized orders from Shopee, the VIP website and Facebook channels, matched waybills to order lists, recorded retail sales, estimated costs, and maintained return records. Information therefore passed between staff responsible for materials, production, customer service, fulfillment, and sales. This description is based on the proposal, which does not provide timed observations or transaction volumes.

The proposal identifies six problems. (1) Manual parcel verification, inventory deduction, and packing-material monitoring increase the risk of stock discrepancies, dispatch delays, and incomplete shipment documentation. (2) Paper records and disconnected spreadsheets permit erasures, inconsistent entries, and counting errors, making physical stock difficult to reconcile with recorded balances. (3) Unstructured production and packaging cost calculations weaken the basis for selling-price decisions. (4) Manually organizing multi-channel orders and matching waybills increases verification work and the possibility of incorrect parcel contents. (5) Manual retail recording delays transactions and their inventory updates. (6) Unstructured return handling makes it difficult to preserve return history and distinguish physically received sellable goods from damaged goods or cancelled orders. These concerns limit the company's ability to trace an item from receipt to sale or return. They are documented problems rather than quantified error rates or measured business losses.

Pyx Tracker addresses these concerns through six connected modules. Integrated Inventory Management records receipts, production stages, finished goods, and stock movements. Product Costing and Pricing maintains product formulas separately from parcel recipes. Multi-Platform Order Processing and Waybill Management connects preparation, validation, stamping, and batch records. Parcel Processing and Inventory Synchronization maintains packing and related stock information, while the current Deliveries station separately captures before-shipment photos. The Point of Sale module records walk-in transactions, receipts, and stock changes. Return-to-Sender Management preserves case and condition records and confirms sellable stock restoration through reviewed barcode stock-in sessions. Role-based access supports the responsibilities of executives, HR personnel, production managers, customer service staff, and cashiers. The expected benefit is a consistent, traceable operational record; actual improvement must be demonstrated through documented tests and business-workflow comparisons.

### Objectives of the Study
#### General Objective
This study aims to develop Pyx Tracker, a web-based enterprise operations management system for Pyx Food Products, to centralize inventory, fulfillment, costing, retail sales, and return records.

#### Specific Objectives
1. Develop an integrated inventory module that records raw materials, production movements, packaging materials, supplies, and finished-goods balances.
2. Implement a costing module that calculates product costs from configured formulas and maintains separate parcel-packaging cost recipes.
3. Develop a multi-platform order module that prepares order files, matches waybills, and produces stamped documents and finalized order batches.
4. Implement a parcel-processing module that maintains traceable packing records, stock-movement references, and before-shipment photo documentation.
5. Develop a POS module that records retail transactions, produces receipts, and deducts the selected inventory source.
6. Implement an RTS module that preserves return history and confirms sellable stock restoration through reviewed barcode stock-in sessions.

The module-specific tests in Chapter III evaluate these objectives through observable balances, cost calculations, generated documents, transaction records, and confirmed stock changes.

### Scope and Limitation of the Study
#### Scope of the Study
The study covers a role-based web application for the internal operations of Pyx Food Products. Its users include executives, HR personnel, production managers, authorized customer service and operational staff, and POS cashiers. Customers benefit from the resulting processes but are not authenticated users of a public storefront. The six major modules are as follows.

- Integrated Inventory Management System — records raw materials, honey production stages, department finished goods, packaging materials, supplies, and stock movements.
- Product Costing and Pricing Calculator — maintains product formulas, material prices, parcel-packaging recipes, and selling prices under executive access.
- Multi-Platform Order Processing and Waybill Management — prepares supported order files, stamps waybills, generates master Excel files, and finalizes batches.
- Parcel Processing and Inventory Synchronization — maintains packing and parcel records with applicable stock movements and separate shipment-photo documentation.
- Point of Sale System — records walk-in sales, deducts the selected stock source, generates receipts, and preserves sales history.
- Return-to-Sender Management — records return cases, item condition, claim history, and reviewed barcode stock-in of physically received sellable goods.

Authentication, staff and supplier records, permissions, dashboards, settings, and activity records support these six modules. They are shared controls rather than additional independent research objectives. The same module names are used in Chapter III.

#### Limitation of the Study
The project is limited to the identified company's workflows and configured product catalogs. It is not a multi-tenant commercial service or a public shopping platform. File preparation and waybill processing support implemented formats; universal marketplace compatibility and continuous API synchronization with Shopee, Facebook, or couriers are not claimed.

Users need access to the application server through a working network and compatible browser. Scanning depends on readable codes and suitable hardware or a supported camera environment. Reliable records require correct starting balances, mappings, units, prices, and confirmation of physical movements. A saved photo is documentary evidence and does not establish that the correct items were packed or delivered.

The current Deliveries photo-saving workflow does not deduct product or packing-material stock and does not automatically mark a parcel as shipped. Stock movements occur through their designated inventory, packing, sales, or return operations. An RTS case does not itself increase sellable inventory; this requires a confirmed barcode stock-in session. Configured production settings also affect raw-material deduction, so automatic deduction is not assumed for every stock-in action.

The costing module depends on maintained formulas and prices. It is not a complete general ledger, payroll system, statutory accounting package, or autonomous pricing authority. The reviewed implementation does not substantiate predictive forecasting, a generative AI assistant, or an AI pricing engine. Completed usability scores, performance benchmarks, and quantified business improvement require separate evaluation evidence.

### Significance of the Study
For executives, the system provides a basis for reviewing stock, sales, costs, and exceptions through connected records. It retains executive control over prices, permissions, and corrections.

For HR personnel, production managers, warehouse personnel, and customer service staff, role-specific workflows provide a consistent place to record receipts, production, packing, order preparation, shipment evidence, and returns. The expected benefit is less duplicate encoding and clearer responsibility for transactions.

For cashiers and customers, integrated POS records support organized transactions and retrievable receipts. Customers are indirect beneficiaries of consistent fulfillment and return documentation rather than assumed users of the internal application.

For future researchers, the project provides a Philippine food-enterprise case combining manufacturing-related inventory, online fulfillment, retail sales, and reverse flows. Its contribution can be evaluated through transaction traceability and documented implementation limits.

### IPO Model
@FIG ipo|1.1|Input Process Output Model of Pyx Tracker
Inputs include product and supplier details, receipts, prices, order sheets, waybill PDFs, scans, photos, sales baskets, and return condition. The system validates access and input, records the relevant movement, calculates costs, prepares documents, and confirms reviewed stock changes. Outputs include balances, histories, stamped files, shipment evidence, receipts, and RTS records.

The model separates documentation from inventory posting. A tracking number and photo produce a shipment record through a save action; a scanned sellable return produces an inventory increase after confirmation. Each output is therefore tied to the action that creates it.

### Definition of Terms
The following terms are defined operationally for this study.

**Barcode** — A machine-readable identifier used to locate a product or tracking record. A permanent SKU code identifies a product type; a legacy unique code identifies an individual issued unit.

**Before-shipment photo** — An image stored against a tracking number. Saving it does not post inventory deductions.

**Bill of materials** — Configured quantities of raw inputs or packaging associated with a product or production operation.

**Black Rice Coffee** — The department product referred to as BRC.

**Cost of goods sold** — The product-cost figure called COGS in the system, maintained separately from outbound parcel-packaging cost.

**Customer service representative** — A CSR staff member who handles supported order or return operations within assigned permissions.

**Finished goods** — Configured sellable bottles, pouches, jars, or other units available after confirmed stock-in.

**Inventory pool** — A defined stock source such as normal finished goods, sellable RTS goods, or damaged returns.

**Jar Cacao** — The cacao product tracked as finished jars in department inventory.

**Parcel recipe** — Material quantities used to estimate packaging cost for a matching parcel composition or tier.

**Point of Sale** — The POS workflow for recording walk-in sales, related stock deductions, and receipts.

**Return-to-Sender** — The RTS workflow for documenting returned parcels and processing their goods according to condition and confirmed receipt.

**Role-based access** — Rules allowing users to perform operations appropriate to their assigned role and department.

**Stock-keeping unit** — A SKU representing a product type or variant rather than one individual item.

**VIP** — The internal label for website and Facebook orders processed through the VIP preparation workflow.

**Waybill** — A shipment document containing tracking and delivery information, matched or stamped during order processing.

# CHAPTER II
## REVIEW OF RELATED LITERATURE AND STUDIES
This chapter develops the six themes in the supplied proposal's literature review. Philippine studies provide local workflow comparisons, while foreign studies contribute evidence on costing, identification, and logistics. The review distinguishes prototypes, reported evaluations, and industry guidance. Its gaps refer to the reviewed works rather than claiming that no comparable system exists elsewhere.

### Web-Based Inventory and Production Management for Food SMEs
Lorzano et al. (2022) linked POS transactions with a bill of materials for True North Empanada, addressing delays and discrepancies associated with manual consolidation. Laping et al. (2023) used design thinking and user evaluation to develop inventory-interface alternatives for a Marikina convenience store handling perishable goods. Ercilla et al. (2024) described a proposed inventory and POS system for Bida Mart with stock visibility and replenishment information. Together, these Philippine studies connect inventory problems with both transaction structure and staff needs.

The studies have different emphases: ingredient requirements, user-centered interface selection, and intended retail integration. A proposal should not be treated as proof of a mature deployment. These distinctions suggest that inventory software needs a clear business-process model as well as usable screens.

Goedhart et al. (2023) extend the discussion to omni-channel retail, showing through numerical analysis that jointly considering replenishment and online allocation can improve inventory balance across stores. For Pyx Tracker, the relevant implication is shared stock visibility across channels, rather than adoption of their optimization model.

However, the reviewed studies do not document the same combination of honey production stages, department goods, packaging and supplies, packing, POS, and confirmed RTS stock-in. Pyx Tracker addresses this integration requirement while retaining physical counts and user confirmation as essential controls.

### Parcel Processing Barcode Scanning and Shipment Verification
Chocholáč et al. (2022) compared RFID and barcode technologies in shipment consolidation through experiments and simulation. Their study concerns relative identification performance, not a general measured barcode-versus-manual improvement. Kamnardsiri et al. (2022) introduced barcode-detection datasets, including ParcelBar, and compared deep convolutional approaches. GS1 US (2026) discusses modern logistics data capture and two-dimensional identification.

These sources distinguish code detection, identifier interpretation, and the business action associated with a scan. A scan may locate a record without proving the contents or condition of a parcel. The local Bida Mart proposal identifies barcode integration as a future enhancement, not an established capability (Ercilla et al., 2024).

Pyx Tracker therefore treats scanning as an input mechanism supported by validation and confirmation. Tracking lookup supports documentary evidence; product scans support designated stock operations. Before-shipment photos provide a separate record for later return handling. The current Deliveries station preserves this distinction by saving photos without deducting stock.

However, the reviewed literature does not establish an evaluated Pyx-specific combination of packing records, product movements, material accountability, and shipment photos. The capstone must test these connections instead of transferring performance claims from RFID experiments or barcode-detection models.

### Product Costing and Pricing Information Systems
Munifah et al. (2026) developed a web-based cost-of-goods-manufactured system for an Indonesian snack-food MSME using variable costing. Lorzano et al. (2022) connect material requirements with food-retail sales, while Divina et al. (2025) combine transactions, inventory, and analytics for Pastil sa Tabi. The latter Philippine studies contribute related evidence on input and transaction integration; they are not equivalent parcel-costing systems.

Costing depends on both the calculation and its inputs. Quantity, purchase price, unit, and conversion yield must be interpreted consistently. A price per roll cannot represent the cost of one cut piece without a defined yield. Manufacturing cost and outbound parcel packaging also serve different purposes, so separate formulas make the calculation easier to review.

Pyx Tracker applies this separation through product formulas and parcel recipes. Executives maintain the values and selling prices. The resulting information supports a traceable decision but does not guarantee a commercially optimal price or profit. Accuracy remains conditional on complete and correct inputs.

However, the reviewed works do not document Pyx's exact connection among formulas, material yields, parcel tiers, and retail prices. The project addresses that integration requirement and checks calculations against known examples rather than treating automation itself as proof of accuracy.

### Multi-Platform Order Processing and Waybill Management
Goedhart et al. (2023) show that online fulfillment and inventory decisions interact across time and locations. Nanayakkara et al. (2022) address e-commerce returns through clustering, circular-network design, and optimization. Divina et al. (2025) illustrate transaction and inventory integration in a Philippine food enterprise. Although their settings differ, these works support examining how information remains connected through forward and reverse flows.

The Pyx proposal describes a file-oriented problem: staff organize supported channel orders and match them to waybills. This differs from selecting a fulfillment store or designing a regional returns network. The appropriate response is a controlled preparation workflow that permits review of names, items, variants, and tracking identifiers before parcel records are finalized.

The preparation tools and stamper perform distinct operations. VIP or SPX preparation produces an intermediate file; the PDF and Excel stamper is a separate step. Explicit stages help staff distinguish prepared input from generated output and finalized records. Duplicate-tracking validation also helps identify conflicting records before they are appended.

However, the reviewed studies do not document the same VIP and SPX preparation, PDF matching, master-file generation, and batch-finalization sequence. The project evaluates this organization-specific workflow using representative source files and expected output records.

### Point of Sale Systems and Integrated Retail Transactions
Ercilla et al. (2024) present stock visibility and reporting as benefits intended by an integrated POS proposal. Lorzano et al. (2022) connect retail sales with ingredient inventory. Divina et al. (2025) report user and technical evaluation of a POS and inventory system for Pastil sa Tabi, including favorable assessments and remaining performance and reliability concerns. This local evidence supports integration while showing that evaluation must extend beyond the presence of a checkout screen.

For Pyx Tracker, a sale must maintain consistency among items, quantities, prices, stock source, receipt, and ending balance. An RTS item must be distinguished from normal stock. Authentication and approval rules also affect a cashier's permitted actions.

Checkout is therefore assessed as a coordinated transaction. A test compares initial stock, purchased quantity, receipt total, and ending stock. Repeated or interrupted requests are examined for duplicate effects. These are evaluation requirements for this project rather than results already demonstrated by the proposal.

However, the reviewed works do not demonstrate the full Pyx combination of PIN access, department catalogs, selected normal or RTS stock, and wider production and fulfillment records. This capstone evaluates that combination within an operations platform.

### Return-to-Sender Management and Reverse Logistics
Nanayakkara et al. (2022) develop a circular returns framework validated in an electrical and electronic goods case. Bhatti and Bauirzhanovna (2023), using interviews with five experts, discuss intelligent inventory systems in Saudi manufacturing reverse logistics. Chocholáč et al. (2022) contribute complementary identification research at shipment-consolidation points. These foreign studies support structured return processes while differing from the food-enterprise setting.

A return record is not the same as usable stock. A case can contain photos, claims, and reusable or damaged item lines, while sellable inventory should represent goods physically received and accepted into the pool. A cancellation, expected return, and received sellable return are different events.

Pyx Tracker separates case recording from a reviewed barcode stock-in session. Scans build a draft; discarding the draft leaves stock unchanged; confirmation posts reviewed quantities. Damaged goods remain distinct. This establishes a testable requirement: entering a case must not itself create sellable inventory.

However, the reviewed sources do not provide a Philippine implementation study of this exact case-to-confirmed-stock process connected with Pyx's sales and packing workflows. That local evidence must come from the project's recorded return scenarios and inventory checks.

### Automation and Sustainable Development Alignment
The proposal aligns the project with SDGs 8, 9, and 12. The United Nations identifies productivity and technological upgrading under Goal 8, industrial innovation under Goal 9, and resource efficiency and waste reduction under Goal 12 (United Nations Department of Economic and Social Affairs [UN DESA], n.d.-a, n.d.-b, n.d.-c). The project's intended contribution is operational: less repeated encoding, connected records, and greater visibility of materials and returns. It does not claim measured achievement of these targets.

AI methods in the barcode and intelligent-inventory literature do not establish that Pyx Tracker implements those methods. The supplied proposal and reviewed application support document processing, scanning, and configured business rules. This manuscript makes no claim of a trained forecasting model, autonomous decision engine, or generative AI feature.

However, an SDG alignment statement cannot replace evidence of reduced waste or improved productivity. Later impact claims should identify the measure, period, and business comparison used.

### Synthesis of the Literature and Research Gap
The reviewed works share a concern with connections among material requirements, sales, inventory, orders, identification, and returns. Philippine studies offer local comparisons, while foreign logistics and costing studies explain additional dependencies. Their results remain specific to their settings and do not establish Pyx Tracker's effectiveness in advance.

The gap addressed here is the need for a coherent Pyx Food Products workflow connecting production-aware inventory, separate product and parcel costing, file-based orders, traceable shipment documentation, retail sales, and confirmed RTS stock-in. The contribution is integration and evaluation under the company's transaction and role rules, rather than a claim that every individual feature is novel.

Therefore, this study addresses these limitations by developing Pyx Tracker: Web-based Enterprise Operations Management System with six connected modules and a shared operational record. Chapter III explains the methodology, interactions, data structures, and verification procedure.

# CHAPTER III
## METHODOLOGY
This chapter describes the development approach, operating environment, functional design, data structure, and verification method for Pyx Tracker. It follows the supplied outline's environment, engineering technology, incremental builds, planning artifacts, analysis and design, database documentation, network design, specifications, and modules. Deployment at Pyx Food Products is acknowledged from the project team's account; dates, installed hardware, respondent counts, and measured results require supporting records.

### Environment
The study is situated at Pyx Food Products, Zone 1, Prk. Vicente Fin, Brgy. Magsaysay, Polomolok, South Cotabato, Philippines. Its setting includes material receiving, production, online orders, packing, shipment documentation, retail sales, and return handling. Executives, HR personnel, production managers, customer service staff, and authorized warehouse and sales personnel participate in these workflows.

The proposal supplies the business scope. Reviewed project files and the local database schema supply implementation evidence for this chapter. These do not establish that every local change is installed identically on the company's server. Identifying the release used during evaluation is therefore part of deployment verification.

### Software Engineering Technology
The study uses the Agile Software Development Model identified in the proposal. Agile principles emphasize working software, collaboration, responsiveness to requirements, and regular reflection (Beck et al., 2001). The methodology organizes work into six increments, each covering design and development, testing, and implementation or refinement. Shared authentication and data definitions support every increment.

@FIG agile|3.1|Incremental Agile Software Development Model
The diagram follows the sample's requirements-to-build structure, expanded to the six modules. Return arrows indicate feedback and revision. This sequence organizes the methodology and does not represent verified historical dates.

#### Build 1 Integrated Inventory Management System
Design and development define material categories, suppliers, products, measurement units, honey stage movements, and department finished goods. Separate stock structures preserve normal and returned sellable balances. Role rules determine who records receipts, performs production actions, and approves corrections.

Testing compares initial balances, recorded movements, and expected ending balances. Scenarios cover honey stages, department stock-in, configured raw deduction, repeated scans, and cancellation before confirmation. Raw kilograms must not be combined directly with finished pouches or bottles. Implementation review demonstrates receipt-to-finished-stock traceability and records the deduction settings used during evaluation.

#### Build 2 Product Costing and Pricing Calculator
Design and development establish formulas, material prices, yield conversions, custom costs, and parcel recipes. Manufacturing cost remains separate from fulfillment packaging. Executive permissions govern costing and selling-price changes.

Testing uses known inputs to verify line calculations and totals, including a roll cost converted to a usable-piece cost, a custom labor line, and a parcel matching one tier. It also checks unauthorized access and missing or inactive recipes. Implementation review records formula inputs and outputs without claiming that cost computation guarantees profit.

#### Build 3 Multi-Platform Order Processing and Waybill Management
Design and development organize supported VIP and SPX inputs into a common preparation workflow. Users review names, items, and variants, then operate the separate PDF and Excel stamper. Finalized batches provide master-file and parcel records.

Testing uses representative spreadsheets and PDFs to check matching, preserved descriptions, readable output, duplicate-tracking feedback, and finalization. Exceptions include missing columns, unmatched records, and repeated requests. Implementation review follows one batch from source files to final records and distinguishes preparation from stamping and finalization.

#### Build 4 Parcel Processing and Inventory Synchronization
Design and development connect packing activity and stock movements with product and parcel records. The current Deliveries station is documentary: staff scan or type a tracking number, capture or upload a photo, and save it for later lookup. Saving the photo is separate from stock deduction.

Testing checks the designated packing-confirmation workflow and its stock source. Photo scenarios cover tracking numbers with and without parcel records, duplicates, and later RTS lookup. Saving a photo alone must leave product and packing-material balances unchanged. Implementation review distinguishes packing, shipment-status actions, and documentary saves.

#### Build 5 Point of Sale System
Design and development provide cashier PIN access, item selection or scanning, normal or RTS stock selection, checkout, receipts, and history. Receipt items, prices, totals, and consumed inventory remain connected. Discount and receivable operations follow their configured permissions.

Testing covers normal-stock sales, RTS sales, totals, insufficient stock, unauthorized discounts, and repeated checkout requests. Each test records initial stock and the expected deduction. Implementation review demonstrates a complete sale and retrieves its receipt and history.

#### Build 6 Return-to-Sender Management
Design and development separate case documentation from sellable stock restoration. Staff locate the parcel, record condition and evidence, then scan accepted sellable goods into an owned draft session. Confirmation applies the reviewed stock-in; damaged goods remain outside the sellable balance.

Testing checks that case saving does not credit sellable stock, cancellation leaves balances unchanged, and confirmation posts the expected quantity once. Further scenarios cover invalid codes, repeated scans, mixed products, and later RTS sales. Implementation review traces a return from the case to confirmed stock while preserving its original history.

### Planning and Conception Initiation Phase
#### Business Model Canvas
@FIG bmc|3.2|Business Model Canvas for Pyx Food Products and Pyx Tracker
The canvas follows the sample's nine blocks: partners, activities, resources, value proposition, customer relationships, channels, customer segments, cost structure, and revenue streams. It represents the food business supported by the system. Pyx Tracker is an internal resource; the canvas does not invent software-subscription income, market-size figures, or financial amounts.

#### Gantt Chart
@FIG gantt|3.3|Provisional Development and Implementation Gantt Chart
The 12-week relative schedule shows requirements, design, six builds, integration, evaluation, and deployment documentation. Week numbers are provisional planning intervals. Actual start, milestone, testing, and deployment dates were not supplied and must replace these intervals before the chart is represented as historical. Testing and feedback accompany each build.

#### Functional Decomposition Diagram
@FIG fdd|3.4|Functional Decomposition Diagram of Pyx Tracker
The FDD decomposes the root into six modules and their subfunctions, following the sample's system-to-function-to-subfunction hierarchy. It represents responsibility, not screen navigation or execution order. Shared authentication, permissions, and reporting support the branches.

### Analysis and Design Phase
#### Use Case Diagrams
Human actors appear outside the system boundary, with use cases inside. Actors represent operational roles rather than named employees. Actual access remains subject to each workflow's role and department checks. The POS diagram uses an include relationship for required stock deduction and an extend relationship for an optional approved discount.

@FIG use_inventory|3.5|Use Cases for Inventory and Production
@FIG use_orders|3.6|Use Cases for Online Orders
@FIG use_pos|3.7|Use Cases for Point of Sale
@FIG use_rts|3.8|Use Cases for Return-to-Sender Management
@FIG use_costing|3.9|Use Cases for Product Costing
@FIG use_parcel|3.10|Use Cases for Parcel Documentation

#### Use Case Matrix
@USECASES

#### Storyboard
The following labeled wireframes explain the principal screens and interaction sequences. They are schematic drawings, not production screenshots. They follow the reference's screen-plus-explanation approach without copying its sample company's interface. Approved screenshots of the exact evaluated release can replace them if deployed-screen evidence is required.

@FIG story_inventory|3.11|Inventory Storyboard
The user selects the category, product, and lot, enters or scans the movement, and reviews the action before confirmation. Quantities use the selected stock type's units. Available actions depend on the user's permissions.

@FIG story_orders|3.12|Online Orders Storyboard
CSR personnel select a source-specific preparation tool, review the information, then operate the separate stamper. Generated documents and finalized batch records are distinct outcomes.

@FIG story_costing|3.13|Product Costing Storyboard
An executive selects a product or parcel recipe, checks price and yield, and reviews formula or custom-cost lines. Product and parcel expenses remain separate.

@FIG story_parcel|3.14|Parcel Documentation Storyboard
The operator enters the tracking number, captures or uploads the image, and saves the photo record. The storyboard does not depict stock deduction from this documentary action.

@FIG story_pos|3.15|Point of Sale Storyboard
The cashier authenticates, chooses items and stock source, reviews the basket, and completes checkout. The receipt and ending stock provide evidence of the transaction.

@FIG story_rts|3.16|Return-to-Sender Storyboard
The operator records the case and condition, then reviews a separate stock-in session for received sellable units. Inventory is credited after confirmation.

#### Database Design
@FIG database|3.17|Database Design by Functional Area
The shared relational database contains identity, stock, production, order, costing, sales, and returns records. The areas shown are logical groupings, not independent databases. Dedicated tables preserve the different units and lifecycles of honey stages, department goods, material pools, and transactions.

Column and foreign-key metadata were inspected on 15 September 2026. Business records were not needed. The diagrams describe the reviewed local schema; deployment verification should confirm that the evaluated installation uses the same version.

#### Entity Relationship Diagram
@FIG erd_inventory|3.18|Inventory and Costing Entity Relationships
@FIG erd_orders|3.19|Order and Sales Entity Relationships
@FIG erd_rts|3.20|Return and Barcode Entity Relationships
These diagrams show selected declared foreign keys. A parent identifier may be referenced by many child records. The dictionary identifies whether a child reference permits NULL. Application-level relationships are not presented as database constraints. Repeated entity boxes keep each relationship readable.

#### Data Dictionary
The core dictionary records actual field names, types, nullability, defaults, and keys for the principal tables supporting the six modules. Descriptions explain operational purpose. A dash means no explicit default or a SQL NULL default reported by metadata, not a literal stored dash. PRI means primary key, UNI means unique index, and MUL means a non-unique indexed column. Index labels do not by themselves establish foreign keys.

Supporting request, history, permission, issued-code, unit-tracking, and correction tables are not all reproduced in this core dictionary. Permanent SKU codes identify product types; additional unit and event tables preserve movement detail.

@DICTIONARY

#### Network Design
@FIG network|3.21|Logical Network Design of Pyx Tracker
Browser clients connect to the application server, PHP services, database, and controlled file storage. Server workflows invoke Python for document processing. Scanners and cameras provide input rather than connecting directly to the database. Session and permission checks apply through the application.

The diagram does not assert an unverified server address, hosting provider, router model, or equipment inventory. A local deployment requires company-network access; remote hosting also depends on an internet connection and appropriate secure browser access. Actual installation details require deployment records.

#### Network Topology
@FIG topology|3.22|Star Topology for Local Access
Clients connect through a router or access point. A LAN server uses this local infrastructure; a remote server is reached through the internet uplink. This is a logical topology pending confirmation of the installed arrangement.

#### Technology Stack Development
@FIG stack|3.23|Technology Stack of Pyx Tracker
The stack follows the proposal and reviewed project: React and TypeScript for the interface, Tailwind CSS for presentation, PHP for business actions, MySQL for persistent records, and Python for document utilities. Vite supports frontend builds. Defined API actions connect these components.

#### Software Specification
@SOFTWARE

#### Hardware Specification
The sources do not identify exact deployed equipment. The table states functional requirements to be validated against the installed hardware rather than inventing machine specifications. Capacity must be tested against actual concurrent use and file sizes.
@HARDWARE

#### Program Specification
@PROGRAM

#### Testing and Evaluation Procedure
Testing follows the six objectives. Each scenario records the initial state, permitted role, input, expected result, observed result, and evidence such as a receipt, generated file, or stock movement. Representative approved test records permit consistent comparisons.
@TESTS

Integration tests follow receipt-to-finished-stock, source-file-to-parcel, POS-to-receipt, and return-to-confirmed-stock paths. Permission tests use authorized and unauthorized roles. Exceptions include invalid codes, duplicates, incomplete files, insufficient stock, interruption, and cancellation of drafts.

User evaluation should involve representatives of the actual operational roles performing defined tasks. Records should identify the selection method, number of participants, instrument, date, and system release. Any comparison of task time or stock accuracy must use comparable conditions and explain the measurements. No respondent count, rating, or improvement percentage is claimed without those records.

Deployment verification identifies the release date, installed schema, application version, roles, input devices, and backup and restoration procedure. An acceptance record should identify the evaluated installation. The provisional Gantt and logical network diagrams must be reconciled with these records before final submission.

#### List of Modules
@MODULES

# REFERENCES
@REFERENCES
