# AGENTS.md — StockFlow

## 1. Project Overview

StockFlow is a professional, responsive inventory management system for rice distribution and warehouse businesses.

Read PRD.md before implementing any feature.

The PRD is the primary source of product requirements.

## 2. Development Principles

- Build incrementally.
- Prioritize reliability over speed.
- Follow Laravel conventions.
- Write maintainable and readable code.
- Avoid unnecessary dependencies.
- Do not implement features outside the approved PRD without discussion.
- Never assume business rules when they are unclear.
- Explain important architectural decisions.

## 3. Technology Stack

- PHP 8.3+
- Laravel
- MySQL
- Laravel Blade
- Tailwind CSS
- Alpine.js
- Lucide Icons
- Chart.js when needed

Do not introduce React, Vue, or another frontend framework without approval.

## 4. Architecture Rules

- Keep controllers thin.
- Use Form Requests for validation.
- Use service classes for important business operations.
- Use Eloquent relationships correctly.
- Use database transactions for stock operations.
- Apply row locking when updating inventory.
- Use authorization policies or middleware.
- Keep business logic out of Blade templates.
- Use reusable Blade components.
- Use migrations for database changes.
- Never manually modify production database schemas.

## 5. Inventory Integrity

Inventory accuracy is critical.

- Never allow negative stock.
- Every stock movement must be recorded.
- Never silently delete completed transactions.
- Use reversal transactions for cancellations.
- Prevent duplicate transaction submissions.
- Ensure concurrent transactions cannot oversell inventory.
- Keep stock balances consistent with inventory movements.
- Validate all inventory changes on the server.

## 6. Design System Rules

Design direction: Premium, minimalist B2B SaaS.

Visual inspiration:

- Linear.
- Stripe Dashboard.
- Vercel Dashboard.

Do not copy their interfaces.

### Visual Rules

- Use consistent typography.
- Use a restrained color palette.
- Use consistent spacing.
- Prefer subtle borders over heavy shadows.
- Use consistent button sizes.
- Keep forms easy to understand.
- Make tables readable.
- Use Lucide icons consistently.
- Use meaningful visual hierarchy.
- Prioritize real content over decoration.

### Strictly Avoid AI Slop

DO NOT:

- Add gradients without a functional reason.
- Use glassmorphism.
- Add excessive rounded cards.
- Use excessive shadows.
- Add decorative charts.
- Use random icon styles.
- Use emojis as interface icons.
- Create oversized headings without purpose.
- Repeat identical card layouts on every page.
- Add unnecessary animations.
- Use fake statistics.
- Generate placeholder content that looks like real business data.

Every visual element must have a clear purpose.

## 7. Responsive Design

All pages must work on:

- Desktop.
- Laptop.
- Tablet.
- Mobile.

Mobile is not an afterthought.

Requirements:

- No unintended horizontal overflow.
- Touch-friendly controls.
- Responsive navigation.
- Mobile-friendly forms.
- Readable tables.
- Appropriate spacing on small screens.
- Important actions remain accessible.

Test layouts at approximately:

- 1440px desktop.
- 1024px tablet.
- 768px tablet/mobile transition.
- 390px mobile.

## 8. Security

- Validate input on the server.
- Enforce role permissions on the server.
- Use CSRF protection.
- Hash passwords securely.
- Protect sensitive configuration with environment variables.
- Never expose credentials.
- Prevent unauthorized data modification.
- Follow Laravel security best practices.

## 9. Testing

After implementing a feature:

1. Run relevant automated tests.
2. Check validation behavior.
3. Check authorization behavior.
4. Check edge cases.
5. Check responsive behavior when UI changes.
6. Fix failures before continuing.

Do not claim that a feature works without verifying it.

## 10. Codex Workflow

Before coding:

1. Read PRD.md.
2. Read this AGENTS.md.
3. Inspect the existing project.
4. Understand the existing architecture.
5. Prepare a small implementation plan.

During coding:

- Work in small steps.
- Avoid unnecessary file modifications.
- Preserve existing working features.
- Explain important changes.

After coding:

- Summarize changed files.
- Explain what was implemented.
- Report tests executed and their results.
- Mention anything incomplete.
- Do not silently ignore errors.

## 11. Communication

Explain technical concepts in clear, simple language.

When a decision has significant architectural or business consequences, explain the options before choosing.

Do not make major product decisions without approval.
