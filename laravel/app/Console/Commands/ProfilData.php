<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Profil data Master Data/Program pada salinan DB (read-only). Hanya ANGKA dan ID teknis; tidak mencetak nama,
 * telepon, email, NIK, atau NPWP. Hasilnya menjadi daftar perbaikan data sebelum NOT NULL/cutover (fase 7).
 */
class ProfilData extends Command
{
    protected $signature = 'labmania:profil-data {--json : Keluaran JSON}';

    protected $description = 'Profil kualitas data Master Data/Program (read-only, hanya angka)';

    public function handle(): int
    {
        $db = DB::connection()->getDatabaseName();
        if ($db === 'db_labmania') {
            $this->error('Tolak: ini database CI3.');

            return self::FAILURE;
        }
        DB::statement('SET SESSION TRANSACTION READ ONLY');
        $count = fn (string $sql, array $bind = []) => (int) (DB::selectOne($sql, $bind)->n ?? 0);

        $report = [
            'database' => $db,
            'jumlah_baris' => collect(['ms_jenis_produk', 'ms_jenis_sertifikasi', 'ms_jenis_bidang', 'ms_varian_pelaksanaan', 'paket_produk', 'paket_produk_lingkup', 'ms_peserta', 'master_customer', 'ms_rate', 'ms_trainer', 'ms_template_keterangan', 'ms_template_konfirmasi', 'ms_user_karyawan', 'users', 'groups', 'master_program', 'master_program_produk', 'master_program_biaya'])
                ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all(),
            'perbaikan' => [
                'product_tanpa_varian' => $count('SELECT COUNT(*) n FROM paket_produk WHERE id_varian_pelaksanaan IS NULL'),
                'product_aktif_kombinasi_ganda' => $count('SELECT COUNT(*) n FROM (SELECT 1 FROM paket_produk WHERE aktif = 1 GROUP BY id_jenis_produk, id_jenis_sertifikasi, id_jenis_bidang, IFNULL(id_varian_pelaksanaan,0) HAVING COUNT(*) > 1) x'),
                'company_tanpa_npwp' => $count("SELECT COUNT(*) n FROM master_customer WHERE deleted_date IS NULL AND (npwp IS NULL OR TRIM(npwp) = '')"),
                'company_npwp_format_salah' => $count("SELECT COUNT(*) n FROM master_customer WHERE deleted_date IS NULL AND npwp IS NOT NULL AND TRIM(npwp) <> '' AND REPLACE(REPLACE(REPLACE(npwp,'.',''),'-',''),' ','') NOT REGEXP '^[0-9]{15,16}$'"),
                'company_nilai_activation_tak_baku' => $count("SELECT COUNT(*) n FROM master_customer WHERE activation IS NOT NULL AND activation NOT IN ('aktif','nonaktif')"),
                'company_jenis_instansi_tak_valid' => $count('SELECT COUNT(*) n FROM master_customer WHERE deleted_date IS NULL AND (jenis_instansi IS NULL OR jenis_instansi NOT IN (\'Swasta\',\'BUMN/BUMD\',\'Pemerintah\',\'Pendidikan\',\'Lainnya\'))'),
                'peserta_belum_lengkap' => $count("SELECT COUNT(*) n FROM ms_peserta WHERE deleted_date IS NULL AND (nama IS NULL OR TRIM(nama)='' OR gender IS NULL OR instansi IS NULL OR TRIM(instansi)='' OR id_jenis_bidang IS NULL OR id_prov IS NULL OR id_kab IS NULL OR no_telp IS NULL OR TRIM(no_telp)='' OR email IS NULL OR TRIM(email)='')"),
                'program_tanpa_jp' => $count('SELECT COUNT(*) n FROM master_program WHERE jam_pembelajaran IS NULL OR jam_pembelajaran < 1'),
                'program_final_tanpa_jp' => $count("SELECT COUNT(*) n FROM master_program WHERE status = 'final' AND (jam_pembelajaran IS NULL OR jam_pembelajaran < 1)"),
                'program_tanpa_product' => $count('SELECT COUNT(*) n FROM master_program mp WHERE NOT EXISTS (SELECT 1 FROM master_program_produk x WHERE x.id_master_program = mp.id)'),
                'program_tanpa_biaya' => $count('SELECT COUNT(*) n FROM master_program mp WHERE NOT EXISTS (SELECT 1 FROM master_program_biaya x WHERE x.id_master_program = mp.id)'),
                'trainer_tanpa_akun' => $count('SELECT COUNT(*) n FROM ms_trainer WHERE id_user IS NULL'),
                'user_tanpa_role' => $count('SELECT COUNT(*) n FROM ms_user_karyawan WHERE id_group IS NULL'),
                'username_ganda' => $count('SELECT COUNT(*) n FROM (SELECT 1 FROM users GROUP BY LOWER(username) HAVING COUNT(*) > 1) x'),
                'rate_satuan_tak_sesuai_tipe' => $count("SELECT COUNT(*) n FROM ms_rate r LEFT JOIN ms_satuan s ON s.id = r.id_satuan WHERE s.id IS NULL OR (r.tipe = 'fixed' AND s.code NOT IN ('event','day')) OR (r.tipe = 'variable' AND s.code NOT IN ('peserta_event','peserta_day'))"),
            ],
            'orphan' => [
                'product_atribut_hilang' => $count('SELECT COUNT(*) n FROM paket_produk p WHERE NOT EXISTS (SELECT 1 FROM ms_jenis_produk x WHERE x.id = p.id_jenis_produk) OR NOT EXISTS (SELECT 1 FROM ms_jenis_bidang x WHERE x.id = p.id_jenis_bidang) OR NOT EXISTS (SELECT 1 FROM ms_jenis_sertifikasi x WHERE x.id = p.id_jenis_sertifikasi)'),
                'lingkup_tanpa_product' => $count('SELECT COUNT(*) n FROM paket_produk_lingkup l WHERE NOT EXISTS (SELECT 1 FROM paket_produk p WHERE p.id = l.id_paket_produk)'),
                'program_produk_tanpa_product' => $count('SELECT COUNT(*) n FROM master_program_produk x WHERE NOT EXISTS (SELECT 1 FROM paket_produk p WHERE p.id = x.id_paket_produk)'),
                'program_produk_tanpa_program' => $count('SELECT COUNT(*) n FROM master_program_produk x WHERE NOT EXISTS (SELECT 1 FROM master_program m WHERE m.id = x.id_master_program)'),
                'biaya_tanpa_program' => $count('SELECT COUNT(*) n FROM master_program_biaya b WHERE NOT EXISTS (SELECT 1 FROM master_program m WHERE m.id = b.id_master_program)'),
                'peserta_company_hilang' => $count('SELECT COUNT(*) n FROM ms_peserta p WHERE p.id_customer IS NOT NULL AND NOT EXISTS (SELECT 1 FROM master_customer c WHERE c.id_customer = p.id_customer)'),
                'trainer_akun_hilang' => $count('SELECT COUNT(*) n FROM ms_trainer t WHERE t.id_user IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.id_user = t.id_user)'),
                'user_group_tanpa_group' => $count('SELECT COUNT(*) n FROM user_groups ug WHERE NOT EXISTS (SELECT 1 FROM `groups` g WHERE g.id_group = ug.id_group)'),
            ],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }
        $this->info('Database: '.$db);
        $this->table(['Tabel', 'Baris'], collect($report['jumlah_baris'])->map(fn ($n, $t) => [$t, $n])->values()->all());
        foreach (['perbaikan' => 'Daftar perbaikan data (angka > 0 = perlu tindakan)', 'orphan' => 'Orphan (relasi putus)'] as $key => $title) {
            $this->newLine();
            $this->info($title);
            $this->table(['Pemeriksaan', 'Jumlah'], collect($report[$key])->map(fn ($n, $k) => [$k, $n])->values()->all());
        }

        return self::SUCCESS;
    }
}
