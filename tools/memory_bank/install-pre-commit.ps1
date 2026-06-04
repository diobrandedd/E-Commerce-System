# Install git pre-commit hook to regenerate memory bank on commit.
$ErrorActionPreference = "Stop"
$Root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$HookSrc = Join-Path $PSScriptRoot "pre-commit"
$HookDst = Join-Path $Root ".git\hooks\pre-commit"

if (-not (Test-Path (Join-Path $Root ".git"))) {
    Write-Error "Not a git repository: $Root"
}

Copy-Item -Path $HookSrc -Destination $HookDst -Force
Write-Host "Installed pre-commit hook -> $HookDst"
Write-Host "Hook runs: php tools/memory_bank/generate_all.php and stages generated/ if changed."
