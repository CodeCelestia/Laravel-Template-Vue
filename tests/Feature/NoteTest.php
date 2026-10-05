<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_are_redirected_to_login_when_visiting_notes(): void
    {
        $response = $this->get(route('notes.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_access_notes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('notes.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_notes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('notes.view');
        $this->actingAs($user);

        $response = $this->get(route('notes.index'));

        $response->assertOk();
    }

    public function test_user_only_sees_their_own_notes(): void
    {
        $user1 = User::factory()->create();
        $user1->givePermissionTo('notes.view');

        $user2 = User::factory()->create();
        $user2->givePermissionTo('notes.view');

        Note::factory()->create([
            'user_id' => $user1->id,
            'title' => 'User 1 Note',
        ]);

        Note::factory()->create([
            'user_id' => $user2->id,
            'title' => 'User 2 Note',
        ]);

        $this->actingAs($user1);

        $response = $this->getJson(route('notes.index'));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'User 1 Note'])
            ->assertJsonMissing(['title' => 'User 2 Note']);
    }

    public function test_authorized_user_can_create_note(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['notes.view', 'notes.create']);
        $this->actingAs($user);

        $response = $this->postJson(route('notes.store'), [
            'title' => 'New Dev Note',
            'content' => 'Sample scratchpad content',
            'color' => 'orange',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('note.title', 'New Dev Note')
            ->assertJsonPath('note.color', 'orange');

        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'title' => 'New Dev Note',
            'color' => 'orange',
        ]);
    }

    public function test_authorized_user_can_update_note(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['notes.view', 'notes.edit']);
        $this->actingAs($user);

        $note = Note::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'content' => 'Old content',
            'color' => 'slate',
        ]);

        $response = $this->putJson(route('notes.update', $note), [
            'title' => 'Updated Title',
            'content' => 'New content from auto-save',
            'color' => 'purple',
            'is_pinned' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('note.title', 'Updated Title')
            ->assertJsonPath('note.color', 'purple')
            ->assertJsonPath('note.is_pinned', true);

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'title' => 'Updated Title',
            'color' => 'purple',
            'is_pinned' => true,
        ]);
    }

    public function test_user_cannot_update_another_users_note(): void
    {
        $user1 = User::factory()->create();
        $user1->givePermissionTo(['notes.view', 'notes.edit']);

        $user2 = User::factory()->create();
        $user2->givePermissionTo(['notes.view', 'notes.edit']);

        $note = Note::factory()->create([
            'user_id' => $user2->id,
            'title' => 'User 2 Note',
        ]);

        $this->actingAs($user1);

        $response = $this->putJson(route('notes.update', $note), [
            'title' => 'Hacked Title',
        ]);

        $response->assertForbidden();
    }

    public function test_authorized_user_can_delete_note(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['notes.view', 'notes.delete']);
        $this->actingAs($user);

        $note = Note::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson(route('notes.destroy', $note));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('notes', [
            'id' => $note->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_note(): void
    {
        $user1 = User::factory()->create();
        $user1->givePermissionTo(['notes.view', 'notes.delete']);

        $user2 = User::factory()->create();
        $user2->givePermissionTo(['notes.view', 'notes.delete']);

        $note = Note::factory()->create([
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1);

        $response = $this->deleteJson(route('notes.destroy', $note));

        $response->assertForbidden();
    }

    public function test_user_can_reorder_notes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['notes.view', 'notes.edit']);
        $this->actingAs($user);

        $note1 = Note::factory()->create(['user_id' => $user->id, 'order_column' => 0]);
        $note2 = Note::factory()->create(['user_id' => $user->id, 'order_column' => 1]);
        $note3 = Note::factory()->create(['user_id' => $user->id, 'order_column' => 2]);

        $response = $this->postJson(route('notes.reorder'), [
            'ordered_ids' => [$note3->id, $note1->id, $note2->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, $note3->fresh()->order_column);
        $this->assertSame(1, $note1->fresh()->order_column);
        $this->assertSame(2, $note2->fresh()->order_column);
    }
}
