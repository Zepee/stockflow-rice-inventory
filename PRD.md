# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# StockFlow — Rice Inventory Management System

**Version:** 1.0  
**Product Type:** Web-based Inventory Management System  
**Target:** Small and medium rice businesses  
**Backend:** Laravel  
**Database:** MySQL

---

## 1. Product Overview

StockFlow is a web-based inventory management application designed for rice wholesalers, warehouse owners, and rice distribution businesses.

The application replaces manual inventory tracking using Excel or notebooks with a centralized system that tracks inventory, purchases, sales, and stock movements.

The primary goal is to provide accurate inventory information and simplify daily warehouse operations.

## 2. Target Users

### Admin / Owner
- Full access to all application features.
- View inventory and business reports.
- Manage products, suppliers, and customers.
- Manage users.
- Review transaction history.

### Staff / Warehouse Operator
- View available inventory.
- Record incoming stock.
- Record outgoing stock.
- View transaction history.
- Cannot manage user permissions or sensitive settings.

## 3. Core Features

### A. Authentication
- Login and logout.
- Secure password hashing.
- Role-based access control.
- Protected application routes.
- Admin and Staff roles.

### B. Dashboard

Display:
- Total inventory quantity.
- Total active rice products.
- Today's incoming stock.
- Today's outgoing stock.
- Low-stock alerts.
- Recent transactions.
- Inventory overview.

Dashboard data must come from actual database records. Never use hardcoded demonstration numbers in production.

### C. Product Management

Each product must contain:
- Product name.
- Unique SKU.
- Description (optional).
- Unit of measurement.
- Purchase price.
- Selling price.
- Minimum stock threshold.
- Current stock.
- Active/inactive status.

Users can:
- Create products.
- Edit products.
- View product details.
- Search and filter products.
- Archive inactive products.

Use a consistent base unit for each product. For the initial implementation, support kg as the default unit and allow decimal quantities.

### D. Supplier Management

- Create suppliers.
- Edit supplier details.
- Supplier name.
- Phone number.
- Address.
- Transaction history.

### E. Customer Management

- Create customers.
- Edit customer details.
- Customer name.
- Phone number.
- Address.
- Transaction history.

### F. Purchase / Stock In

Users can create purchase transactions containing:
- Transaction number.
- Supplier.
- Transaction date.
- One or more rice products.
- Quantity.
- Purchase price per unit.
- Subtotal.
- Total transaction value.
- Notes (optional).

Business rules:
- Saving a valid purchase increases product inventory.
- Every purchase creates an inventory movement record.
- All inventory updates must be atomic.
- Prevent duplicate stock updates when a request is retried.

### G. Sales / Stock Out

Users can create sales transactions containing:
- Transaction number.
- Customer.
- Transaction date.
- One or more rice products.
- Quantity.
- Selling price per unit.
- Subtotal.
- Total transaction value.
- Notes (optional).

Business rules:
- Saving a valid sale decreases inventory.
- Prevent selling more stock than available.
- Validate inventory again during transaction processing.
- All stock changes must be atomic.
- Every sale creates an inventory movement record.

### H. Inventory History

Display a searchable inventory movement table.

Columns:
- Date.
- Transaction number.
- Product.
- Movement type.
- Quantity in.
- Quantity out.
- Stock balance.
- User responsible.

Support filtering by:
- Date range.
- Product.
- Movement type.

### I. Reports

Provide:
- Current inventory report.
- Purchase report.
- Sales report.
- Stock movement report.

Include date filters and CSV export.

### J. WhatsApp Daily Notification

Future integration:
- Generate a daily inventory summary.
- Include total incoming stock.
- Include total outgoing stock.
- Include closing stock for each product.
- Notify when products fall below minimum stock.

The first implementation should prepare a clean notification service and message template. Actual WhatsApp API integration is a later development phase.

## 4. Database Architecture

Use a relational MySQL database.

Suggested tables:

1. users
2. warehouses (one active/default warehouse in the MVP)
3. products
4. suppliers
5. customers
6. purchases
7. purchase_items
8. sales
9. sale_items
10. stock_adjustments
11. stock_adjustment_items
12. inventory_movements
13. transaction_number_sequences
14. transaction_cancellations
15. product_selling_price_changes

