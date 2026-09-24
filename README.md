# Garage Management System

A robust backend application built in native PHP to manage garage operations, tracking client orders, maintenance logs, and inventory. This project showcases complex database relationships and custom backend architecture without reliance on modern frameworks.

---

## Key Features
* **Vehicle & Client Tracking:** Complete CRUD system to manage customer records and vehicle history.
* **Maintenance Logs:** Relational tracking linking specific mechanical issues to inventory parts used.
* **Role-Based Access Control:** Simple, secure session-based authentication for admins and garage mechanics.
* **Dynamic Reporting:** Built-in SQL aggregation queries to track monthly revenue and part usage.

## Tech Stack
* **Backend:** Native PHP
* **Database:** MySQL
* **Frontend:** JS / jQuery / HTML5 / CSS3

## Database Architecture
This system utilizes a highly relational database schema. The structures of all tables, indexes, and primary/foreign key relationships can be found in the `garagedb.sql` file included in the root directory.

## Installation & Setup
To run this project locally, follow these steps:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/CharbelAhh/Garage-Sale-Manager.git
   ```
   
2. **Database Setup:**
   - Open your local database manager (e.g. phpMyAdmin).
   - Create a new database named garagedb.
   - Import garagedb.sql into the database.

3. **Running the application:**
   - Move the folder to your local server directory (e.g. htdocs for XAMPP or www for WAMP).
   - Open your browser and navigate to http://localhost/YourRepoName.

***Origin:** This application was originally created and delivered as a custom freelancing project in August 2025.*
