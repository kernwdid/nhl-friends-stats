
# NHL Friends Stats

Since it is not possible to track NHL Online Versus Games (unranked) against friends,
I created a web application to track all the scores, create tournaments and show some statistics for you and your friends.

NEW: Team statistics are updated every day from www.nhlratings.net. In tournaments new ratings are taken into account for new rounds. See "Get daily team statistics update" section for the installation.

Feel free to use it and if you want to collaborate or have any questions, contact me at any time.

<a href="https://www.buymeacoffee.com/didiweinh" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/default-orange.png" alt="Buy Me A Coffee" height="41" width="174"></a>

## Statistics

- Total games
- Total goals
- Total shorthanded goals
- Average goals per game
- Total shots
- Average shots for one goal
- Total hits

Against each player you played against the following stats are available:

- Wins against player
- Losses against player
- Highest win (largest difference)
- Highest loss (largest difference)
- Average goals against player
- Average shots against player
- Average checks against player
- Average pass percentage against player
- Average time in offense against player
- Powerplay possibility
- Powerplay score probability

## Content

In addition, the application provides:

- Tournament mode
- Role and user management
- Login & Authentication
- CRUD of games
- Upload game results and processing with  Google Vision API OCR
- CRUD of teams and their strengths
- Language support for german and english (not 100%)

## OCR

To use the upload result feature with the Google Vision API OCR processing, 
you need a Google service account and place the credentials in the root folder with the name `gc_config.json`.

Since it is free to use OCR up to 1000 images per month, we stop the upload feature by 750 pictures as it can get very costly.
If you want to change it, you can set the env variable `GC_OCR_ANALYZING_LIMIT` to your liking.

For the stats identification I used clustering and similarity algorithms combined with regular expressions.

## Get daily team statistics update

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
