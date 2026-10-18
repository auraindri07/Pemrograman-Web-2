<?php
function validateAdminInput(PDO $pdo, string $page, array $input): void {
    $action = $input['action'] ?? '';
    $validUser = function ($id, $role) use ($pdo) {
        $q = $pdo->prepare('SELECT 1 FROM users WHERE id=? AND role=? AND is_active=1');
        $q->execute([(int)$id,$role]); return (bool)$q->fetchColumn();
    };
    $class = function ($id, $year) use ($pdo) {
        $q = $pdo->prepare('SELECT 1 FROM kelas WHERE id=? AND tahun_ajaran=?');
        $q->execute([(int)$id,$year]); return (bool)$q->fetchColumn();
    };
    if (in_array($action,['buat_kelas','tempatkan','tugaskan'])) {
        $year = trim($input['tahun_ajaran'] ?? '') ?: TAHUN_AJARAN_AKTIF;
        if (!preg_match('/^(\d{4})\/(\d{4})$/',$year,$parts) || (int)$parts[2] !== (int)$parts[1]+1) throw new InvalidArgumentException('Tahun ajaran harus berurutan, misalnya 2026/2027.');
    }
    if (in_array($action,['buat_kelas','set_wali']) && !empty($input['wali_kelas_id']) && !$validUser($input['wali_kelas_id'],'guru')) throw new InvalidArgumentException('Wali kelas harus guru aktif.');
    if ($action === 'tempatkan' && (!$class($input['kelas_id'] ?? 0,$year) || !$validUser($input['siswa_id'] ?? 0,'siswa'))) throw new InvalidArgumentException('Siswa atau kelas/tahun ajaran tidak valid.');
    if ($action === 'tugaskan') {
        if (!$class($input['kelas_id'] ?? 0,$year) || !$validUser($input['guru_id'] ?? 0,'guru')) throw new InvalidArgumentException('Guru atau kelas/tahun ajaran tidak valid.');
        if (!empty($input['hari']) && !in_array($input['hari'],['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'])) throw new InvalidArgumentException('Hari tidak valid.');
        if (!empty($input['jam_mulai']) && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/',$input['jam_mulai'])) throw new InvalidArgumentException('Jam tidak valid.');
    }
    if ($action === 'buat_mapel' && !in_array($input['kategori'] ?? '',['wajib','peminatan','lintas_minat'])) throw new InvalidArgumentException('Kategori mata pelajaran tidak valid.');
    if ($action === 'ubah_status' && ($input['status'] ?? '') === 'aktif') {
        $q = $pdo->prepare("SELECT 1 FROM siswa_kelas a JOIN siswa_kelas b ON b.siswa_id=a.siswa_id AND b.tahun_ajaran=a.tahun_ajaran WHERE a.id=? AND b.id<>a.id AND b.status='aktif'");
        $q->execute([(int)($input['enrol_id'] ?? 0)]);
        if ($q->fetchColumn()) throw new InvalidArgumentException('Siswa sudah aktif pada kelas lain di tahun ajaran yang sama.');
    }
}
