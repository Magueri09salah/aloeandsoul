<?php

namespace App\Enums;

enum AdminRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => Permission::cases(),
            self::Manager => [
                Permission::ViewDashboard,
                Permission::ViewOrders,
                Permission::ManageOrders,
                Permission::ViewCatalog,
                Permission::ManageCatalog,
                Permission::ViewCustomers,
                Permission::ManageMarketing,
                Permission::ManageContent,
            ],
            self::Staff => [
                Permission::ViewDashboard,
                Permission::ViewOrders,
                Permission::ManageOrders,
                Permission::ViewCatalog,
                Permission::ViewCustomers,
            ],
        };
    }

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Manager => 'Manager',
            self::Staff => 'Staff',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Everything, including settings, admin users and the audit log.',
            self::Manager => 'Orders, products, stock, customers, discounts, delivery zones and content.',
            self::Staff => 'Orders and customers. Can view products and stock but not change them.',
        };
    }

    /** @return list<array{value: string, label: string, description: string}> */
    public static function options(): array
    {
        return array_map(fn (self $r) => ['value' => $r->value, 'label' => $r->label(), 'description' => $r->description()], self::cases());
    }
}
