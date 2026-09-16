$wordCap = New-Object -ComObject Word.Application
$wordCap.Visible = $false
$wordCap.DisplayAlerts = 0
try {
$docCap = $wordCap.Documents.Open('C:\xampp\htdocs\rts\output\documents\Pyx_Tracker_Capstone_Chapters_I_III_Revised.docx')
$docCap.Fields.Update() | Out-Null
$docCap.Repaginate()
foreach ($tocCap in $docCap.TablesOfContents) { $tocCap.Update(); $tocCap.Range.Font.Size = 11; $tocCap.Range.ParagraphFormat.LineSpacingRule = 0; $tocCap.Range.ParagraphFormat.SpaceBefore = 0; $tocCap.Range.ParagraphFormat.SpaceAfter = 2; $tocCap.Range.ParagraphFormat.KeepWithNext = 0 }
$docCap.Repaginate()
foreach ($tocCap in $docCap.TablesOfContents) { $tocCap.UpdatePageNumbers() }
$docCap.Save()
$docCap.ExportAsFixedFormat('C:\xampp\htdocs\rts\output\capstone_work\revised.pdf',17)
$docCap.Close(0)
} finally { $wordCap.Quit() }


