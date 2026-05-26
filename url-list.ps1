# 1. Define your GitHub repository details
$repoUrlBase = "https://github.com/sburton59/settle-site/blob/main"

# 2. Get the current local directory path (and ensure it ends with a backslash for clean stripping)
$localPath = (Get-Location).Path
if (-not $localPath.EndsWith('\')) { $localPath += '\' }

# 3. Recurse the files, filter, and swap the paths
$urls = Get-ChildItem -Recurse -File | 
    Where-Object { $_.FullName -notmatch '\\\.' } | # Exclude hidden files/folders (like .git)
    ForEach-Object {
        # Strip the local folder path from the beginning of the file's full path
        $relativePath = $_.FullName.Replace($localPath, '')
        
        # Convert Windows backslashes (\) to URL forward slashes (/)
        $urlPath = $relativePath.Replace('\', '/')
        
        # Combine with the GitHub base URL
        "$repoUrlBase/$urlPath"
    }

# 4. Save to file
$urls | Out-File urls.txt

Write-Host "Done! Processed $(($urls).Count) files and saved their GitHub URLs to urls.txt" -ForegroundColor Green