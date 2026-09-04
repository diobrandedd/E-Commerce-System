# Silent RAW print to a Windows printer (ESC/POS). Used by includes/pos_thermal_print.php
param(
  [Parameter(Mandatory = $true)][string]$PrinterName,
  [Parameter(Mandatory = $true)][string]$FilePath
)

$ErrorActionPreference = "Stop"
if (-not (Test-Path -LiteralPath $FilePath)) {
  throw "Print file not found: $FilePath"
}

$bytes = [System.IO.File]::ReadAllBytes($FilePath)
if ($bytes.Length -lt 1) {
  throw "Print file is empty: $FilePath"
}

Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;

public class PyxRawPrinter {
  [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
  public class DOCINFOA {
    [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
    [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
    [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
  }

  [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi)]
  public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

  [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true)]
  public static extern bool ClosePrinter(IntPtr hPrinter);

  [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi)]
  public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In] DOCINFOA di);

  [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true)]
  public static extern bool EndDocPrinter(IntPtr hPrinter);

  [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true)]
  public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

  public static void Send(string printer, byte[] data) {
    IntPtr hPrinter;
    if (!OpenPrinter(printer, out hPrinter, IntPtr.Zero)) {
      throw new Exception("OpenPrinter failed for '" + printer + "' (error " + Marshal.GetLastWin32Error() + ")");
    }
    try {
      // RAW jobs must NOT call StartPagePrinter/EndPagePrinter (MSDN).
      DOCINFOA di = new DOCINFOA();
      di.pDocName = "Pyx POS receipt";
      di.pOutputFile = null;
      di.pDataType = "RAW";
      if (!StartDocPrinter(hPrinter, 1, di)) {
        throw new Exception("StartDocPrinter failed (error " + Marshal.GetLastWin32Error() + ")");
      }
      try {
        IntPtr p = Marshal.AllocHGlobal(data.Length);
        try {
          Marshal.Copy(data, 0, p, data.Length);
          int written;
          if (!WritePrinter(hPrinter, p, data.Length, out written)) {
            throw new Exception("WritePrinter failed (error " + Marshal.GetLastWin32Error() + ")");
          }
          if (written != data.Length) {
            throw new Exception("WritePrinter wrote " + written + " of " + data.Length + " bytes");
          }
        } finally {
          Marshal.FreeHGlobal(p);
        }
      } finally {
        EndDocPrinter(hPrinter);
      }
    } finally {
      ClosePrinter(hPrinter);
    }
  }
}
"@

[PyxRawPrinter]::Send($PrinterName, $bytes)
Write-Output ("OK wrote " + $bytes.Length + " bytes")
