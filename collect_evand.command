#!/bin/bash
# Move to the script's directory
cd "$(dirname "$0")"

echo "================================================="
echo " Rokhdad.ToP - Evand Event & Organizer Importer"
echo "================================================="
echo "Starting Evand collection process..."
echo ""

# Check if php command is available
if command -v php &> /dev/null
then
    echo "PHP is available. Running Laravel artisan importer..."
    if [ -f "backend/artisan" ]; then
        echo "Ensuring database migrations are up to date..."
        php backend/artisan migrate --force
        echo ""

        echo "Running: php backend/artisan evand:import"
        php backend/artisan evand:import
        
        echo ""
        echo "Running: php backend/artisan evand:import-organizers"
        php backend/artisan evand:import-organizers
    else
        echo "Error: backend/artisan not found."
    fi
else
    echo "PHP command not found. Trying Python workers..."
    # Fallback/alternative python script
    if command -v python3 &> /dev/null; then
        echo "Python 3 is available. Running python worker ingestion for Evand..."
        # If they want python workers, run the evand source script
        python3 workers/rokhdad_workers/sources/evand.py --limit 50 > /tmp/evand_raw.json
        if [ -s /tmp/evand_raw.json ]; then
            echo "Raw data collected to /tmp/evand_raw.json"
            echo "Attempting snapshot storage in MongoDB..."
            python3 workers/rokhdad_workers/snapshots.py --input-json "$(cat /tmp/evand_raw.json)"
        else
            echo "Failed to collect Evand events via Python worker."
        fi
    else
        echo "Neither PHP nor Python 3 found on the system path."
    fi
fi

echo ""
echo "================================================="
echo "Process finished. Press any key to exit."
echo "================================================="
read -n 1 -s -r -p ""
