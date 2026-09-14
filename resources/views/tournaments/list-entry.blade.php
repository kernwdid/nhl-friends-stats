<div class="d-flex align-items-start justify-content-between gap-3 w-100">
    <div class="min-w-0" style="overflow-wrap: anywhere;">
        <a href="{{ route('platform.tournament-info', ['id' => $tournament->id, 'sort' => '-points']) }}">
            {{ $tournament->name }}
        </a>
        <div class="d-none d-md-block small text-muted mt-1">
            {{ $tournament->archived ? 'Archiviert' : 'Nicht archiviert' }}
            · {{ __('general.created_at') }}: {{ \App\Helpers\DateHelper::formatDateTime($tournament->created_at->toDateTimeString()) }}
            · {{ __('general.updated_at') }}: {{ \App\Helpers\DateHelper::formatDateTime($tournament->updated_at->toDateTimeString()) }}
        </div>
    </div>
</div>