Important relationships and constraints:
- One purchase has many purchase_items; each item stores the configured purchase price snapshot, applied price, quantity, and rounded line subtotal.
- One sale has many sale_items; each item stores the configured selling price, applied price, quantity, rounded line subtotal, and any required Owner override reason.
- One stock adjustment has many stock_adjustment_items; every adjustment has a mandatory reason and responsible user.
- One product has many inventory_movements; each movement is linked to its source item and, for reversals, to the movement it reverses.
- One supplier has many purchases; one customer has many sales.
- One user can create multiple transactions, adjustments, cancellations, and price changes.
- Each transaction cancellation references exactly one original purchase, sale, or stock adjustment; unique constraints prevent cancelling the same original more than once.
- Product selling-price changes retain old price, new price, user, and timestamp.
- Transaction-number sequences are unique by transaction type and confirmation date. Persist sequence allocations separately from business transactions and enforce unique transaction numbers on transaction records.
- Transactions and inventory movements reference the single MVP warehouse. Do not add multi-warehouse workflows to the MVP.

Store whole-rupiah prices and monetary totals in BIGINT UNSIGNED columns. The maximum storable value is 18,446,744,073,709,551,615. Validate unit prices, rounded line subtotals, and transaction totals server-side against this limit before persistence. Use exact arithmetic for intermediate products so multiplication and summation cannot overflow.

Store product balances, movement quantities, and transaction item quantities as DECIMAL(15,3). Use exact decimal/integer arithmetic, not binary floating point. Validate input and resulting balances against the DECIMAL capacity.

`inventory_movements` is the permanent audit ledger. `products.current_stock` stores the current balance for efficient queries. Update both in the same database transaction and use row locking to prevent concurrent changes from producing inconsistent balances. Reconciliation must be possible by comparing each product's stored balance with the signed sum of its movement records. Cancellations preserve original records and append reversing movements; they do not delete or rewrite ledger history.

Do not allow ordinary users to directly edit current stock. Record initial stock and later corrections only through dedicated stock adjustment transactions with a mandatory reason and audit trail.
## 5. UI/UX Design Requirements

Design direction: Professional B2B SaaS application.

Visual references:
- Linear.
- Stripe Dashboard.
- Vercel Dashboard.

These references are for design principles only. Do not copy their interfaces.

### Design principles

1. Minimalist and functional.
2. Clear information hierarchy.
3. Strong typography.
4. Consistent spacing.
5. Data readability over decoration.
6. Desktop-first but responsive.
7. Consistent components across pages.

### Color system

- Primary: Deep navy.
- Background: White and very light gray.
- Accent: Emerald green.
- Text: Dark charcoal.
- Secondary text: Neutral gray.
- Danger: Restrained red.
- Warning: Amber.

### Typography

Use Inter with consistent font weights and sizes.

### Layout

Sidebar navigation:
- Dashboard
- Products
- Purchases
- Sales
- Inventory History
- Reports
- Suppliers
- Customers
- User Management (Admin only)
- Settings

Main content:
- Page title.
- Short contextual description.
- Primary action button.
- Relevant filters.
- Main content area.

### Anti-AI-Slop Requirements

STRICTLY AVOID:
- Excessive gradients.
- Glassmorphism.
- Excessive rounded cards.
- Unnecessary decorative charts.
- Random colorful icons.
- Excessive shadows.
- Emojis as interface icons.
- Huge headings without purpose.
- Repeated card layouts on every page.
- Fake data presented as real data.
- Unnecessary animations.
- Generic AI-generated dashboard patterns.

PREFER:
- Clean tables.
- Compact and purposeful statistics.
- Consistent alignment.
- Subtle borders.
- Restrained colors.
- Useful empty states.
- Clear loading and error states.
- Consistent button hierarchy.
- Professional form layouts.
- Meaningful confirmation dialogs.

Every component must serve a functional purpose.

## 6. Technical Requirements

Backend:
- Laravel.
- PHP 8.3 compatible.
- MySQL.
- Laravel migrations.
- Eloquent ORM.
- Form Request validation.
- Service classes for business logic.
- Database transactions.
- Authorization policies or middleware.

