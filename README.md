# WooCommerce Command Center

Custom WordPress/WooCommerce admin dashboard for centralizing order tracking, monthly revenue, customer follow-up actions and operational activity.

## Preview

![WooCommerce Command Center dashboard preview](assets/screenshot/dashboard-preview.png)

## Overview

WooCommerce Command Center is a custom WordPress plugin that extends WooCommerce with a focused internal dashboard for store operations, order tracking, revenue visibility and customer follow-up.

Instead of checking orders, students, products and follow-up actions across different WooCommerce screens, the plugin centralizes the most relevant operational data in one admin view.

## Features

- Custom WordPress admin dashboard
- Active WooCommerce product/course count
- Confirmed enrollment count
- Monthly revenue calculation
- Pending follow-up count
- Recent enrollments table
- Custom checkout meta field support for DNI
- Clean course names for variable products
- WhatsApp follow-up action per student
- Fallback behavior when WooCommerce is inactive
- Custom admin UI with separated template and assets

## Tech Stack

- WordPress
- WooCommerce
- PHP
- HTML
- CSS
- Figma
- Custom admin templates

## Plugin Structure

```text
lumina-course-command-center/
├── lumina-course-command-center.php
├── README.md
├── .gitignore
├── templates/
│   └── admin-dashboard.php
└── assets/
    ├── admin.css
    └── icons/
        └── whatsapp.svg
```

## Design Process

The dashboard interface was first drafted as a Figma prototype to define layout, visual hierarchy and UI direction before being implemented as a custom WordPress admin plugin.

## Data Sources

The plugin uses WooCommerce data through native WooCommerce/WordPress APIs:

- WooCommerce products
- WooCommerce orders
- Order status
- Order totals
- Billing data
- Custom checkout meta field: `user_identity_number`

## What the Dashboard Shows

The admin dashboard displays:

- Total active courses/products
- Confirmed enrollments
- Monthly revenue
- Pending follow-ups
- Latest 10 confirmed or processing course orders
- Student contact data from WooCommerce orders
- Course purchased
- Order status
- WhatsApp follow-up action

## Notes

This plugin was built for a real WordPress/WooCommerce education platform and adapted to its operational workflow.

Sensitive student/order data is not included in this repository. Screenshots used for portfolio purposes should hide personal information such as names, emails, phone numbers and DNI.

## Status

MVP / Live tested.
