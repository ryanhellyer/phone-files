#!/bin/bash

# Find and delete files matching the thumbnail pattern (e.g., name-100x100.avif)
# This looks for a hyphen followed by digits, an 'x', and more digits before the extension.
find . -type f -regextype posix-extended -regex ".*-[0-9]+x[0-9]+\.avif$" -delete
