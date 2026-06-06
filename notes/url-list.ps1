# 1. Define your GitHub repository details
$repoUrlBase = "https://raw.githubusercontent.com/sburton59/settle-site/main"

# 2. Define folders you want to ignore/exclude (add any folder names here)
$foldersToExclude = @('notes', 'wp-content', 'obj', 'ignore-me')

# 3. Get the current local directory path (and ensure it ends with a backslash for clean stripping)
$localPath = (Get-Location).Path
if (-not $localPath.EndsWith('\')) { $localPath += '\' }

# Create a regex pattern from the exclusion list (e.g., "\\(node_modules|bin|obj)\\")
# This ensures it matches the exact folder names within the path
$excludePattern = if ($foldersToExclude) {
    '\\(' + ($foldersToExclude -join '|') + ')\\'
} else {
    '^$' # Matches nothing if the array is empty
}

# 4. Recurse the files, filter, and swap the paths
$urls = Get-ChildItem -Recurse -File | 
    Where-Object { 
        $_.FullName -notmatch '\\\.' -and                     # Exclude hidden files/folders (like .git)
        ($excludePattern -eq '^$' -or $_.FullName -notmatch $excludePattern) # Exclude custom folders
    } | 
    ForEach-Object {
        # Strip the local folder path from the beginning of the file's full path
        $relativePath = $_.FullName.Replace($localPath, '')
        
        # Convert Windows backslashes (\) to URL forward slashes (/)
        $urlPath = $relativePath.Replace('\', '/')
        
        # Combine with the GitHub base URL
        "$repoUrlBase/$urlPath"
    }

# 5. Save to file
$urls | Out-File notes\urls.txt

Write-Host "Done! Processed $(($urls).Count) files and saved their GitHub URLs to urls.txt" -ForegroundColor Green