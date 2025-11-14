#!/bin/bash

# Script to add project path to Docker Desktop file sharing
# This requires Docker Desktop to be restarted after running

set -e

PROJECT_PATH="/media/kanishka/HDD/Workplace/orders-kpi"
SETTINGS_FILE="$HOME/.docker/desktop/settings-store.json"
BACKUP_FILE="${SETTINGS_FILE}.backup.$(date +%s)"

echo "🔧 Configuring Docker Desktop file sharing..."

# Check if settings file exists
if [ ! -f "$SETTINGS_FILE" ]; then
    echo "❌ Docker Desktop settings file not found at: $SETTINGS_FILE"
    exit 1
fi

# Create backup
echo "📦 Creating backup of settings file..."
cp "$SETTINGS_FILE" "$BACKUP_FILE"
echo "   Backup saved to: $BACKUP_FILE"

# Check if path is already in the list
if python3 -c "
import json
import sys

with open('$SETTINGS_FILE', 'r') as f:
    data = json.load(f)

if 'FilesharingDirectories' in data:
    if '$PROJECT_PATH' in data['FilesharingDirectories']:
        print('already_exists')
        sys.exit(0)
    else:
        data['FilesharingDirectories'].append('$PROJECT_PATH')
else:
    data['FilesharingDirectories'] = ['$PROJECT_PATH']

with open('$SETTINGS_FILE', 'w') as f:
    json.dump(data, f, indent=4)

print('added')
" | grep -q "already_exists"; then
    echo "✅ Path is already configured in Docker Desktop file sharing!"
    exit 0
fi

# Update the settings file
python3 << EOF
import json

with open('$SETTINGS_FILE', 'r') as f:
    data = json.load(f)

if 'FilesharingDirectories' not in data:
    data['FilesharingDirectories'] = []

if '$PROJECT_PATH' not in data['FilesharingDirectories']:
    data['FilesharingDirectories'].append('$PROJECT_PATH')
    print("✅ Added path to Docker Desktop settings")
else:
    print("✅ Path already exists in settings")

with open('$SETTINGS_FILE', 'w') as f:
    json.dump(data, f, indent=4)
EOF

echo ""
echo "✅ Path added to Docker Desktop settings!"
echo ""
echo "⚠️  IMPORTANT: Docker Desktop needs to be restarted for changes to take effect."
echo ""
echo "Please:"
echo "1. Restart Docker Desktop (or click 'Apply & Restart' in Settings)"
echo "2. Then run: ./sail-up.sh"
echo ""

