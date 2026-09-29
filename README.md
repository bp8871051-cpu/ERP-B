# ERP-Software-Backend

Enterprise-grade REST API backend for the ERP System, built with **PHP 8.2+**, **Laravel 11+**, **MySQL**, **Laravel Sanctum**, and **FilamentPHP**.

## Architecture & Modules

- **Inventory & Logistics**:
  - Centralized multi-warehouse stock management (`InventoryService`).
  - Authoritative inventory valuation (FIFO, Weighted Average, Cost Price).
  - Run-rate forecasting, stockout predictions, and automated reorder recommendations.
  - Multi-depot stock transfers and physical inventory reconciliation adjustments.
  - Granular transaction audit ledger (`inventory_transactions`).
- **Finance & Treasury**:
  - General ledger, operational expenses, payment transactions, and cashflow velocity.
  - Aggregated expense distribution analytics.
- **Enterprise CRM**:
  - Contacts, Leads, Deals, Pipeline stages, Campaigns, and Customer Feedback.
- **Procurement & Supply Chain**:
  - Purchase Orders, Goods Receipt Notes (GRN), and Vendor management.
- **Sales & Distribution**:
  - Sales Orders, Delivery Notes, Invoicing, POS integration, and Customer accounts.
- **Human Resources (HRM)**:
  - Employees, Departments, Designations, Attendance, Payroll, and Leave tracking.

## Tech Stack

- **PHP**: 8.2+
- **Framework**: Laravel 11.x
- **Database**: MySQL 8+ / MariaDB
- **Authentication**: Laravel Sanctum (Bearer Token & Stateful Sessions)
- **Admin Panel**: FilamentPHP v3
- **Cache**: File / Redis / Database with sub-millisecond query optimization

## Installation & Setup

```bash
# Clone the repository
git clone https://github.com/bp8871051-cpu/ERP-Software-Backend.git
cd ERP-Software-Backend

# Install Composer dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Configure your database credentials in .env, then run migrations and seeders:
php artisan migrate --seed

# Start the Laravel development server
php artisan serve
```

## API Documentation

All REST API endpoints are exposed under `/api/v1/`:

- `/api/v1/inventory/*`: Inventory dashboard, summary, movements, valuation trends, warehouse capacities, alerts, and mutations.
- `/api/v1/finance/*`: Revenue vs expenses, cashflow, expense distribution, payments.
- `/api/v1/crm/*`: Contacts, leads, pipeline, deals, feedback, analytics.
- `/api/v1/sales/*`: Invoices, quotes, orders, customers.
- `/api/v1/purchases/*`: Purchase orders, vendors, goods receipts.
