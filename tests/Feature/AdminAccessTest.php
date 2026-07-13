<?php

test('customer cannot access admin area', function () {
    $customer = createCustomerUser();

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin can access admin dashboard', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});