Frontend:
- Laravel Blade.
- Tailwind CSS.
- Alpine.js where necessary.
- Lucide icons.

Engineering rules:
- Follow Laravel conventions.
- Use reusable components.
- Keep controllers thin.
- Separate business logic from presentation.
- Never place important business logic inside Blade templates.
- Validate all user input on the server.
- Protect against unauthorized access.
- Use proper database indexes.
- Use CSRF protection.
- Store sensitive configuration in environment variables.
- Do not hardcode credentials.

## 7. Quality Assurance

Required tests:

Authentication:
- Valid login.
- Invalid login.
- Unauthorized access.

Products:
- Create product.
- Update product.
- Duplicate SKU prevention.

Purchases:
- Valid purchase increases stock.
- Invalid purchase does not change stock.

Sales:
- Valid sale decreases stock.
- Insufficient stock prevents sale.
- Concurrent sales cannot oversell inventory.

Inventory:
- Movement history is accurate.
- Cancelled transactions create appropriate reversal movements.
- Stock balances remain consistent.

Reports:
- Filters return correct records.
- Export contains the correct data.

## 8. Acceptance Criteria

The MVP is considered complete when:

1. Users can log in securely.
2. Admin can manage products, suppliers, and customers.
3. Staff can record purchases and sales.
4. Inventory automatically updates after valid transactions.
5. Invalid sales cannot create negative stock.
6. Every stock movement has an audit trail.
7. Dashboard displays actual database information.
8. Reports can be filtered and exported.
9. Role-based permissions work correctly.
10. The interface is responsive and visually consistent.
11. Critical business workflows have automated tests.
12. No critical errors occur during normal usage.

## 9. Development Strategy

Build in small, verifiable phases. Confirm the local MySQL service and database before the foundation phase. Do not implement all features in one step.

### Phase 1: Project Foundation and Access
- Configure and verify the MySQL connection.
- Configure Indonesian language and the Asia/Jakarta business timezone.
- Implement secure login/logout and server-enforced Owner/Staff permissions.
- Build the responsive application shell and shared design components.

### Phase 2: Database Foundation and Master Data
- Add the single MVP warehouse reference without multi-warehouse workflows.
- Implement products, suppliers, and customers with appropriate validation and relationships.
- Add product price defaults and audit records for selling-price changes.
- Ensure Staff cannot access purchase-price data through pages, queries, or responses.

### Phase 3: Inventory Ledger and Transaction Infrastructure
- Implement the permanent inventory movement ledger and `products.current_stock` balance.
- Use DECIMAL(15,3) quantities, exact arithmetic, database transactions, row locking, and server-side capacity validation.
- Implement request idempotency and concurrency-safe transaction-number sequences that allow gaps and never reuse allocated numbers.
- Store business dates separately from confirmation timestamps.

### Phase 4: Stock Adjustments and Initial Inventory
- Implement Owner-only initial stock and adjustment transactions with mandatory reasons.
- Update the ledger and current balance atomically; prohibit direct stock edits.

### Phase 5: Purchases and Stock In
- Implement multi-item purchase transactions and mobile-first review/confirmation.
- Let Staff use the configured default purchase price without exposing it.
- Allow Owner purchase-item price overrides with mandatory reasons and immutable price snapshots.
- Calculate rounded line subtotals and transaction totals on the server without floating-point arithmetic.

### Phase 6: Sales and Stock Out
- Implement multi-item sales and mobile-first review/confirmation.
- Enforce configured selling prices for Staff; allow Owner overrides with mandatory reasons and audit records.
- Revalidate available stock under row locks before confirming a sale.

### Phase 7: Cancellation and Reversal
- Allow Owner to cancel confirmed purchases, sales, and adjustments with a mandatory reason.
- Preserve original transactions and movements, create separately numbered reversal records and movements, and prevent duplicate cancellations.

### Phase 8: Dashboard, History, and Reports
- Build dashboard statistics from actual records using business_date and Asia/Jakarta for daily totals.
- Add searchable/filterable inventory history and inventory, purchase, and sales reports.
- Exclude cancelled transactions from active purchase and sales totals while retaining them in history; show reversals separately.
- Add CSV export and do not calculate or display net profit.

