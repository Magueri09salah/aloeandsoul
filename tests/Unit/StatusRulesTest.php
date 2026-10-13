<?php

use App\Enums\AdminRole;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;

it('allows only the documented order transitions', function () {
    expect(OrderStatus::PendingConfirmation->canTransitionTo(OrderStatus::Confirmed))->toBeTrue()
        ->and(OrderStatus::PendingConfirmation->canTransitionTo(OrderStatus::Shipped))->toBeFalse()
        ->and(OrderStatus::PendingConfirmation->canTransitionTo(OrderStatus::Delivered))->toBeFalse()
        ->and(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled))->toBeFalse()
        ->and(OrderStatus::Shipped->canTransitionTo(OrderStatus::FailedDelivery))->toBeTrue()
        ->and(OrderStatus::FailedDelivery->canTransitionTo(OrderStatus::Shipped))->toBeTrue()
        ->and(OrderStatus::Delivered->canTransitionTo(OrderStatus::Returned))->toBeTrue()
        ->and(OrderStatus::Delivered->canTransitionTo(OrderStatus::PendingConfirmation))->toBeFalse();
});

it('has terminal states with no way out', function () {
    expect(OrderStatus::Cancelled->allowedTransitions())->toBe([])
        ->and(OrderStatus::Returned->allowedTransitions())->toBe([]);
});

it('never allows an order to transition to itself', function () {
    foreach (OrderStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse();
    }
});

it('lets every non-terminal status reach a terminal one', function () {
    foreach (OrderStatus::cases() as $start) {
        $seen = [];
        $queue = [$start];
        while ($queue) {
            $s = array_shift($queue);
            $seen[$s->value] = $s;
            foreach ($s->allowedTransitions() as $next) {
                $seen[$next->value] ?? $queue[] = $next;
            }
        }
        $terminal = array_filter($seen, fn (OrderStatus $s) => $s->isTerminal());
        expect($terminal)->not->toBeEmpty();
    }
});

it('only allows cash to be marked collected on delivered orders', function () {
    expect(PaymentStatus::Pending->canChangeTo(PaymentStatus::Collected, OrderStatus::Delivered))->toBeTrue();

    foreach (OrderStatus::cases() as $status) {
        if ($status !== OrderStatus::Delivered) {
            expect(PaymentStatus::Pending->canChangeTo(PaymentStatus::Collected, $status))->toBeFalse();
        }
    }
});

it('only refunds cash that was collected on a returned order', function () {
    expect(PaymentStatus::Collected->canChangeTo(PaymentStatus::Refunded, OrderStatus::Returned))->toBeTrue()
        ->and(PaymentStatus::Pending->canChangeTo(PaymentStatus::Refunded, OrderStatus::Returned))->toBeFalse()
        ->and(PaymentStatus::Collected->canChangeTo(PaymentStatus::Refunded, OrderStatus::Delivered))->toBeFalse();
});

it('maps roles to permissions with owner-only areas', function () {
    expect(AdminRole::Owner->can(Permission::ManageSettings))->toBeTrue()
        ->and(AdminRole::Manager->can(Permission::ManageSettings))->toBeFalse()
        ->and(AdminRole::Manager->can(Permission::ManageCatalog))->toBeTrue()
        ->and(AdminRole::Staff->can(Permission::ManageCatalog))->toBeFalse()
        ->and(AdminRole::Staff->can(Permission::ManageOrders))->toBeTrue()
        ->and(AdminRole::Staff->can(Permission::ManageUsers))->toBeFalse()
        ->and(AdminRole::Manager->can(Permission::AnonymizeCustomers))->toBeFalse();
});
