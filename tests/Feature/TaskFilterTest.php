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

    public function test_status_filter_shows_only_matching_tasks_of_the_signed_in_user(): void
    {
        $amina = User::factory()->create();
        $daniel = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $amina->id, 'status' => 'Not started']);
        Task::factory()->count(2)->create(['user_id' => $amina->id, 'status' => 'Complete']);
        Task::factory()->count(4)->create(['user_id' => $daniel->id, 'status' => 'Complete']);

        $tasks = $this->actingAs($amina)->get('/?status=Complete')->assertOk()->viewData('tasks');

        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->every(fn ($t) => $t->user_id === $amina->id && $t->status->value === 'Complete'));
    }

    public function test_unknown_or_malformed_status_is_ignored(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id]);

        foreach (['/?status=Blocked', '/?status[]=Complete', '/?status='] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
            $this->assertCount(3, $this->actingAs($user)->get($url)->viewData('tasks'));
        }
    }

    public function test_status_is_kept_in_the_form_and_combines_with_q(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Budget one', 'status' => 'Complete']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Budget two', 'status' => 'Not started']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Other', 'status' => 'Complete']);

        $response = $this->actingAs($user)->get('/?status=Complete&q=Budget');

        $response->assertSee('Budget one')->assertDontSee('Budget two')->assertDontSee('Other');
        $response->assertSee('<option value="Complete" selected>Complete</option>', false);
    }

    public function test_priority_filter_shows_only_matching_tasks_of_the_signed_in_user(): void
    {
        $amina = User::factory()->create();
        $daniel = User::factory()->create();
        Task::factory()->count(2)->create(['user_id' => $amina->id, 'priority' => 'High']);
        Task::factory()->count(3)->create(['user_id' => $amina->id, 'priority' => 'Low']);
        Task::factory()->count(4)->create(['user_id' => $daniel->id, 'priority' => 'High']);

        $response = $this->actingAs($amina)->get('/?priority=High')->assertOk();

        $tasks = $response->viewData('tasks');
        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->every(fn ($t) => $t->user_id === $amina->id && $t->priority->value === 'High'));
        $response->assertSee('<option value="High" selected>High</option>', false);
    }

    public function test_unknown_or_malformed_priority_is_ignored(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id]);

        foreach (['/?priority=Urgent', '/?priority[]=High', '/?priority='] as $url) {
            $this->assertCount(3, $this->actingAs($user)->get($url)->assertOk()->viewData('tasks'));
        }
    }

    public function test_priority_and_status_combine(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Match', 'priority' => 'High', 'status' => 'Complete']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Wrong status', 'priority' => 'High', 'status' => 'Not started']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Wrong priority', 'priority' => 'Low', 'status' => 'Complete']);

        $this->actingAs($user)->get('/?priority=High&status=Complete')
            ->assertSee('Match')->assertDontSee('Wrong status')->assertDontSee('Wrong priority');
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
