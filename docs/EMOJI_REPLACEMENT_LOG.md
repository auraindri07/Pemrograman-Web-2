> Catatan audit ulang 15 Oktober 2026: klaim kesesuaian pada log lama ini tidak dipakai sebagai bukti. Inventaris terbaru, termasuk emoji yang dipertahankan, SVG lokal, logo, dan foto asli, ada di [ICON_FIDELITY_AUDIT.md](ICON_FIDELITY_AUDIT.md). Hasil render terukur ada di [UI_FIDELITY_REPORT.md](UI_FIDELITY_REPORT.md).

# EMOJI REPLACEMENT LOG
**Tanggal:** 15 Oktober 2026  
**Status:** In Progress

## MAPPING EMOJI → FONTAWESOME ICONS

| Emoji | FontAwesome Icon | Class |
|-------|------------------|-------|
| 👋 | hand-wave | `fa-solid fa-hand-wave` |
| ⚠️ | triangle-exclamation | `fa-solid fa-triangle-exclamation` |
| ✅ | circle-check | `fa-solid fa-circle-check` |
| ✓ | check | `fa-solid fa-check` OR `fa-solid fa-circle-check` |
| 📚 | book | `fa-solid fa-book` |
| 📖 | book-open | `fa-solid fa-book-open` |
| 📝 | file-lines | `fa-solid fa-file-lines` |
| 📊 | chart-line/chart-pie | `fa-solid fa-chart-line` OR `fa-solid fa-chart-pie` |
| 📅 | calendar-days | `fa-solid fa-calendar-days` |
| 📁 | folder | `fa-solid fa-folder` |
| 📋 | clipboard-list | `fa-solid fa-clipboard-list` |
| 🏫 | school | `fa-solid fa-school` |
| 👥 | users | `fa-solid fa-users` |
| 🔍 | magnifying-glass | `fa-solid fa-magnifying-glass` |
| ➕ | plus | `fa-solid fa-plus` |
| 💾 | floppy-disk | `fa-solid fa-floppy-disk` |
| ● | circle (small) | `fa-solid fa-circle` with `text-[6px]` or `text-[8px]` |
| 👨‍🏫 | chalkboard-user | `fa-solid fa-chalkboard-user` |
| 🎓 | graduation-cap | `fa-solid fa-graduation-cap` |
| ✏️ | pen-to-square | `fa-solid fa-pen-to-square` |
| 📄 | file | `fa-regular fa-file` |
| 🔄 | rotate | `fa-solid fa-rotate` |

## FILES COMPLETED

### ✅ Selesai
- [x] `auth/login.php` - Error icon ⚠️ → triangle-exclamation
- [x] `dashboard/index.php` - hand-wave, chart-line, file-lines, circle-check, circle indicator
- [x] `dashboard/tugas.php` - All emoji replaced
- [x] `dashboard/guru_presensi.php` - All emoji replaced
- [x] `dashboard/guru_kelas.php` - All emoji replaced
- [x] `dashboard/admin.php` - All emoji replaced

### 🔄 In Progress
- [ ] `dashboard/guru.php`
- [ ] `dashboard/guru_daftar_siswa.php`
- [ ] `dashboard/guru_penilaian.php`
- [ ] `dashboard/guru_materi.php`
- [ ] `dashboard/mata_pelajaran.php`
- [ ] `dashboard/mapel_detail.php`
- [ ] `dashboard/pengumuman.php`
- [ ] `dashboard/presensi.php`
- [ ] `dashboard/riwayat.php`
- [ ] `dashboard/kumpulkan_tugas.php`

## CATATAN
- Pastikan FontAwesome library sudah diload di `includes/portal_assets.php`
- Untuk icon yang digunakan dalam loop (seperti icon mata pelajaran), gunakan fallback jika data emoji kosong
- Gunakan `fa-solid` untuk icon filled, `fa-regular` untuk icon outline
- Ukuran default icon bisa disesuaikan dengan class parent atau tambahkan `text-{size}`
