# Laravel 13 and tournament ratings

## Deploy

Use PHP 8.3 or newer with the extensions required by Composer and your database driver. The lockfile targets PHP 8.3; Sail now uses its 8.3 runtime. PHPUnit 12 is retained for PHP 8.3 compatibility.

~~~sh
composer install
php artisan migrate
php artisan orchid:publish
php artisan optimize:clear
~~~

Back up the database before upgrading. The rating migration stores previous values in nhl27_rating_backups; it updates existing NHL teams without renaming them or changing IDs/history. Fresh databases without team rows can migrate, then must be seeded and imported with the command below. All-Star teams are excluded.

The NHL 27 snapshot was retrieved on 2026-09-13 from NHL Ratings; every source URL is included in database/data/nhl27-ratings-2026-09-13.json. This is a third-party rating database, not an official EA API. Utah is matched by UTA and Winnipeg by WIN or WPG.

## Daily updates

The Laravel scheduler runs teams:sync-ratings daily at 06:00 in the application's timezone, without overlapping. Enable the normal scheduler on the application server:

~~~cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
~~~

Run manually or import a reviewed file using the bundled JSON structure:

~~~sh
php artisan teams:sync-ratings --dry-run
php artisan teams:sync-ratings
php artisan teams:sync-ratings --file=database/data/nhl27-ratings-2026-09-13.json --dry-run
php artisan teams:sync-ratings --file=database/data/nhl27-ratings-2026-09-13.json
~~~

The source must remain accessible and retain its NHL 27 page format. Direct HTTP access returned 403 during development, although the snapshot pages could be verified through web browsing. Test access from the deployment host; monitor the command's failure exit status and Laravel log. A blocked request, wrong edition, invalid rating, missing team or incomplete dataset leaves all previous ratings intact. A reviewed JSON import is available when automated access is blocked.

## Round behavior

New tournaments schedule player pairings immediately, with exactly the requested game count per player and a home/away difference of at most one. Opponent counts differ by at most one. Odd numbers of players require an even game count per player. Rounds contain approximately equal numbers of matches.

The first authenticated opening of the tournament page starts its current round. Only that round receives NHL teams, using one committed snapshot of the current database ratings and the configured maximum overall-rating difference. The next round becomes current after every result in the previous round is saved and is drawn when its page is next opened. The source is refreshed by the scheduler, not by every page request.

Once assigned, teams and their recorded overall ratings stay fixed, even if the daily ratings change. Refreshes cannot redraw a round. Published draws from existing tournaments are preserved, including their future rounds. A failed draw reports a validation error; it never relaxes the rating limit. Result submissions must match the assigned players/teams, belong to a current-round participant, and cannot overwrite an existing result.

Tournament names can be edited, but settings/participants cannot change after scheduling. Rolling back the deferred-assignment migration requires completing or removing tournaments with unassigned teams first. Rolling back the rating migration restores its saved ratings, including after subsequent syncs.

## Validation

~~~sh
composer validate --strict
composer check-platform-reqs
php artisan route:list
php artisan schedule:list
php vendor/bin/phpunit
~~~

Tests use an isolated in-memory SQLite database and cover scheduling, delayed assignments, changed ratings, published draws, invalid/duplicate results, rendered tournament pages, import failure behavior and rating migration rollback. Production data is not used for tests.
