<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolReview;
use Tests\TestCase;

class HasCommunityVotesTest extends TestCase
{
    use RefreshDatabase;

    private function createReview(): ToolReview
    {
        $tool = Tool::create(['name' => 'Test Tool', 'slug' => 'test-tool-'.uniqid(), 'status' => 'published']);

        return ToolReview::create([
            'directory_tool_id' => $tool->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 4,
            'title' => 'Test',
            'body' => 'Test body',
            'is_approved' => true,
        ]);
    }

    public function test_toggle_vote_creates_vote(): void
    {
        $user = User::factory()->create();
        $review = $this->createReview();

        $result = $review->toggleVote($user);

        $this->assertTrue($result);
        $this->assertEquals(1, $review->communityVoteCount());
    }

    public function test_toggle_vote_removes_existing(): void
    {
        $user = User::factory()->create();
        $review = $this->createReview();

        $review->toggleVote($user);
        $result = $review->toggleVote($user);

        $this->assertFalse($result);
        $this->assertEquals(0, $review->communityVoteCount());
    }

    public function test_has_voted_true(): void
    {
        $user = User::factory()->create();
        $review = $this->createReview();

        $review->toggleVote($user);

        $this->assertTrue($review->hasVoted($user));
    }

    public function test_has_voted_false_for_null(): void
    {
        $review = $this->createReview();

        $this->assertFalse($review->hasVoted(null));
    }

    public function test_community_vote_count(): void
    {
        $review = $this->createReview();
        $users = User::factory(3)->create();

        foreach ($users as $user) {
            $review->toggleVote($user);
        }

        $this->assertEquals(3, $review->communityVoteCount());
    }

    public function test_get_badge_tier_none(): void
    {
        $review = $this->createReview();

        $this->assertEquals('none', $review->getBadgeTier());
    }

    public function test_get_badge_tier_noticed(): void
    {
        $review = $this->createReview();

        foreach (User::factory(2)->create() as $user) {
            $review->toggleVote($user);
        }

        $this->assertEquals('noticed', $review->getBadgeTier());
    }

    public function test_get_badge_tier_approved(): void
    {
        $review = $this->createReview();

        foreach (User::factory(5)->create() as $user) {
            $review->toggleVote($user);
        }

        $this->assertEquals('approved', $review->getBadgeTier());
    }

    public function test_get_badge_tier_favorite(): void
    {
        $review = $this->createReview();

        foreach (User::factory(10)->create() as $user) {
            $review->toggleVote($user);
        }

        $this->assertEquals('favorite', $review->getBadgeTier());
    }
}