### Phase 9: MVP Verification and Hardening
- Verify authentication, permissions, validation, price visibility, audit history, transaction totals, duplicate submissions, cancellations, and stock consistency.
- Exercise concurrent stock operations against MySQL.
- Review responsive behavior at approximately 1440px, 1024px, 768px, and 390px.
- Fix critical security, correctness, accessibility, and performance issues before MVP acceptance.

### Phase 10: WhatsApp Integration (Future)
- Prepare the daily summary service and message template after the MVP.
- Add scheduling and the WhatsApp API only in a later phase.

The priority is a reliable, auditable inventory system, not the number of features.
## 10. Product Decisions

This section records the latest product decisions. If an earlier section conflicts with this section, follow the decisions below.

### 10.1 Platform and Responsive Experience

StockFlow must be a responsive web application that remains fully functional on desktop computers, laptops, tablets, and mobile phones, including small mobile screens.

**Desktop experience**
- Persistent sidebar navigation.
- Data tables with clear columns.
- Dashboard statistics.
- Multi-column forms where appropriate.
- Efficient, keyboard-friendly workflows.

**Mobile experience**
- Compact navigation.
- Single-column forms.
- Touch-friendly buttons and inputs.
- Readable transaction summaries.
- No horizontal page overflow.
- Important actions remain easy to access.
- Data tables adapt to readable mobile layouts or use controlled horizontal scrolling when necessary.

Do not simply shrink the desktop layout to fit mobile.

### 10.2 User Roles and Permissions

**Owner** has full administrative access and can:
- View all dashboard information.
- Manage products, suppliers, and customers.
- Create and review purchases and sales.
- View financial and inventory reports and export reports.
- Manage staff accounts and configure application settings.
- Review all transaction history.

**Staff** has operational access and can:
- View inventory and product details.
- Record incoming and outgoing stock.
- Select existing suppliers and customers.
- View transaction history and low-stock alerts.

Staff cannot:
- Manage user accounts or change application settings.
- Delete historical transactions.
- Access owner-only financial reports.
- Change product master data or stock adjustment settings.

Enforce all permissions on the server; hiding interface elements alone is not sufficient.

### 10.3 Mobile-First Transaction Workflows

Warehouse staff must be able to complete the following workflows comfortably on a smartphone.

**Incoming stock**
1. Open Stock In.
2. Select a supplier.
3. Select a product.
4. Enter the quantity.
5. Enter the purchase price if authorized.
6. Review the transaction.
7. Confirm the transaction.

**Outgoing stock**
1. Open Stock Out.
2. Select a customer.
3. Select a product.
4. Enter the quantity.
5. Review available stock.
6. Review the transaction.
7. Confirm the transaction.

Show clear success and error feedback, and prevent accidental duplicate submissions.

### 10.4 Business Usability and Localization

- Use Indonesian Rupiah (IDR) as the default currency.
- Use Indonesian for the initial interface.
- Use Asia/Jakarta as the initial business timezone, configurable later.
- Display dates in a familiar Indonesian format.
- Support decimal inventory quantities and use kilograms (kg) as the default unit.
- Keep interfaces simple for non-technical warehouse employees.

### 10.5 Design Quality

The design must feel like a professionally designed B2B SaaS product. Prioritize operational efficiency, information hierarchy, consistent spacing, accessible contrast, responsive behavior, clear form validation, useful empty states, and consistent components. Do not sacrifice usability for visual decoration.

---
## 11. Approved MVP Decisions

These approved decisions refine and take precedence over earlier requirements where they conflict.

### 11.1 Warehouse Scope

The MVP supports one warehouse only. Keep the data model reasonably extensible for future multiple warehouses, but do not implement multi-warehouse workflows or user-facing functionality in the MVP.

### 11.2 Initial Stock and Adjustments

Initial inventory must be recorded as a stock adjustment with a mandatory reason. Record the responsible user, date, quantity, and reason. Only the Owner may perform adjustments. Never set initial or adjusted stock by directly editing the product's current stock field.

### 11.3 Inventory Ledger and Balance

`inventory_movements` is the permanent inventory ledger. `products.current_stock` stores the current balance for efficient queries. Update both atomically in a database transaction and use appropriate row locking to prevent concurrent inventory errors. Keep the stored balance consistent with the ledger.

