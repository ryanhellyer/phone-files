#!/bin/bash

TARGET_DIR="./"
COUNT=0

find "$TARGET_DIR" -type f -regextype posix-extended \
    -regex ".*-[0-9]+x[0-9]+\.(webp|WEBP|jpg|jpeg|JPG|JPEG)" -print0 | while IFS= read -r -d '' file; do
    
    echo "Deleting: $file"
    rm "$file"
    ((COUNT++))
    
    # This keeps the count updated inside the loop
    echo $COUNT > /tmp/file_count.txt
done

# Retrieve the count from the temp file (due to subshell limitations in while loops)
TOTAL_DELETED=$(cat /tmp/file_count.txt || echo 0)
rm /tmp/file_count.txt

echo "Cleanup complete. Removed $TOTAL_DELETED files."
