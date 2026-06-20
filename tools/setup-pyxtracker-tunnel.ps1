# Pyx Tracker — Cloudflare Tunnel local setup helper
# Run PowerShell as Administrator for DNS fix; XAMPP + cloudflared must be installed.
#
# Usage:
#   powershell -NoProfile -ExecutionPolicy Bypass -File tools\setup-pyxtracker-tunnel.ps1
#   powershell -NoProfile -ExecutionPolicy Bypass -File tools\setup-pyxtracker-tunnel.ps1 -FixDns

param(
    [switch]$FixDns,
    [string]$PublicHost = "pyxtracker.pyxfood.com",
    [string]$LocalUrl = "http://localhost/rts/app/dist/"
)

$ErrorActionPreference = "Continue"
Write-Host "`n=== Pyx Tracker tunnel setup check ===" -ForegroundColor Cyan

function Test-DnsRecord {
    param([string]$HostName)
    try {
        $r = Resolve-DnsName -Name $HostName -Type CNAME -Server 1.1.1.1 -ErrorAction Stop
        return $true
    } catch {
        return $false
    }
}

# 1. cloudflared Windows service
$cf = Get-Service -Name "cloudflared" -ErrorAction SilentlyContinue
if ($null -eq $cf) {
    Write-Host "[FAIL] cloudflared service not found. Install from Zero Trust tunnel page." -ForegroundColor Red
} elseif ($cf.Status -ne "Running") {
    Write-Host "[WARN] cloudflared service exists but is not running. Starting..." -ForegroundColor Yellow
    try {
        Start-Service cloudflared
        Write-Host "[OK]   cloudflared started." -ForegroundColor Green
    } catch {
        Write-Host "[FAIL] Could not start cloudflared (run as Administrator)." -ForegroundColor Red
    }
} else {
    Write-Host "[OK]   cloudflared service running (Automatic)." -ForegroundColor Green
}

# 2. Apache / local app
try {
    $resp = Invoke-WebRequest -Uri $LocalUrl -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop
    Write-Host "[OK]   Local app responds at $LocalUrl ($($resp.StatusCode))." -ForegroundColor Green
} catch {
    if ($_.Exception.Response -and [int]$_.Exception.Response.StatusCode -in 301,302,200) {
        Write-Host "[OK]   Local app responds at $LocalUrl (redirect or OK)." -ForegroundColor Green
    } else {
        Write-Host "[FAIL] Local app not reachable. Start XAMPP Apache (and MySQL)." -ForegroundColor Red
        Write-Host "       Expected: $LocalUrl" -ForegroundColor DarkGray
    }
}

# 3. forwarding.php
$forwardingPath = Join-Path $PSScriptRoot "..\config\forwarding.php"
if (Test-Path $forwardingPath) {
    $cfgText = Get-Content $forwardingPath -Raw
    if ($cfgText -match [regex]::Escape($PublicHost)) {
        Write-Host "[OK]   config/forwarding.php points to $PublicHost." -ForegroundColor Green
    } else {
        Write-Host "[WARN] config/forwarding.php may still point at ngrok. Set public_url to https://$PublicHost" -ForegroundColor Yellow
    }
} else {
    Write-Host "[WARN] config/forwarding.php missing. Copy from config/forwarding.sample.php" -ForegroundColor Yellow
}

# 4. Public DNS — must resolve to Cloudflare edge IPs (104.x / 172.x), NOT fd10:: / cfargotunnel CNAME only
try {
    $a = Resolve-DnsName -Name $PublicHost -Server 1.1.1.1 -Type A -ErrorAction Stop
    $hasPublicA = @($a | Where-Object { $_.Type -eq 'A' -and $_.IPAddress -notmatch '^127\.' }).Count -gt 0
    if ($hasPublicA) {
        Write-Host "[OK]   Public DNS: $PublicHost has routable A record(s)." -ForegroundColor Green
    } else {
        Write-Host "[FAIL] Public DNS: $PublicHost has no public A record." -ForegroundColor Red
        Write-Host "       In Cloudflare DNS, set pyxtracker CNAME to Proxied (orange cloud)." -ForegroundColor DarkGray
        Write-Host "       Grey/DNS-only CNAME to cfargotunnel.com causes ERR_CONNECTION_TIMED_OUT." -ForegroundColor DarkGray
    }
} catch {
    $cname = Resolve-DnsName -Name $PublicHost -Server 1.1.1.1 -Type CNAME -ErrorAction SilentlyContinue
    if ($cname.NameHost -match 'cfargotunnel\.com') {
        Write-Host "[FAIL] Public DNS: $PublicHost CNAME points to cfargotunnel but is not proxied." -ForegroundColor Red
        Write-Host "       Cloudflare dashboard -> DNS -> pyxtracker -> turn Proxy ON (orange)." -ForegroundColor DarkGray
    } else {
        Write-Host "[FAIL] Public DNS: $PublicHost not found on 1.1.1.1. Check Cloudflare DNS." -ForegroundColor Red
    }
}

# 5. Local DNS (often the reason Chrome shows NXDOMAIN)
try {
    Resolve-DnsName -Name $PublicHost -ErrorAction Stop | Out-Null
    Write-Host "[OK]   This PC resolves $PublicHost." -ForegroundColor Green
} catch {
    Write-Host "[WARN] This PC cannot resolve $PublicHost yet (router DNS cache)." -ForegroundColor Yellow
    if ($FixDns) {
        $wifi = Get-NetAdapter | Where-Object { $_.Status -eq "Up" -and $_.Name -match "Wi-Fi|Ethernet" } | Select-Object -First 1
        if ($wifi) {
            try {
                Set-DnsClientServerAddress -InterfaceAlias $wifi.Name -ServerAddresses @("1.1.1.1", "1.0.0.1")
                ipconfig /flushdns | Out-Null
                Write-Host "[OK]   Set DNS on $($wifi.Name) to 1.1.1.1 / 1.0.0.1 and flushed cache." -ForegroundColor Green
            } catch {
                Write-Host "[FAIL] Could not set DNS. Re-run this script in Administrator PowerShell with -FixDns." -ForegroundColor Red
            }
        }
    } else {
        Write-Host "       Fix: run as Admin with -FixDns, or wait 15-60 min for router DNS." -ForegroundColor DarkGray
    }
}

Write-Host "`nPublic URL: https://$PublicHost/rts/app/dist/" -ForegroundColor Cyan
Write-Host "Legacy login: https://$PublicHost/rts/login.php" -ForegroundColor DarkGray
Write-Host "`nRequirements when sharing access:" -ForegroundColor DarkGray
Write-Host "  - XAMPP Apache (+ MySQL) running on this PC" -ForegroundColor DarkGray
Write-Host "  - cloudflared service running" -ForegroundColor DarkGray
Write-Host "  - This PC powered on and online`n" -ForegroundColor DarkGray
