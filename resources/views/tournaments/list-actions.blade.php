    <div class="d-flex flex-wrap justify-content-end gap-2 ms-auto flex-shrink-0">
    @can('view', $tournament)
    <a class="btn btn-sm btn-outline-secondary"
       href="{{ route('platform.resource.view', ['resource' => 'tournament-resources', 'id' => $tournament->id]) }}"><x-orchid-icon path="bs.eye" class="me-1" aria-hidden="true" />{{ __('tournaments.view') }}</a>
    @endcan
    @can('update', $tournament)
    <a class="btn btn-sm btn-outline-primary flex-shrink-0"
       href="{{ route('platform.resource.edit', ['resource' => 'tournament-resources', 'id' => $tournament->id]) }}"
       aria-label="{{ 'Turnier '.$tournament->name.' bearbeiten' }}">Bearbeiten</a>
    @endcan
    </div>
