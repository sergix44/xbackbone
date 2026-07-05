<?php

use Illuminate\Http\Request;
use Livewire\Livewire;
use XBB\Actions\Resource\ListResources;
use XBB\Livewire\Dashboard;
use XBB\Models\Resource;
use XBB\Models\User;

test('the dashboard lists the users resources and hides other users resources', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Resource::factory()->for($user)->create(['name' => 'My Resource']);
    Resource::factory()->for($other)->create(['name' => 'Their Resource']);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('My Resource')
        ->assertDontSee('Their Resource');
});

test('resources are listed newest first by default', function () {
    $user = User::factory()->create();

    Resource::factory()->for($user)->create(['name' => 'Older One', 'created_at' => now()->subDay()]);
    Resource::factory()->for($user)->create(['name' => 'Newer One', 'created_at' => now()]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSeeInOrder(['Newer One', 'Older One']);
});

test('the listing can be searched by name, filename or code', function () {
    $user = User::factory()->create();

    Resource::factory()->for($user)->create(['name' => 'Findable Note']);
    Resource::factory()->for($user)->create(['name' => null, 'filename' => 'invoice.pdf']);
    Resource::factory()->for($user)->create(['name' => null, 'filename' => null, 'code' => 'SECRETCODE']);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->set('search', 'Findable')
        ->assertSee('Findable Note')
        ->assertDontSee('invoice.pdf')
        ->set('search', 'invoice')
        ->assertSee('invoice.pdf')
        ->assertDontSee('Findable Note')
        ->set('search', 'SECRETCODE')
        ->assertSee('SECRETCODE')
        ->assertDontSee('invoice.pdf');
});

test('changing the search resets pagination to the first page', function () {
    $user = User::factory()->create();
    Resource::factory()->for($user)->count(25)->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->set('search', 'no-such-resource')
        ->assertSet('paginators.page', 1);
});

test('resources can be sorted by a column and direction', function () {
    $user = User::factory()->create();

    Resource::factory()->for($user)->create(['name' => 'Small', 'size' => 100]);
    Resource::factory()->for($user)->create(['name' => 'Medium', 'size' => 200]);
    Resource::factory()->for($user)->create(['name' => 'Large', 'size' => 300]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('setSort', 'size')            // defaults to descending
        ->assertSeeInOrder(['Large', 'Medium', 'Small'])
        ->call('toggleSortDirection')        // descending -> ascending
        ->assertSeeInOrder(['Small', 'Medium', 'Large']);
});

test('the name sort falls back to filename and code like the displayed name', function () {
    $user = User::factory()->create();

    Resource::factory()->for($user)->create(['name' => 'zebra']);
    Resource::factory()->for($user)->create(['name' => null, 'filename' => 'apple.bin']);
    Resource::factory()->for($user)->create(['name' => 'mango']);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('setSort', 'name')
        ->call('toggleSortDirection')        // descending -> ascending
        ->assertSeeInOrder(['apple.bin', 'mango', 'zebra']);
});

test('setSort ignores columns that are not offered in the UI', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('setSort', 'password')
        ->assertSet('sortColumn', 'created_at');
});

test('the listing state is restored from the query string on load', function () {
    $user = User::factory()->create();

    Resource::factory()->for($user)->create(['name' => 'Keep Me', 'size' => 100]);
    Resource::factory()->for($user)->create(['name' => 'Hide Me', 'size' => 300]);

    $this->actingAs($user);

    // Simulate landing on (or refreshing) a URL that already carries the filters.
    Livewire::withQueryParams(['search' => 'Keep', 'sortColumn' => 'size', 'sortDirection' => 'asc'])
        ->test(Dashboard::class)
        ->assertSet('search', 'Keep')
        ->assertSet('sortColumn', 'size')
        ->assertSet('sortDirection', 'asc')
        ->assertSee('Keep Me')
        ->assertDontSee('Hide Me');
});

test('ListResources honours the same query string vocabulary the API uses', function () {
    $user = User::factory()->create();

    Resource::factory()->for($user)->create(['name' => 'match small', 'size' => 100]);
    Resource::factory()->for($user)->create(['name' => 'match large', 'size' => 300]);
    Resource::factory()->for($user)->create(['name' => 'unrelated', 'size' => 200]);

    // Simulate an API GET carrying filter/sort in the query string.
    $this->app->instance('request', Request::create('/?filter[search]=match&sort=-size'));

    $result = app(ListResources::class)($user);

    expect(collect($result->items())->pluck('name')->all())->toBe(['match large', 'match small']);
});
