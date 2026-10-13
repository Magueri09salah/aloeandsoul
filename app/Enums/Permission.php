<?php

namespace App\Enums;

/**
 * Fine-grained admin abilities. Each is registered as a Gate in
 * AppServiceProvider and granted to roles in AdminRole::permissions().
 */
enum Permission: string
{
    case ViewDashboard = 'dashboard.view';
    case ViewOrders = 'orders.view';
    case ManageOrders = 'orders.manage';
    case ViewCatalog = 'catalog.view';
    case ManageCatalog = 'catalog.manage';
    case ViewCustomers = 'customers.view';
    case AnonymizeCustomers = 'customers.anonymize';
    case ManageMarketing = 'marketing.manage';
    case ManageContent = 'content.manage';
    case ManageSettings = 'settings.manage';
    case ManageUsers = 'users.manage';
    case ViewAuditLog = 'audit.view';
}
