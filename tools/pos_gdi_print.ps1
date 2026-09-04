# Silent text print via Windows printer driver (GDI) — same path Chrome uses.
# Vendor POS-58 drivers often ignore RAW ESC/POS (queue shows Printing, paper never moves).
param(
  [Parameter(Mandatory = $true)][string]$PrinterName,
  [Parameter(Mandatory = $true)][string]$FilePath
)

$ErrorActionPreference = "Stop"
if (-not (Test-Path -LiteralPath $FilePath)) {
  throw "Print file not found: $FilePath"
}

Add-Type -AssemblyName System.Drawing

$text = [System.IO.File]::ReadAllText($FilePath, [System.Text.Encoding]::UTF8)
# Normalize newlines; drop trailing empties that waste paper
$lines = @($text -split "`r?`n")
while ($lines.Count -gt 0 -and [string]::IsNullOrWhiteSpace($lines[$lines.Count - 1])) {
  if ($lines.Count -eq 1) { $lines = @(); break }
  $lines = $lines[0..($lines.Count - 2)]
}
if ($lines.Count -lt 1) {
  throw "Print file is empty."
}

$doc = New-Object System.Drawing.Printing.PrintDocument
$doc.PrinterSettings.PrinterName = $PrinterName
if (-not $doc.PrinterSettings.IsValid) {
  throw "Printer not found or invalid: $PrinterName"
}
$doc.DocumentName = "Pyx POS receipt"
$doc.PrintController = New-Object System.Drawing.Printing.StandardPrintController

# ~58mm roll: narrow page (≈2.28in). Drivers that ignore custom size still print left-aligned text.
try {
  $doc.DefaultPageSettings.PaperSize = New-Object System.Drawing.Printing.PaperSize("POS58", 226, 1200)
} catch {}
$doc.DefaultPageSettings.Margins = New-Object System.Drawing.Printing.Margins(8, 8, 8, 8)

$script:lineIndex = 0
$font = New-Object System.Drawing.Font("Consolas", 7.5, [System.Drawing.FontStyle]::Regular)

$doc.add_PrintPage({
  param($sender, $e)
  $left = $e.MarginBounds.Left
  $y = $e.MarginBounds.Top
  $lineHeight = [math]::Ceiling($font.GetHeight($e.Graphics))
  $brush = [System.Drawing.Brushes]::Black
  while ($script:lineIndex -lt $lines.Count) {
    if (($y + $lineHeight) -gt $e.MarginBounds.Bottom) {
      $e.HasMorePages = $true
      return
    }
    $e.Graphics.DrawString([string]$lines[$script:lineIndex], $font, $brush, $left, $y)
    $y += $lineHeight
    $script:lineIndex++
  }
  $e.HasMorePages = $false
})

try {
  $doc.Print()
} finally {
  $doc.Dispose()
  $font.Dispose()
}

Write-Output ("OK gdi lines=" + $lines.Count + " printer=" + $PrinterName)
