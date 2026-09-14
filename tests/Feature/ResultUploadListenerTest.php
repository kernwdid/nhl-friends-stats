<?php

namespace Tests\Feature;

use App\Http\Controllers\VisionController;
use App\Models\User;
use App\Orchid\Layouts\ResultUploadListener;
use App\Orchid\Screens\VisionScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Repository;
use Tests\TestCase;

class ResultUploadListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_applies_results_clears_stale_values_and_keeps_tournament_teams(): void
    {
        $this->mock(VisionScreen::class)->shouldReceive('processResult')->once()->with(12)->andReturn([
            'shots_away' => 25, 'away_team_id' => 9, 'view_result' => '25 Shots', 'detection_percentage' => 7.69,
        ]);
        $repository = new Repository(['query' => ['away_team_id' => 4, 'home_team_id' => 8],
            'shots_home' => 99, 'home_user_id' => 3]);
        $result = (new ResultUploadListener)->handle($repository, Request::create('/', 'POST', ['game_result' => '12']));
        $this->assertSame(25, $result->get('shots_away'));
        $this->assertNull($result->get('shots_home'));
        $this->assertSame(4, $result->get('away_team_id'));
        $this->assertSame(8, $result->get('home_team_id'));
        $this->assertSame(3, $result->get('home_user_id'));
        $this->assertSame('25 Shots', $result->get('view_result'));
    }

    public function test_api_error_does_not_leave_old_image_statistics_in_the_form(): void
    {
        $this->mock(VisionScreen::class)->shouldReceive('processResult')->once()->with(12)->andReturn('Detection failed.');
        $result = (new ResultUploadListener)->handle(new Repository(['goals_away' => 8]), Request::create('/', 'POST', ['game_result' => 12]));
        $this->assertNull($result->get('goals_away'));
        $this->assertSame('Detection failed.', $result->get('view_result'));
        $this->assertSame(0, $result->get('detection_percentage'));
    }

    public function test_invalid_attachment_does_not_call_vision(): void
    {
        $this->mock(VisionScreen::class)->shouldNotReceive('processResult');
        foreach ([null, '', 'abc', ['12'], 0] as $value) {
            $repository = new Repository(['shots_away' => 2]);
            $result = (new ResultUploadListener)->handle($repository, Request::create('/', 'POST', ['game_result' => $value]));
            $this->assertSame(2, $result->get('shots_away'));
        }
    }

    public function test_real_upload_route_keeps_context_and_renders_recognized_time(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ocr/test.png', 'image fixture');
        $user = User::factory()->create(['permissions' => ['platform.index' => true]]);
        $attachment = Attachment::create([
            'name' => 'test', 'original_name' => 'test.png', 'mime' => 'image/png',
            'extension' => 'png', 'size' => 13, 'path' => 'ocr/', 'disk' => 'public',
            'hash' => 'testhash', 'user_id' => $user->id,
        ]);
        $this->mock(VisionController::class)->shouldReceive('getNHLResultFromImage')
            ->once()->with('image fixture')->andReturn([
                'time_in_offense_away_in_seconds' => 525, 'away_team_id' => 999,
                'view_result' => '8:45 Time on Attack', 'detection_percentage' => 8.33,
            ]);
        $page = $this->actingAs($user)->get('/upload_result?away_team_id=4&home_team_id=8');
        $page->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($page->getContent());
        $xpath = new \DOMXPath($document);
        $state = $xpath->query('//*[@id="screen-state"]')->item(0)->getAttribute('value');
        $decoded = Crypt::decrypt($state);
        $this->assertSame('4', $decoded->query['away_team_id']);

        $response = $this->post(route('platform.async.listener', [
            'screen' => Crypt::encryptString(VisionScreen::class),
            'layout' => Crypt::encryptString(ResultUploadListener::class),
        ]), ['_state' => $state, 'game_result' => $attachment->id, 'away_user_id' => $user->id]);
        $response->assertOk()->assertSee('08:45')->assertSee('8:45 Time on Attack');
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame('4', $xpath->query('//input[@name="away_team_id"]')->item(0)->getAttribute('value'));
        $this->assertSame('8', $xpath->query('//input[@name="home_team_id"]')->item(0)->getAttribute('value'));
        Storage::disk('public')->assertMissing('ocr/test.png');
    }
}