### 11.4 Price Access, Defaults, Overrides, and Snapshots

The Owner can view and modify purchase and selling prices and access purchase and sales financial summaries. Staff cannot view or modify purchase prices or modify selling prices. Enforce these permissions in the backend, queries, and API responses as well as the user interface.

When Staff records incoming stock, use the product's configured default purchase price without exposing it to Staff. The Owner may override the default purchase price for an individual purchase item; each override requires a reason. Store the configured default price at transaction time, the applied price, and any override reason as historical data. Later changes to product defaults must not change completed purchase items.

Staff may view the configured selling price, line subtotals, and transaction totals, but cannot change prices. The backend must enforce the configured selling price when processing Staff sales. The Owner may override the configured selling price for an individual sales item. An override requires a reason and records the original configured price and final applied price.

Record every change to a product's default selling price, including the old price, new price, user, and timestamp.

### 11.5 Adjustments and Cancellations

Only the Owner may perform stock adjustments or authorize transaction cancellations. Every adjustment and cancellation requires a reason. Confirmed purchases, sales, and stock adjustments must not be permanently deleted. Cancellation preserves the original transaction and audit history and creates appropriate reversal inventory movements. Record the cancellation reason, authorizing user, and confirmation timestamp. Prevent duplicate cancellations and reversals.

### 11.6 Reports and Profit

Use `business_date` for operational reports, date filters, and daily transaction calculations. Dashboard figures for today use the current date in the Asia/Jakarta business timezone. Cancelled purchases and sales are excluded from active purchase and sales totals, but remain visible in history with a clear cancelled status. Reversal movements are recorded separately and remain available for stock reconciliation and audit.

MVP reports may show total active purchase value, total active sales value, inventory quantities, and stock movements, subject to role permissions. Do not calculate or display net profit in the MVP. Any future profit calculation must use an explicitly selected inventory costing method, such as FIFO or weighted average; never estimate profit from the latest purchase price.

### 11.7 Currency, Amount Limits, and Pricing

Use IDR as the default currency. Store prices and monetary totals as whole rupiah amounts in BIGINT UNSIGNED columns, whose maximum value is 18,446,744,073,709,551,615. Server-side validation must reject values exceeding the database limit. Validate exact intermediate calculations so a unit price multiplied by quantity, a rounded line subtotal, or a summed transaction total cannot overflow the supported range.

Do not implement discounts or tax calculations in the MVP. Calculate each line subtotal as unit price multiplied by quantity, rounded to the nearest whole rupiah using `ROUND_HALF_UP`. Calculate the transaction total by summing the rounded line subtotals. Use consistent server-side rounding and do not use floating-point arithmetic for financial calculations.

### 11.8 Quantity Precision

Support inventory quantities with up to three decimal places. Store product balances, movement quantities, and transaction item quantities in `DECIMAL(15,3)` columns. Validate that inputs and resulting balances fit the database capacity. Avoid floating-point arithmetic for inventory calculations and display quantities with up to three decimal places.

### 11.9 Business Dates and Confirmation Timestamps

Store the business transaction date (`business_date`) separately from the confirmation timestamp (`confirmed_at`). Use `business_date` for operational reports and daily totals. Keep `confirmed_at` for audit. Transaction numbers use the local Asia/Jakarta calendar date on which the transaction is confirmed, not the business transaction date. Reversal numbers use the local Asia/Jakarta calendar date on which cancellation is confirmed.

### 11.10 Transaction Numbering

Generate unique transaction numbers sequentially per transaction type and confirmation date, safely under concurrent requests, and never reuse an allocated number. The sequence must be stored separately from the main business transaction so a failed transaction cannot cause an allocated number to be reused. Number gaps are allowed.

Use a minimum of four sequence digits and expand the sequence as needed beyond 9,999. Do not reject a valid transaction solely because the daily sequence exceeds four digits. Use these formats:

- Purchase: `PUR-YYYYMMDD-XXXX`
- Sales: `SAL-YYYYMMDD-XXXX`
- Stock adjustment: `ADJ-YYYYMMDD-XXXX`
- Reversal: `REV-YYYYMMDD-XXXX`
**END OF PRD**





