# Pyx Tracker — unified verify for agent loop and local dev.
# Usage:
#   powershell -File tools/verify.ps1
#   powershell -File tools/verify.ps1 -WithApi
#   powershell -File tools/verify.ps1 -All

param(
    [switch]$WithApi,
    [switch]$All
)

$ErrorActionPreference = 'Stop'
$RepoRoot = Split-Path -Parent $PSScriptRoot
$Php = if (Test-Path 'c:\xampp\php\php.exe') { 'c:\xampp\php\php.exe' } else { 'php' }
$Failed = $false

function Write-Pass([string]$Label) {
    Write-Host "[PASS] $Label"
}

function Write-Fail([string]$Label, [string]$Detail = '') {
    $script:Failed = $true
    if ($Detail) {
        Write-Host "[FAIL] $Label - $Detail"
    } else {
        Write-Host "[FAIL] $Label"
    }
}

function Test-Stage([string]$Name, [scriptblock]$Block) {
    Write-Host ""
    Write-Host "=== $Name ==="
    try {
        & $Block
    } catch {
        $detail = $_.Exception.Message
        if ([string]::IsNullOrWhiteSpace($detail)) {
            $detail = $_.ToString()
        }
        Write-Fail $Name $detail
    }
}

# npm/vite write chunk-size hints to stderr; with $ErrorActionPreference Stop that becomes a false failure.
function Invoke-VerifyCommand([scriptblock]$Command) {
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $out = & $Command 2>&1
    } finally {
        $ErrorActionPreference = $prev
    }
    $text = ($out | ForEach-Object {
        if ($_ -is [System.Management.Automation.ErrorRecord]) { $_.ToString() }
        else { "$_" }
    }) -join "`n"
    return @{
        Text = $text
        ExitCode = $LASTEXITCODE
    }
}

# Vite can finish ("built in Ns") then Node crashes on Windows libuv shutdown (0xC0000409).
function Test-SpaBuildOk([string]$BuildText, [int]$ExitCode, [string]$DistRoot) {
    if ($ExitCode -eq 0) { return $true }
    if ($ExitCode -ne -1073740791) { return $false }
    if ($BuildText -notmatch 'built in [\d.]+s') { return $false }
    $index = Join-Path $DistRoot 'index.html'
    $assets = Join-Path $DistRoot 'assets'
    if (-not (Test-Path $index)) { return $false }
    $js = @(Get-ChildItem -Path $assets -Filter '*.js' -ErrorAction SilentlyContinue)
    return ($js.Count -gt 0)
}

function Get-ChangedPaths {
    Push-Location $RepoRoot
    try {
        $prevEap = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        $out = git diff --name-only HEAD 2>&1 | Where-Object { $_ -is [string] -and $_ -notmatch '^warning:' }
        if (-not $out) {
            $out = git diff --name-only 2>&1 | Where-Object { $_ -is [string] -and $_ -notmatch '^warning:' }
        }
        $ErrorActionPreference = $prevEap
        if (-not $out) {
            return @()
        }
        return @($out | ForEach-Object { "$_".Trim() } | Where-Object { $_ -ne '' })
    } finally {
        Pop-Location
    }
}

function Test-PathTouched([string[]]$Paths, [string[]]$Prefixes) {
    if ($All) { return $true }
    if ($Paths.Count -eq 0) { return $true }
    foreach ($p in $Paths) {
        foreach ($prefix in $Prefixes) {
            if ($p -like "$prefix*") { return $true }
        }
    }
    return $false
}

$changed = @(Get-ChangedPaths)
$touchApp = Test-PathTouched $changed @('app/')
$touchPhp = Test-PathTouched $changed @('includes/', 'api/')
$touchRtsStockSession = Test-PathTouched $changed @(
    'includes/rts_stock_in_sessions.php',
    'api/v1/rts.php',
    'app/src/app/pages/RTSPage.tsx',
    'tools/test_rts_stock_in_session.php',
    'sql/migration_phase76'
)
$touchPackaging = Test-PathTouched $changed @(
    'includes/fulfillment_pack',
    'includes/parcel_pack',
    'includes/material_inventory',
    'includes/honey_parcel',
    'tools/test_parcel_pack_match.php',
    'sql/migration_phase'
)

Write-Host '=== Pyx Tracker verify ==='
if ($changed.Count -gt 0) {
    Write-Host "Changed files: $($changed.Count) (scoped stages)"
} else {
    Write-Host 'No git diff — running full verify'
    $touchApp = $true
    $touchPhp = $true
    $touchPackaging = $true
}

