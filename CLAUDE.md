# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Lottery Genie is a Laravel 10 (PHP ^8.1) site that shows statistics, predictions and combination/sum checkers for the Greek-market lotteries Eurojackpot, Joker and Lotto. There is no database: all data comes from historical-draw `.xlsx` files in `storage/stats/{euro,joker,lotto}/` (not in git), parsed with PhpSpreadsheet.

## Commands

```bash
php artisan serve                      # dev server on http://localhost:8000
php artisan app:cache-all              # parse all .xlsx files and refresh caches (each game in its own process)
php artisan app:cache-eurojackpot      # likewise per game: app:cache-joker, app:cache-lotto
php artisan test                       # PHPUnit (tests/ only holds the Laravel examples)
php artisan test --filter=ExampleTest  # single test
./vendor/bin/pint                      # code style
```

Frontend has no build step: the layout loads Tailwind (play CDN) and Alpine.js from CDNs, plus `public/css/style.css`. There is no npm/Vite toolchain.

## Architecture

- **Services** (`app/Services/{Eurojackpot,Joker,Lotto}Service.php`) hold all logic: `load_files()` parses the spreadsheets, and the services expose stats, predictions (`run()`, a weighted shuffle of historical frequencies over 100 simulated draws), `checkCombination()`, `getSumDistribution()` and `getLatestDrawDate()`. `EurojackpotService` uses snake_case method names (`get_stats`) while Joker/Lotto use camelCase (`getStats`).
- **Caching**: results are cached (file driver, 7 days) under keys like `lotto_draws`, `lotto_stats`, `lotto_delays`, `lotto_latest_draw_date`. Services read the cache and fall back to parsing the files on a miss (`cachedOrLoaded`/`loadFilesOnce` in Joker/Lotto). After changing what a service computes or stores, rerun `app:cache-*`, or pages keep showing stale cached data. Parsing spreadsheets is memory-heavy; that is why `app:cache-all` spawns a separate process per game.
- **Shared calculators**: `SumDistribution` and `RangeDistribution` are game-agnostic and configured per game inside each service. Their output is rendered by `resources/views/partials/{sum,range}_distribution.blade.php`.
- **Controllers**: `DrawsController` has one action per game × page (predict, stats, checker, sum-checker); the three sum checkers share the private `sumChecker()` and the single `sum-checker.blade.php` view. `MainController` renders the home page with the latest draw of each game.
- **Views**: one Blade view per game and page (`eurojackpot-stats.blade.php`, `joker-checker.blade.php`, …) extending `layouts/app.blade.php`. Checker and sum-checker UIs are Alpine.js components defined in inline `<script>` blocks.

## Localization

The site is bilingual: English (default) and Greek.

- Translations use JSON files keyed by the English text: wrap strings in `__('English text')` and add the Greek entry to `lang/el.json`. There is no `en.json`.
- The locale is stored in the session by the `/lang/{locale}` route and applied by `App\Http\Middleware\SetLocale` (in the `web` group). The supported locales and their names are in `config('app.available_locales')`, and each needs a flag SVG at `resources/views/partials/flags/{locale}.blade.php`. The navbar shows a single flag button for the other language.
- Strings used by Alpine/JS are passed from Blade with `@json(__('...'))`, with placeholders filled client-side (see `fmt()` in `sum-checker.blade.php`). Build multi-line arrays in `@php` first; a multi-line `@json([...])` breaks Blade's parser.
- Some labels come from cached service data in English (bucket names such as "Core Average", and even/odd keys like "3 even / 2 odd"). They are translated in the views, not in the services, so the cache stays language-neutral.
