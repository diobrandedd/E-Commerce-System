# Runs verify when parent agent stops; re-opens chat if checks failed.
param()

$ErrorActionPreference = 'Stop'
$RepoRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$VerifyScript = Join-Path $RepoRoot 'tools\verify.ps1'

try {
    $null = [Console]::In.ReadToEnd()
} catch {
    # stdin may be empty
}

if (-not (Test-Path $VerifyScript)) {
    exit 0
}

Push-Location $RepoRoot
try {
    $output = & powershell -NoProfile -ExecutionPolicy Bypass -File $VerifyScript 2>&1
    $text = ($output | Out-String).Trim()
    if ($LASTEXITCODE -ne 0) {
        $excerpt = $text
        if ($excerpt.Length -gt 4000) {
            $excerpt = $excerpt.Substring($excerpt.Length - 4000)
        }
        $msg = @"
Verify failed after agent stop. Delegate to fixer sub-agent with the output below, then re-delegate to verifier. Max 5 cycles total.

$excerpt
"@
        @{ followup_message = $msg } | ConvertTo-Json -Compress
        exit 0
    }
} finally {
    Pop-Location
}

exit 0
