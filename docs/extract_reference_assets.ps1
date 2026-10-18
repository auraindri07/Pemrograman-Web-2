Add-Type -AssemblyName System.Drawing
$projectRoot = Split-Path $PSScriptRoot -Parent
$assetDir = Join-Path $projectRoot 'assets/images/reference'
New-Item -ItemType Directory -Force -Path $assetDir | Out-Null
function Export-Asset($SourceName, $AssetName, $X, $Y, $Width, $Height) {
    $sourcePath = Join-Path (Join-Path $projectRoot 'EduLMS_UI') $SourceName
    $bitmap = [System.Drawing.Bitmap]::FromFile($sourcePath)
    try {
        $rect = New-Object System.Drawing.Rectangle($X, $Y, $Width, $Height)
        $crop = $bitmap.Clone($rect, $bitmap.PixelFormat)
        try { $crop.Save((Join-Path $assetDir $AssetName), [System.Drawing.Imaging.ImageFormat]::Png) }
        finally { $crop.Dispose() }
    } finally { $bitmap.Dispose() }
}
# Only reusable photography and the original small logo are extracted.
Export-Asset 'Slide Guru - Kelas & Mata Pelajaran.png' 'class-math-1.png' 48 385 362 145
Export-Asset 'Slide Guru - Kelas & Mata Pelajaran.png' 'class-math-2.png' 467 385 362 145
Export-Asset 'Slide Guru - Kelas & Mata Pelajaran.png' 'class-physics-1.png' 885 385 362 145
Export-Asset 'Slide Guru - Kelas & Mata Pelajaran.png' 'class-math-3.png' 48 917 362 145
Export-Asset 'Slide Guru - Kelas & Mata Pelajaran.png' 'class-physics-2.png' 467 917 362 145
Export-Asset 'Dashboard dengan Grafik Perkembangan Belajar.png' 'avatar.png' 1034 24 32 32
Export-Asset 'Dashboard dengan Grafik Perkembangan Belajar.png' 'logo.png' 32 29 24 20
# Reusable student portraits, not UI screenshots. Coordinates from the attendance reference.
Export-Asset 'Slide Guru - Presensi Siswa.png' 'student-avatar-1.png' 118 665 36 36
Export-Asset 'Slide Guru - Presensi Siswa.png' 'student-avatar-2.png' 118 731 36 36
Export-Asset 'Slide Guru - Presensi Siswa.png' 'student-avatar-3.png' 118 797 36 36
Export-Asset 'Slide Guru - Presensi Siswa.png' 'student-avatar-4.png' 118 863 36 36
Export-Asset 'Slide Guru - Presensi Siswa.png' 'student-avatar-5.png' 118 929 36 36
Export-Asset 'Slide Guru - Presensi Siswa.png' 'student-avatar-6.png' 118 995 36 36
