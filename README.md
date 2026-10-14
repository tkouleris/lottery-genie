# Lottery Genie

Lottery Genie is a Laravel-based web application designed to provide data-driven predictions for popular lotteries, including Eurojackpot, Joker, and Lotto. By analyzing historical draw data stored in Excel files, the application generates potential winning combinations using statistical frequency analysis.

## Features

- **Eurojackpot Predictions:** Generates main numbers and additional "Euro" numbers.
- **Joker Predictions:** Provides predictions for the Joker lottery.
- **Lotto Predictions:** Generates combinations for standard Lotto draws.
- **Historical Data Analysis:** Uses statistical weighting and frequency analysis of past results to suggest numbers.
- **Modern UI:** Built with a clean, responsive interface using Tailwind CSS and a "glassmorphism" design.

## How It Works

The application processes historical draw data (stored in `storage/stats`) in `.xlsx` format. The `LottoService` and other related services:
1. Load historical data from Excel files using `phpoffice/phpspreadsheet`.
2. Calculate the frequency of each number appearing in past draws.
3. Perform a weighted shuffle and selection process to generate 100 simulated draws.
4. Extract the most frequent numbers from these simulations to provide the final prediction.

## Requirements

- PHP ^8.1
- Composer
- Node.js & NPM (for frontend assets)

## Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd lottery
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install and build frontend assets:**
   ```bash
   npm install
   npm run build
   ```

4. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Prepare Statistics:**
   Ensure your historical data Excel files are placed in the appropriate directories within `storage/stats/` (`storage/stats/euro/`, `storage/stats/joker/`, `storage/stats/lotto/`), then run `php artisan app:cache-all`.

## Usage

Start the local development server:
```bash
php artisan serve
```
Visit `http://localhost:8000` in your browser to access the Lottery Genie interface.

### Console Commands

The statistics are read from the Excel files and cached for 7 days. Run these commands after adding new draw files so the site shows the latest data:

```bash
php artisan app:cache-all           # cache Eurojackpot, Joker and Lotto
php artisan app:cache-eurojackpot   # cache only Eurojackpot (storage/stats/euro)
php artisan app:cache-joker         # cache only Joker (storage/stats/joker)
php artisan app:cache-lotto         # cache only Lotto (storage/stats/lotto)
```

`app:cache-all` runs each game in its own process, so the memory used to read one game's spreadsheets is freed before the next starts.

## Technologies Used

- **Framework:** [Laravel 10](https://laravel.com/)
- **Frontend:** [Tailwind CSS](https://tailwindcss.com/)
- **Excel Processing:** [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet)
- **Icons/Fonts:** Poppins (Google Fonts)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
