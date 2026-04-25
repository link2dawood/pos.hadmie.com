<claude-mem-context>
# Memory Context

# [pos.hadmie.com] recent context, 2026-04-25 6:40pm GMT+5

Legend: 🎯session 🔴bugfix 🟣feature 🔄refactor ✅change 🔵discovery ⚖️decision
Format: ID TIME TYPE TITLE
Fetch details: get_observations([IDs]) | Search: mem-search skill

Stats: 50 obs (19,226t read) | 584,828t work | 97% savings

### Apr 22, 2026
21 5:25p 🔵 Invoice Design Registry: 6 Named Designs; elegant_modified Exists But Is Not Registered
22 " 🔵 ZATCA QR Code (Saudi Arabia TLV Format) Already Implemented in TransactionUtil
23 " 🔵 Each Document Type Has Its Own Isolated Print Logic — No Shared Receipt Service
24 " 🔵 Receipt Data Builder: getReceiptDetails() Is a 1000+ Line Method Assembling Full Invoice State
25 5:27p 🔵 invoice_layouts Table Has show_barcode Column Since Initial Creation (2018)
26 " 🔵 BusinessLocation Has Two Invoice Layout Fields: invoice_layout_id and sale_invoice_layout_id
27 " 🔵 slim and slim2 Receipt Templates Are Functionally Identical — Different Header Alignment Only
28 5:28p 🔵 POS Document Rendering Architecture: Template System Overview
29 " 🔵 InvoiceLayout Model: Full Field Inventory and Template Control Fields
30 " 🔵 All Print/Download Routes Mapped Across POS System
31 " ⚖️ Barcode/QR Redesign Can Reuse Existing DB Fields — No New Columns Needed at Layout Level
32 5:29p 🔵 Barcodes Table Is for Sticker Label Layout Config, Not Product Barcode Values
33 " 🔵 Thermal Printer Hardware Path: Printer Model Schema and printerConfig() Flow
34 " 🔵 print_receipt_on_invoice Location Check Is Commented Out — Printing Always Enabled
35 " 🔵 POS JS Architecture: Compiled Static Assets with No Source in resources/js
36 5:30p 🔵 Barcode/QR Already Rendered in All 6 Receipt Templates via milon/barcode DNS1D/DNS2D
37 " 🔵 Product Barcode Schema: SKU on products Table, sub_sku on variations Table
38 " 🔵 Hardware ESC/POS Printer Path Fully Commented Out in pos.js — Always Falls Back to window.print()
39 " 🔵 LabelsController: Full Batch Barcode Label Print Flow for Products/Purchases
### Apr 25, 2026
71 5:34p 🔵 Laravel POS Project Structure at pos.hadmie.com
72 5:35p 🔵 Ultimate POS Laravel App — PHP Extension and Environment Requirements for Docker
77 " 🟣 Full Docker Stack Added to pos.hadmie.com
78 5:36p 🟣 Docker Infrastructure Files Confirmed Written and Validated
79 " 🔵 Docker Daemon Not Running on Host Machine
80 5:40p 🔵 Docker Socket Permission Denied on macOS
81 5:41p 🔵 Docker Desktop Successfully Started — Version 29.2.0
82 " 🟣 Docker Compose Build Started with phpMyAdmin
83 5:49p 🟣 Docker Setup Initiated for pos.hadmie.com POS Project
84 5:50p 🟣 Complete Docker File Set Created for pos.hadmie.com
85 " 🟣 Docker Compose Stack Fully Configured for pos.hadmie.com Laravel POS
86 " 🟣 Makefile Added with Full Docker Workflow Shortcuts
87 6:01p 🔵 Docker Build Failure: poshadmiecom-app Image Already Exists
88 6:03p 🔵 pos.hadmie.com Docker Compose Architecture Mapped
89 " 🔵 poshadmiecom-app Build Succeeds Standalone; Original Failure Was Stale Image Conflict
90 6:08p 🔵 pos-hadmie-app Container Crash-Looping After Stack Start
91 6:15p 🟣 Docker + phpMyAdmin Setup Requested on Port 8011
92 " 🔵 Docker App Container Crash-Looping: myfatoorah/laravel-package Missing from composer.lock
93 " 🔵 PHP and Composer Not Available on Host Machine
94 6:16p 🔵 composer update Fails With Same Exit Code 4 — Lock File Mismatch Blocks All Composer Commands
95 6:17p 🔴 composer.lock Repaired by Overriding Docker Entrypoint to Run Composer Directly
96 6:18p 🔵 Composer Update Fails With Permission Error: Cannot Create vendor/spatie Directory
97 " 🔴 composer.lock Successfully Updated With myfatoorah Packages Despite Exit Code 1
98 6:19p 🔴 composer install Now Succeeds — Full 190-Package Install Running After Lock File Fix
99 6:21p 🔵 composer install Failed at 100% — Corrupted zip Download for php-http/discovery
100 " ✅ vendor/ Directory Moved to Named Docker Volume to Fix Permission Issues
101 6:22p 🔵 Docker Files Are All New — Project Had No Prior Docker Setup
102 " 🔵 Dockerfile Uses Multi-Stage Build: composer:2 + php:8.2-apache
103 6:23p 🟣 Full Docker Stack Successfully Up: App + MySQL + phpMyAdmin With vendor_data Volume
104 6:35p 🟣 Docker + phpMyAdmin Setup Requested for Project on Port 8011
105 " 🟣 pos.hadmie.com Dockerized Stack Running Successfully on Port 8011

Access 585k tokens of past work via get_observations([IDs]) or mem-search skill.
</claude-mem-context>