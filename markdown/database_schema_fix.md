# Database Schema Improvements for Garage Management System

## Missing Tables

Based on the code analysis, the following tables are referenced but missing from the database dump:

### 1. Products Table
```sql
CREATE TABLE IF NOT EXISTS `Products` (
  `ProductID` varchar(50) NOT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `Description` text,
  `DateAdded` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ProductID`),
  FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
```

### 2. productbrands Table
```sql
CREATE TABLE IF NOT EXISTS `productbrands` (
  `RelationID` int(11) NOT NULL AUTO_INCREMENT,
  `ProductID` varchar(50) NOT NULL,
  `BrandID` int(11) NOT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`RelationID`),
  FOREIGN KEY (`ProductID`) REFERENCES `Products` (`ProductID`) ON DELETE CASCADE,
  FOREIGN KEY (`BrandID`) REFERENCES `brand` (`BrandID`) ON DELETE CASCADE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
```

### 3. locationstock Table
```sql
CREATE TABLE IF NOT EXISTS `locationstock` (
  `LocationStockID` int(11) NOT NULL AUTO_INCREMENT,
  `RelationID` int(11) NOT NULL,
  `LocationID` int(11) NOT NULL,
  `ShelfNB` varchar(10) DEFAULT NULL,
  `RowNB` varchar(10) DEFAULT NULL,
  `Quantity` int(11) DEFAULT '0',
  PRIMARY KEY (`LocationStockID`),
  FOREIGN KEY (`RelationID`) REFERENCES `productbrands` (`RelationID`) ON DELETE CASCADE,
  FOREIGN KEY (`LocationID`) REFERENCES `locations` (`LocationID`) ON DELETE CASCADE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
```

## Schema Improvements

### 1. Add Foreign Key Constraints
The existing tables should have proper foreign key constraints:

```sql
-- Add foreign key to audit_logs for consistency
ALTER TABLE `audit_logs` 
ADD CONSTRAINT `fk_audit_category` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`) ON DELETE SET NULL;

-- Add foreign key to clients for consistency
ALTER TABLE `clients` 
ADD CONSTRAINT `fk_client_location` FOREIGN KEY (`LocationID`) REFERENCES `locations` (`LocationID`) ON DELETE SET NULL;
```

### 2. Add Indexes for Performance
```sql
-- Add indexes for frequently queried columns
ALTER TABLE `Products` ADD INDEX `idx_category` (`CategoryID`);
ALTER TABLE `Products` ADD INDEX `idx_date_added` (`DateAdded`);
ALTER TABLE `productbrands` ADD INDEX `idx_product_brand` (`ProductID`, `BrandID`);
ALTER TABLE `locationstock` ADD INDEX `idx_location_relation` (`LocationID`, `RelationID`);
ALTER TABLE `locationstock` ADD INDEX `idx_quantity` (`Quantity`);
```

## Data Integrity Improvements

### 1. Add Constraints
```sql
-- Add constraints to ensure data integrity
ALTER TABLE `Products` 
MODIFY `ProductID` varchar(50) NOT NULL UNIQUE;

ALTER TABLE `locationstock` 
MODIFY `Quantity` int(11) DEFAULT '0' CHECK (`Quantity` >= 0);
```

### 2. Add Timestamps
```sql
-- Add update timestamp to track modifications
ALTER TABLE `Products` 
ADD COLUMN `DateUpdated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `productbrands` 
ADD COLUMN `DateUpdated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `locationstock` 
ADD COLUMN `DateUpdated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;