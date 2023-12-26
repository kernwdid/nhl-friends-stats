<?php

namespace App\Orchid\Screens;

use App\Helpers\DateHelper;
use App\Http\Controllers\VisionController;
use App\Models\Game;
use App\Models\Round;
use App\Orchid\Layouts\ResultUploadListener;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Storage;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layout;
use Orchid\Screen\Screen;
use function DeepCopy\deep_copy;

class VisionScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        return ['query' => $request->query()];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('games.upload_result');
    }

    /**
     * The screen's action buttons.
     *
     * @return iterable<Action>
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('games.save'))->icon('plus')
                ->method('saveGame'),
        ];
    }

    public function saveGame(Request $request): Redirector|Application|RedirectResponse
    {
        $data = $request->validate([
            '*' => 'required',
            'game_result' => 'exclude',
        ]);

        $roundId = null;
        if (array_key_exists('round_id', $data)) {
            $roundId = deep_copy($data['round_id']);
            unset($data['round_id']);
        }

        $tournamentId = null;
        if (array_key_exists('tournament_id', $data)) {
            $tournamentId = deep_copy($data['tournament_id']);
            unset($data['tournament_id']);
        }

        if (array_key_exists('game_result', $data)) {
            unset($data['game_result']);
        }

        $data['time_in_offense_home_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['time_in_offense_home_in_seconds']);
        $data['time_in_offense_away_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['time_in_offense_away_in_seconds']);

        $data['pass_percentage_home'] = floatval($data['pass_percentage_home']);
        $data['pass_percentage_away'] = floatval($data['pass_percentage_away']);

        if (array_key_exists('penalty_minutes_home_in_seconds', $data) && $data['penalty_minutes_home_in_seconds'] !== null) {
            $data['penalty_minutes_home_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['penalty_minutes_home_in_seconds']);
        }

        if (array_key_exists('penalty_minutes_away_in_seconds', $data) && $data['penalty_minutes_away_in_seconds'] !== null) {
            $data['penalty_minutes_away_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['penalty_minutes_away_in_seconds']);
        }

        if (array_key_exists('powerplay_time_home_in_seconds', $data) && $data['powerplay_time_home_in_seconds'] !== null) {
            $data['powerplay_time_home_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['powerplay_time_home_in_seconds']);
        }

        if (array_key_exists('powerplay_time_away_in_seconds', $data) && $data['powerplay_time_away_in_seconds'] !== null) {
            $data['powerplay_time_away_in_seconds'] = DateHelper::getSecondsFromMinutesAndSeconds($data['powerplay_time_away_in_seconds']);
        }

        $game = new Game($data);
        $game->save();

        if ($roundId) {
            $round = Round::find($roundId);
            $round->game_id = $game->id;
            $round->save();
        }

        if ($tournamentId) {
            return redirect('/tournament-info/' . $tournamentId);
        }

        return redirect('/');
    }

    public function processResult(int $attachmentId)
    {
        $attachment = Attachment::find($attachmentId);

        if ($attachment) {
            $path = $attachment->path . $attachment->name . '.' . $attachment->extension;
            $fileContent = Storage::disk($attachment->disk)->get($path);

            $visionController = new VisionController();
            $res = $visionController->getNHLResultFromImage($fileContent);

            Storage::delete($path);

            return $res;
        }
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return array<Layout>|array<string>
     */
    public function layout(): iterable
    {
        return [
            ResultUploadListener::class,
        ];
    }
}
