<?php

use App\Models\Report;
use App\Models\User;

// --- Submitting reports ---

test('unauthenticated users cannot submit a report', function () {
    $user = User::factory()->create();
    $this->postJson(route('report.store', $user->id), ['reason' => 'Spam'])
        ->assertStatus(401);
});

test('unverified users cannot submit a report', function () {
    $reporter = User::factory()->unverified()->create();
    $user = User::factory()->create();
    $this->actingAs($reporter)
        ->postJson(route('report.store', $user->id), ['reason' => 'Spam'])
        ->assertStatus(403);
});

test('a user cannot report themselves', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson(route('report.store', $user->id), ['reason' => 'Spam'])
        ->assertStatus(422);
});

test('a valid report is stored', function () {
    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    $this->actingAs($reporter)
        ->postJson(route('report.store', $reported->id), [
            'reason'  => 'Sending spam messages',
            'details' => 'They kept messaging me.',
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('reports', [
        'reported_by'      => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason'           => 'Sending spam messages',
        'status'           => 'pending',
    ]);
});

test('a user cannot submit a duplicate pending report for the same user', function () {
    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    Report::create([
        'reported_by'      => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason'           => 'First report',
        'status'           => 'pending',
    ]);

    $this->actingAs($reporter)
        ->postJson(route('report.store', $reported->id), ['reason' => 'Second report'])
        ->assertStatus(422);
});

test('reason is required', function () {
    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    $this->actingAs($reporter)
        ->postJson(route('report.store', $reported->id), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});

test('details cannot exceed 500 characters', function () {
    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    $this->actingAs($reporter)
        ->postJson(route('report.store', $reported->id), [
            'reason'  => 'Spam',
            'details' => str_repeat('a', 501),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['details']);
});

test('a second report can be submitted once the first is no longer pending', function () {
    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    Report::create([
        'reported_by'      => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason'           => 'Old report',
        'status'           => 'dismissed',
    ]);

    $this->actingAs($reporter)
        ->postJson(route('report.store', $reported->id), ['reason' => 'New reason'])
        ->assertStatus(201);
});

// --- Admin report management ---

test('admin can list reports for a user', function () {
    $admin = User::factory()->admin()->create();
    $reported = User::factory()->create();
    $reporter = User::factory()->create();

    Report::create([
        'reported_by'      => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason'           => 'Test report',
        'status'           => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->getJson("/api/admin/users/{$reported->id}/reports");

    $response->assertOk();
    $data = $response->json();
    expect(count($data))->toBe(1);
    expect($data[0]['reason'])->toBe('Test report');
    expect($data[0]['reported_by_id'])->toBe($reporter->id);
    expect($data[0]['reported_by_name'])->toBe($reporter->name);
});

test('admin can dismiss a report', function () {
    $admin = User::factory()->admin()->create();
    $report = Report::create([
        'reported_by'      => User::factory()->create()->id,
        'reported_user_id' => User::factory()->create()->id,
        'reason'           => 'Test',
        'status'           => 'pending',
    ]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/reports/{$report->id}", ['status' => 'dismissed'])
        ->assertOk();

    $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'dismissed']);
});

test('admin can mark a report as reviewed', function () {
    $admin = User::factory()->admin()->create();
    $report = Report::create([
        'reported_by'      => User::factory()->create()->id,
        'reported_user_id' => User::factory()->create()->id,
        'reason'           => 'Test',
        'status'           => 'pending',
    ]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/reports/{$report->id}", ['status' => 'reviewed'])
        ->assertOk();

    $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'reviewed']);
});

test('report is preserved when reporter deletes their account', function () {
    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    $report = Report::create([
        'reported_by'      => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason'           => 'Preserved report',
        'status'           => 'pending',
    ]);

    $reporter->delete();

    $this->assertDatabaseHas('reports', [
        'id'          => $report->id,
        'reported_by' => null,
    ]);
});
