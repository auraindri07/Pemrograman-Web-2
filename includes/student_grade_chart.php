<?php
/** Actual task grades, scoped to the selected academic semester, in grading order. */
function studentGradeChart(PDO $pdo, int $studentId, string $academicYear, ?DateTimeImmutable $today = null): array {
    $today ??= new DateTimeImmutable('today');
    $firstYear = (int)substr($academicYear, 0, 4);
    $secondSemester = (int)$today->format('n') <= 6;
    $start = new DateTimeImmutable(($secondSemester ? $firstYear + 1 : $firstYear).($secondSemester ? '-01-01' : '-07-01'));
    $query = $pdo->prepare("SELECT pt.id,pt.nilai,t.judul_tugas,mp.nama_mapel,
        COALESCE(pt.dinilai_pada,pt.tanggal_kumpul) AS evaluated_at
        FROM pengumpulan_tugas pt JOIN tugas t ON t.id=pt.tugas_id
        JOIN guru_mapel gm ON gm.id=t.guru_mapel_id JOIN mata_pelajaran mp ON mp.id=gm.mapel_id
        WHERE pt.siswa_id=? AND pt.nilai IS NOT NULL AND gm.tahun_ajaran=?
        AND COALESCE(pt.dinilai_pada,pt.tanggal_kumpul)>=?
        AND COALESCE(pt.dinilai_pada,pt.tanggal_kumpul)<?
        ORDER BY evaluated_at DESC,pt.id DESC LIMIT 10");
    $periods = [];
    foreach (['current'=>0, 'previous'=>1] as $key=>$yearsBack) {
        $periodStart = $start->modify('-'.$yearsBack.' year');
        $year = ($firstYear-$yearsBack).'/'.($firstYear+1-$yearsBack);
        $query->execute([$studentId,$year,$periodStart->format('Y-m-d'),$periodStart->modify('+6 months')->format('Y-m-d')]);
        $points = [];
        foreach (array_reverse($query->fetchAll()) as $row) {
            $points[] = [
                'id'=>(int)$row['id'], 'value'=>(float)$row['nilai'],
                'task'=>$row['judul_tugas'], 'subject'=>$row['nama_mapel'],
                'evaluatedAt'=>$row['evaluated_at'],
                'dateLabel'=>formatTanggalIndo($row['evaluated_at']).', '.substr($row['evaluated_at'],11,5).' WIB'
            ];
        }
        $periods[$key] = ['points'=>$points, 'start'=>$periodStart->format('Y-m-d'),
            'end'=>$periodStart->modify('+6 months')->format('Y-m-d'), 'academicYear'=>$year];
    }
    return $periods;
}
