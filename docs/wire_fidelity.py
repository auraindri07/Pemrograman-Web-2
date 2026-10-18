"""One-off view wiring; controllers and authorization stay in the existing PHP files."""
from pathlib import Path
root=Path(__file__).resolve().parents[1]
for page,view in {'mata_pelajaran':'subjects','tugas':'tasks','pengumuman':'announcements','guru_kelas':'classes','index':'student_dashboard','guru':'teacher_dashboard','guru_penilaian':'grading','guru_presensi':'attendance','../auth/login':'login'}.items():
 path=root/'dashboard'/f'{page}.php'
 view_path=root/'includes/views'/f'{view}.php'
 if view_path.exists():
  source=path.read_text(encoding='utf-8-sig')
  if '<!DOCTYPE html>' in source:
   source=source[:source.index('<!DOCTYPE html>')]
   source+=f"<?php require __DIR__ . '/../includes/views/{view}.php'; ?>\n"
   path.write_text(source,encoding='utf-8')
for name,target in [('portal_nav','ui_nav'),('portal_footer','ui_footer')]:
 (root/'includes'/f'{name}.php').write_text(f"<?php require __DIR__ . '/{target}.php'; ?>\n",encoding='utf-8')
