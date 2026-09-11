# ColdConnect - Technical Architecture & Schema

## 1. System Overview

ColdConnect is engineered as a lightweight, reliable, high-performance web application designed to run seamlessly in rural and urban agricultural centers. It operates without external paid dependencies, heavy cloud overhead, or complex setup pipelines.

```mermaid
graph TD
    Client[Browser: Farmer or Storage Owner] -->|HTTP / HTTPS Request| WebServer[Apache 2.4 Server on XAMPP]
    WebServer -->|PHP 8.2 Execution| AppLayer[ColdConnect Application Core]
    
    subgraph AppLayer [ColdConnect Application Core]
        Auth[Authentication & Session Guard]
        Engine[Smart Match Engine: 5 Rule Factors]
        CostCalc[Dynamic Rental Cost Estimator]
        CapacityMgr[Transactional Capacity Manager]
    end
    
    AppLayer -->|PDO with Prepared Statements| Database[(MySQL 8 / MariaDB: coldconnect)]
    
    subgraph Database [Database Entities]
        Users[users Table]
        Storages[cold_storages Table]
        Bookings[bookings Table]
    end
```

---

## 2. Database Schema & Entity Relationships

```mermaid
erDiagram
    users ||--o{ cold_storages : "owns"
    users ||--o{ bookings : "reserves"
    cold_storages ||--o{ bookings : "allocates"

    users {
        int id PK
        string name
        string email UK
        string password "Bcrypt Hash"
        string phone
        enum role "farmer, owner"
        string location
        timestamp created_at
    }

    cold_storages {
        int id PK
        int owner_id FK
        string name
        string location
        decimal available_capacity
        decimal total_capacity
        string temperature
        decimal price_per_kg
        text supported_crops
        decimal minimum_quantity
        string contact
        enum status "Available, Full, Inactive"
        timestamp created_at
    }

    bookings {
        int id PK
        int farmer_id FK
        int storage_id FK
        string crop
        decimal quantity
        date start_date
        date end_date
        decimal total_cost
        enum status "Pending, Accepted, Rejected, Completed"
        timestamp created_at
    }
```

---

## 3. Data Integrity & Concurrency Safeguards

1. **Prepared Statements Everywhere:** All database interactions use PDO parameter binding to prevent SQL injection vulnerabilities.
2. **ACID Transaction on Booking Acceptance:** When an owner accepts a reservation, the system initiates a PDO transaction:
   ```php
   $pdo->beginTransaction();
   // 1. Verify capacity >= booking quantity
   // 2. UPDATE bookings SET status = 'Accepted' WHERE id = ?
   // 3. UPDATE cold_storages SET available_capacity = available_capacity - ? WHERE id = ?
   $pdo->commit();
   ```
3. **Session Authentication & Guard Middleware:** Helper routines `requireFarmer()` and `requireOwner()` inspect `$_SESSION['user_role']` on protected pages, preventing privilege escalation.
