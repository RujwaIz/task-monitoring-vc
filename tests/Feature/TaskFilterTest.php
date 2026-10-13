<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_q_filters_by_title_and_never_shows_another_users_tasks(): void
    {
        $amina = User::factory()->create();
        $daniel = User::factory()->create();
        Task::factory()->create(['user_id' => $amina->id, 'title' => 'Budget review']);
        Task::factory()->create(['user_id' => $amina->id, 'title' => 'Site visit']);
        Task::factory()->create(['user_id' => $daniel->id, 'title' => 'Budget']);

        $response = $this->actingAs($amina)->get('/?q=Budget');

        $response->assertOk()->assertSee('Budget review')->assertDontSee('Site visit');
        $this->assertCount(1, $response->viewData('tasks'));
        $this->assertSame($amina->id, $response->viewData('tasks')->first()->user_id);
    }

    public function test_q_treats_like_wildcards_literally(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => '100% done']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Something else']);

        $response = $this->actingAs($user)->get('/?q='.urlencode('%'));

        $this->assertCount(1, $response->viewData('tasks'));
    }

    public function test_q_is_escaped_in_the_page_and_kept_in_the_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/?q='.urlencode('<script>alert(1)</script>'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_q_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/')->get('/?q='.str_repeat('a', 181))
            ->assertRedirect('/')->assertSessionHasErrors('q');
        $this->actingAs($user)->from('/')->get('/?q[]=x')
            ->assertRedirect('/')->assertSessionHasErrors('q');
    }
}
