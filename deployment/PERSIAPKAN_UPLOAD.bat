@echo off
echo Mempersiapkan file untuk upload ke Hostinger...
echo.

REM Buat folder sementara untuk file yang akan di-zip
mkdir "%~dp0temp_upload"

REM Salin semua file kecuali yang tidak diperlukan untuk produksi
echo Menyalin file aplikasi...
xcopy "%~dp0..\" "%~dp0temp_upload\" /E /H /C /I /Y /EXCLUDE:%~dp0exclude_list.txt

echo.
echo Mempersiapkan database...
REM Salin database SQLite dengan izin yang benar
copy "%~dp0..\database\rental_baju.sqlite" "%~dp0temp_upload\database\"

echo.
echo Membuat file zip...
powershell -command "Compress-Archive -Path '%~dp0temp_upload\*' -DestinationPath '%~dp0rental_baju_upload.zip' -Force"

echo.
echo Membersihkan file sementara...
rmdir /S /Q "%~dp0temp_upload"

echo.
echo Proses selesai!
echo File zip siap diupload: %~dp0rental_baju_upload.zip
echo.
echo Silakan upload file zip tersebut ke hosting Anda dan ekstrak di folder tujuan.
echo Ikuti panduan di PANDUAN_DEPLOY_HOSTINGER.md untuk langkah selanjutnya.
echo.
pause
