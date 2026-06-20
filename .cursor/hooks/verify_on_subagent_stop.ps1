# After shell/verifier sub-agent stops, nudge parent if verify output contains FAIL.
param()

$ErrorActionPreference = 'Stop'

try {
    $raw = [Console]::In.ReadToEnd()
    if ([string]::IsNullOrWhiteSpace($raw)) {
        exit 0
    }
    $input = $raw | ConvertFrom-Json
} catch {
    exit 0
}

$transcript = ''
if ($input.PSObject.Properties['output']) {
    $transcript = [string]$input.output
}
if ($transcript -eq '' -and $input.PSObject.Properties['result']) {
    $transcript = [string]$input.result
}
if ($transcript -eq '' -and $input.PSObject.Properties['transcript']) {
    $transcript = [string]$input.transcript
}

$looksLikeVerify = $transcript -match '\[FAIL\]|Verify result:\s*FAIL|verify — one or more stages failed'
if (-not $looksLikeVerify) {
    exit 0
}

$excerpt = $transcript
if ($excerpt.Length -gt 4000) {
    $excerpt = $excerpt.Substring($excerpt.Length - 4000)
}

$msg = @"
Verifier sub-agent reported FAIL. Delegate to fixer sub-agent with this output, then re-delegate to verifier.

$excerpt
"@

@{ followup_message = $msg } | ConvertTo-Json -Compress
exit 0
