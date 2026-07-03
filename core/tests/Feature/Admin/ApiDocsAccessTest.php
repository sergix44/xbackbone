<?php

use XBB\Models\User;
use Laravel\Pennant\Feature;

test('guests cannot view the api docs while they are restricted', function () {
    Feature::deactivate('public-api-docs');

    $this->get('/docs/api')->assertForbidden();
});

test('authenticated users can always view the api docs', function () {
    Feature::deactivate('public-api-docs');

    $this->actingAs(User::factory()->create());

    $this->get('/docs/api')->assertOk();
});

test('guests can view the api docs once they are made public', function () {
    Feature::activate('public-api-docs');

    $this->get('/docs/api')->assertOk();
});
