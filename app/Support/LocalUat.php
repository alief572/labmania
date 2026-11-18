<?php

namespace App\Support;

use App\Http\Controllers\MasterProgramController;
use App\Http\Controllers\SalesController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocalUat
{
    private const MARKER = 'UAT sintetis lokal v1';

    private const ACCOUNTS = [1 => 'superadmin', 2 => 'sales', 3 => 'manajemen', 4 => 'admin', 5 => 'direktur', 6 => 'trainer'];

    public static function guard(): void
    {
        $c = DB::connection()->getConfig();
        $test = app()->runningUnitTests() && app()->environment('testing') && $c['driver'] === 'sqlite' && $c['database'] === ':memory:';
        if (! config('labmania.local_uat_enabled') || (! $test && (! app()->environment('local') || $c['driver'] !== 'mysql'
            || ($c['host'] ?? '') !== 'uat-db' || ($c['port'] ?? '') != 3306 || $c['database'] !== 'labmania_laravel'
            || ($c['username'] ?? '') !== 'labmania_uat' || ! empty($c['url'])))) {
            throw new RuntimeException('Perintah ditolak: hanya Docker UAT lokal dengan host uat-db dan akun labmania_uat.');
        }
    }

    public static function bootstrap(): array
    {
        self::guard();

        return DB::transaction(function () {
            if (DB::table('groups')->where('id_group', 1)->where('ket', self::MARKER)->exists()) {
                return [];
            }
            foreach (DB::connection()->getSchemaBuilder()->getTableListing(schemaQualified: false) as $table) {
                if ($table !== 'migrations' && DB::table($table)->exists()) {
                    throw new RuntimeException('Bootstrap menolak database berisi data non-UAT; tidak ada data dihapus.');
                }
            }
            $passwords = [];
            foreach (self::ACCOUNTS as $id => $role) {
                DB::table('groups')->insert(['id_group' => $id, 'nm_group' => $id === 1 ? 'Superadmin' : ucfirst($role), 'ket' => self::MARKER]);
                $username = $role === 'superadmin' ? 'admin' : 'uat_'.$role;
                $passwords[$username] = bin2hex(random_bytes(12));
                $user = User::forceCreate(['username' => $username, 'password' => password_hash($passwords[$username], PASSWORD_DEFAULT),
                    'nm_lengkap' => 'UAT '.ucfirst($role), 'email' => $username.'@example.invalid', 'st_aktif' => 1, 'deleted' => 0]);
                DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => $id]);
                DB::table('ms_user_karyawan')->insert(['kode' => 'UAT-'.strtoupper($role), 'nama' => 'UAT '.ucfirst($role), 'id_group' => $id, 'id_user' => $user->id_user, 'aktif' => 1]);
            }
            $actor = User::where('username', 'admin')->firstOrFail();
            Auth::setUser($actor);
            try {
                self::menus();
                self::examples();
                write_log('UAT Lokal', 'Tambah', 'Inisialisasi data sintetis UAT, bukan data produksi', ['accounts' => array_keys($passwords)], 'groups', 1, 'UAT');
            } finally {
                Auth::forgetUser();
            }

            return $passwords;
        });
    }

    public static function ready(): void
    {
        self::guard();
        if (! DB::table('groups')->where('id_group', 1)->where('ket', self::MARKER)->exists()
            || User::whereIn('username', array_map(fn ($role) => $role === 'superadmin' ? 'admin' : 'uat_'.$role, self::ACCOUNTS))->count() !== 6) {
            throw new RuntimeException('Bootstrap UAT belum lengkap; web tidak boleh dialihkan.');
        }
    }

    private static function menus(): void
    {
        $menus = collect(config('navigation'))->filter(fn ($n) => $n['menu'])->unique('menu')->values()->all();
        array_unshift($menus, ['menu' => 'dashboard', 'label' => 'Dashboard', 'url' => '/']);
        foreach ($menus as $order => $menu) {
            $code = $menu['menu'];
            $id = DB::table('ms_menu')->insertGetId(['kode' => $code, 'judul' => $menu['label'], 'link' => $menu['url'], 'parent_id' => 0,
                'kelompok' => 1, 'urutan' => $order, 'aktif' => 1, 'sistem' => in_array($code, ['dashboard', 'menu_labmania', 'permission_group', 'master_data.users'], true) ? 1 : 0]);
            $actions = match ($code) {
                'dashboard', 'activity_log', 'monitoring_peserta' => ['lihat'],
                'master_data.katalog' => ['lihat', 'ubah', 'cetak'],
                'permission_group', 'master_data.role', 'master_data.peserta' => ['lihat', 'tambah', 'ubah', 'hapus'],
                'master_data.company' => ['lihat', 'tambah', 'ubah', 'aktif', 'hapus'],
                'master_program', 'penjualan_program' => ['lihat', 'tambah', 'ubah', 'aktif', 'approve', ...($code === 'penjualan_program' ? ['hapus'] : [])],
                'approval_pengajuan_biaya' => ['lihat', 'approve'],
                'sales_order', 'manage_peserta' => ['lihat', 'ubah'],
                'refund_daftar' => ['lihat', 'tambah', 'ubah'],
                'refund_approval' => ['lihat', 'approve'],
                'invoicing', 'pengajuan_biaya', 'quotation_program' => ['lihat', 'tambah', 'ubah'],
                default => ['lihat', 'tambah', 'ubah', 'aktif'],
            };
            foreach ($actions as $action) {
                DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => $action]);
                foreach ([2, 3, 4, 5, 6] as $role) {
                    $allowed = match ($role) {
                        2 => in_array($code, ['dashboard', 'master_program', 'penjualan_program', 'quotation_program', 'pengajuan_biaya', 'manage_peserta'], true) && ! in_array($action, ['approve', 'hapus', 'aktif'], true),
                        3 => $action === 'lihat' && ! in_array($code, ['permission_group', 'master_data.role', 'master_data.users', 'menu_labmania'], true),
                        4 => ! in_array($code, ['permission_group', 'master_data.role', 'master_data.users', 'menu_labmania', 'activity_log', 'approval_pengajuan_biaya'], true) && $action !== 'approve',
                        5 => $action === 'lihat' || ($code === 'approval_pengajuan_biaya' && $action === 'approve'),
                        6 => in_array($code, ['dashboard', 'pengajuan_biaya', 'manage_peserta', 'monitoring_peserta'], true),
                    };
                    if ($allowed) {
                        DB::table('ms_group_akses')->insert(['id_group' => $role, 'id_menu' => $id, 'aksi' => $action]);
                    }
                }
            }
        }
    }

    private static function examples(): void
    {
        $ids = [];
        foreach (config('labmania.atribut') as $attribute) {
            $ids[$attribute['table']] = DB::table($attribute['table'])->insertGetId(['kode' => $attribute['prefix'].'-UAT', 'nama' => $attribute['label'].' UAT', 'aktif' => 1]);
        }
        foreach ([9001 => ['1102-01-01', 'Piutang UAT'], 9002 => ['2104-01-07', 'PPN UAT'], 9003 => ['4101-01-03', 'Penjualan Public UAT'],
            9004 => ['4101-01-02', 'Penjualan B2B UAT'], 9005 => ['UAT-UM', 'Uang Muka WAPU UAT'], 9006 => ['UAT-HT', 'Hutang WAPU UAT']] as $id => [$number, $name]) {
            DB::table('coa_master')->insert(['id' => $id, 'no_perkiraan' => $number, 'nama' => $name, 'level' => '5']);
        }
        $units = [];
        foreach (['event' => ['fixed', 'Event'], 'day' => ['fixed', 'Day'], 'peserta_event' => ['variable', 'Peserta/Event'], 'peserta_day' => ['variable', 'Peserta/Day']] as $code => [$kind, $name]) {
            $units[$code] = DB::table('ms_satuan')->insertGetId(['category' => 'rate_'.$kind, 'code' => $code, 'nama' => $name, 'deleted' => '0']);
        }
        foreach (['fixed' => ['event', 100000], 'variable' => ['peserta_event', 25000]] as $kind => [$unit, $cost]) {
            DB::table('ms_rate')->insert(['kode' => strtoupper(substr($kind, 0, 1)).'C-UAT', 'tipe' => $kind, 'item' => 'Biaya '.$kind.' UAT', 'id_satuan' => $units[$unit], 'default_cost' => $cost, 'coa' => '4101-01-03', 'aktif' => 1]);
        }
        $product = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-UAT', 'judul' => 'Pelatihan Sintetis UAT', 'id_jenis_produk' => $ids['ms_jenis_produk'],
            'id_jenis_bidang' => $ids['ms_jenis_bidang'], 'id_jenis_sertifikasi' => $ids['ms_jenis_sertifikasi'], 'id_varian_pelaksanaan' => $ids['ms_varian_pelaksanaan'], 'aktif' => 1]);
        DB::table('ms_template_konfirmasi')->insert(['kode' => 'KF-UAT', 'nama' => 'Konfirmasi UAT', 'label_kegiatan' => 'Pelatihan UAT', 'id_jenis_produk' => $ids['ms_jenis_produk'], 'isian' => '["nama","nik","email","wa"]', 'pernyataan' => 'Pernyataan sintetis UAT.', 'aktif' => 1]);
        DB::table('master_customer')->insert(['id_customer' => 'CO-UAT', 'name_customer' => 'Company Sintetis UAT', 'activation' => 'aktif', 'jenis_instansi' => 'Swasta',
            'address_office' => 'Alamat Sintetis UAT', 'npwp' => '0000000000000000', 'pic_name' => 'PIC UAT', 'pic_email' => 'pic@example.invalid', 'perlakuan_ppn' => 'PKP']);
        $trainer = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-UAT', 'nama' => 'Trainer Sintetis UAT', 'aktif' => 1, 'id_user' => User::where('username', 'uat_trainer')->value('id_user')]);
        $costs = [['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Biaya Sintetis UAT', 'id_satuan' => $units['event'], 'harga' => 100000, 'is_manual' => true]];
        $master = app(MasterProgramController::class);
        foreach ([false, true] as $final) {
            $master->store(new Request(['paket' => [$product], 'durasi_hari' => 2, 'jam_pembelajaran' => 16, 'target_peserta' => 10, 'harga_jual_peserta' => 1000000, 'target_profit' => 0, 'biaya' => $costs]));
            if ($final) {
                $masterId = (int) DB::table('master_program')->max('id');
                $master->finalkan($masterId);
            }
        }
        $sales = app(SalesController::class);
        foreach (['public', 'b2b'] as $kind) {
            $sales->store(new Request(['jenis' => $kind, 'id_master_program' => $masterId, 'id_customer' => $kind === 'b2b' ? 'CO-UAT' : null,
                'id_trainer' => $trainer, 'id_asisten' => DB::table('ms_user_karyawan')->where('id_group', 2)->value('id'),
                'tgl_mulai' => today()->addDays(45)->toDateString(), 'tgl_selesai' => today()->addDays(46)->toDateString(), 'waktu' => '08.00-16.00',
                'lokasi' => 'Lokasi Sintetis UAT', 'jam_pembelajaran' => 16, 'target_peserta' => 10, 'harga_jual_peserta' => 1000000, 'target_profit' => 0, 'biaya' => $costs]));
            if ($kind === 'b2b') {
                $sales->status(new Request, (int) DB::table('penjualan_program')->max('id'), 'finalkan');
            }
        }
    }
}
