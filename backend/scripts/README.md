# Python Scripts Setup

This directory contains Python scripts that use the `nba-api` library to fetch real NBA data.

## Virtual Environment Setup

To avoid conflicts with system Python packages, we use a virtual environment.

### Initial Setup

1. Create the virtual environment:

```bash
cd backend/scripts
python3 -m venv venv
```

2. Activate the virtual environment:

```bash
source venv/bin/activate
```

3. Install dependencies:

```bash
pip install -r requirements.txt
```

### Using the Virtual Environment

**Option 1: Activate before running scripts manually**

```bash
cd backend/scripts
source venv/bin/activate
python3 get_top_performers.py 2024-11-01 2024-11-30
```

**Option 2: Use Laravel artisan commands (recommended)**
The Laravel artisan commands automatically detect and use the virtual environment:

```bash
php artisan stats:top-performers 2024-11-01 2024-11-30
php artisan nba:import-from-stats
```

### Deactivating

When you're done working with Python scripts:

```bash
deactivate
```

## Dependencies

-   `nba-api` - Official NBA API wrapper for Python

All dependencies are listed in `requirements.txt`.

## Troubleshooting

If you get "nba_api package not found" errors:

1. Make sure the virtual environment exists:

    ```bash
    ls backend/scripts/venv
    ```

2. If it doesn't exist, create it (see Initial Setup above)

3. If it exists but packages aren't installed:
    ```bash
    cd backend/scripts
    source venv/bin/activate
    pip install -r requirements.txt
    ```
