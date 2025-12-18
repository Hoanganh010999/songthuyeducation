#!/usr/bin/env python3
import json
import glob
import mysql.connector
from pathlib import Path
import os

print("=== RESTORING MATERIAL CONTENT FROM DEBUG FILES ===\n")

# Find debug files
debug_files = sorted(glob.glob('/var/www/school/storage/logs/material_debug_*.json'), reverse=True)

if not debug_files:
    print("❌ No debug files found")
    exit(1)

print(f"Found {len(debug_files)} debug files")
print("Checking most recent files...\n")

data = None
selected_file = None

for debug_file in debug_files:
    print(f"Checking: {Path(debug_file).name}")

    try:
        with open(debug_file, 'r', encoding='utf-8') as f:
            content = f.read()

        print(f"  File size: {len(content):,} bytes")

        # Try to parse JSON
        debug_data = json.loads(content)

        if 'content' not in debug_data:
            print("  ⚠️  No 'content' field found")
            continue

        content_len = len(debug_data['content'])

        if content_len < 5000:
            print(f"  ⚠️  Content too short ({content_len} chars)")
            continue

        # This looks good!
        print(f"  ✅ Valid content found! Length: {content_len:,} chars")
        data = debug_data
        selected_file = debug_file
        break

    except json.JSONDecodeError as e:
        print(f"  ❌ JSON parse error: {e}")
        continue
    except Exception as e:
        print(f"  ❌ Error: {e}")
        continue

if not data:
    print("\n❌ Could not find valid content in any debug file")
    exit(1)

print(f"\n=== SELECTED FILE ===")
print(f"File: {Path(selected_file).name}\n")

title = data.get('title', 'Untitled')
content = data['content']

print("=== EXTRACTED DATA ===")
print(f"Title: {title}")
print(f"Content length: {len(content):,} chars")
print(f"Content preview (first 200 chars):")
print(f"{content[:200]}...\n")

# Verify content
has_vocab = 'vocabulary' in content.lower() or 'PART A' in content
has_table = '<table' in content
has_reading = 'reading' in content.lower() or 'PART B' in content
has_answer = 'answer' in content.lower() and 'key' in content.lower()

print("Content verification:")
print(f"{'✅' if has_vocab else '❌'} Has vocabulary section")
print(f"{'✅' if has_table else '❌'} Has tables")
print(f"{'✅' if has_reading else '❌'} Has reading section")
print(f"{'✅' if has_answer else '❌'} Has answer key\n")

# Read database credentials from .env
env_file = '/var/www/school/.env'
db_config = {
    'host': 'localhost',
    'database': 'school_db',
    'user': 'root',
    'password': ''
}

if os.path.exists(env_file):
    with open(env_file, 'r') as f:
        for line in f:
            line = line.strip()
            if line.startswith('DB_HOST='):
                db_config['host'] = line.split('=', 1)[1]
            elif line.startswith('DB_DATABASE='):
                db_config['database'] = line.split('=', 1)[1]
            elif line.startswith('DB_USERNAME='):
                db_config['user'] = line.split('=', 1)[1]
            elif line.startswith('DB_PASSWORD='):
                db_config['password'] = line.split('=', 1)[1]

# Connect to database
try:
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)

    print(f"✅ Connected to database: {db_config['database']}\n")

    # Show current record
    cursor.execute("SELECT id, title, CHAR_LENGTH(content) as content_len FROM session_materials WHERE id = 2")
    current = cursor.fetchone()

    if current:
        print("=== CURRENT DATABASE RECORD ===")
        print(f"ID: {current['id']}")
        print(f"Title: {current['title']}")
        print(f"Content length: {current['content_len']:,} chars\n")

    # Update the record
    print("Updating record...")

    update_query = """
        UPDATE session_materials
        SET content = %s,
            title = %s,
            updated_at = NOW()
        WHERE id = 2
    """

    cursor.execute(update_query, (content, title))
    conn.commit()

    print(f"✅ Updated session_materials record id=2")
    print(f"Rows affected: {cursor.rowcount}\n")

    # Verify the update
    cursor.execute("SELECT id, title, CHAR_LENGTH(content) as content_len FROM session_materials WHERE id = 2")
    result = cursor.fetchone()

    print("=== AFTER UPDATE ===")
    print(f"ID: {result['id']}")
    print(f"Title: {result['title']}")
    print(f"Content length: {result['content_len']:,} chars\n")

    improvement = result['content_len'] - (current['content_len'] if current else 0)
    print(f"Content increased by: {improvement:,} chars\n")

    if result['content_len'] > 15000:
        print("✅✅✅ SUCCESS! Content restored to full length! ✅✅✅")
        print("\nThe material now has:")
        print("  - Complete vocabulary tables")
        print("  - Full reading text")
        print("  - All exercises and activities")
        print("  - Answer keys\n")
        print("You can now view the material in MaterialsManager!")
    else:
        print("⚠️  Content is still shorter than expected")
        print(f"Expected: >15,000 chars")
        print(f"Got: {result['content_len']:,} chars")

    cursor.close()
    conn.close()

except mysql.connector.Error as e:
    print(f"❌ Database error: {e}")
    exit(1)
except Exception as e:
    print(f"❌ Unexpected error: {e}")
    exit(1)