# PHP syntax
Test-Stage 'PHP syntax' {
    $dirs = @('includes', 'api')
    foreach ($dir in $dirs) {
        $full = Join-Path $RepoRoot $dir
        if (-not (Test-Path $full)) { continue }
        Get-ChildItem -Path $full -Filter '*.php' -Recurse | ForEach-Object {
            $out = & $Php -l $_.FullName 2>&1
            if ($LASTEXITCODE -ne 0) {
                Write-Fail "php -l $($_.FullName)" ($out -join ' ')
            }
        }
    }
    if (-not $Failed) { Write-Pass "PHP syntax (includes and api)" }
}

if ($touchRtsStockSession -or $All) {
    Test-Stage 'RTS stock-in session' {
        Push-Location $RepoRoot
        try {
            $out = & $Php tools/test_rts_stock_in_session.php 2>&1
            Write-Host ($out -join "`n")
            if ($LASTEXITCODE -ne 0) {
                Write-Fail 'RTS stock-in session' "exit $LASTEXITCODE"
            } else {
                Write-Pass 'RTS stock-in session'
            }
        } finally {
            Pop-Location
        }
    }
}

# Parcel tier regression
if ($touchPackaging -or $All) {
    Test-Stage 'Parcel pack tiers' {
        Push-Location $RepoRoot
        try {
            # Phase-30 legacy tiers (dept_1_2_units) match every shape until re-seeded.
            $seedOut = & $Php tools/seed_fulfillment_pack_tiers.php 2>&1
            if ($LASTEXITCODE -eq 0) {
                Write-Host ($seedOut -join "`n")
            } else {
                Write-Host "seed_fulfillment_pack_tiers skipped or failed (phase33 SKUs may be missing):"
                Write-Host ($seedOut -join "`n")
            }
            $out = & $Php tools/test_parcel_pack_match.php 2>&1
            $text = $out -join "`n"
            Write-Host $text
            if ($LASTEXITCODE -ne 0) {
                Write-Fail 'Parcel pack tiers' "exit $LASTEXITCODE"
            } else {
                Write-Pass 'Parcel pack tiers'
            }
        } finally {
            Pop-Location
        }
    }
}

# SPA build
if ($touchApp -or $All) {
    Test-Stage 'SPA build' {
        Push-Location (Join-Path $RepoRoot 'app')
        try {
            $distRoot = Join-Path $RepoRoot 'app\dist'
            $result = Invoke-VerifyCommand { npm run build }
            if ($result.Text) { Write-Host $result.Text }
            if (Test-SpaBuildOk $result.Text $result.ExitCode $distRoot) {
                if ($result.ExitCode -ne 0) {
                    Write-Host "Note: npm run build exited $($result.ExitCode) (known Windows libuv flake); dist artifacts OK."
                }
                Write-Pass 'SPA build'
            } else {
                Write-Fail 'SPA build' "exit $($result.ExitCode)"
            }
        } finally {
            Pop-Location
        }
    }
}

# Memory bank regenerate
if ($touchPhp -or $All) {
    Test-Stage 'Memory bank' {
        Push-Location $RepoRoot
        try {
            $out = & $Php tools/memory_bank/generate_all.php 2>&1
            Write-Host ($out -join "`n")
            if ($LASTEXITCODE -ne 0) {
                Write-Fail 'Memory bank generate' "exit $LASTEXITCODE"
            } else {
                Write-Pass 'Memory bank generate'
            }
        } finally {
            Pop-Location
        }
    }
}

# API smoke (optional)
if ($WithApi) {
    Test-Stage 'API smoke' {
        Push-Location $RepoRoot
        try {
            $out = & $Php tools/spa_smoke_test.php 2>&1
            $text = $out -join "`n"
            # spa_smoke_test prints [FAIL] lines; mirror exit code
            if ($text -match '\[FAIL\]') {
                Write-Host $text
                Write-Fail 'API smoke' 'see output above'
            } elseif ($LASTEXITCODE -ne 0) {
                Write-Host $text
                Write-Fail 'API smoke' "exit $LASTEXITCODE"
            } else {
                Write-Host $text
                Write-Pass 'API smoke'
            }
        } finally {
            Pop-Location
        }
    }
}

Write-Host ''
if ($Failed) {
    Write-Host '[FAIL] verify - one or more stages failed'
    exit 1
}
Write-Host '[PASS] verify - all stages passed'
exit 0
