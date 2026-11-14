<?php

namespace Tests\Feature\Master;

use App\Support\TrainerCvPdf;
use Illuminate\Support\Facades\DB;

/** PDF CV Trainer (FR-47): isi, lampiran tersisip, fallback berkas hilang, hak dan batas folder. */
class TrainerCvPdfTest extends MasterTestCase
{
    private string $root;

    private int $trainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/cvpdf-'.bin2hex(random_bytes(6)));
        mkdir($this->root.'/uploads/trainers', 0777, true);
        config(['labmania.document_root' => $this->root, 'labmania.legacy_document_root' => null]);
        $this->trainer = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-001', 'nama' => 'Ivan Uji', 'tgl_lahir' => '1985-05-01', 'bidang_keahlian' => "K3 Umum;\nAhli K3", 'aktif' => 1]);
        DB::table('ms_trainer_pelatihan')->insert(['id_trainer' => $this->trainer, 'urutan' => 1, 'judul' => 'Seminar K3']);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->root);
        parent::tearDown();
    }

    private function rmrf(string $dir): void
    {
        foreach (is_dir($dir) ? scandir($dir) : [] as $item) {
            if ($item !== '.' && $item !== '..') {
                is_dir("$dir/$item") ? $this->rmrf("$dir/$item") : @unlink("$dir/$item");
            }
        }
        @rmdir($dir);
    }

    private function attach(string $dokumen, string $file): void
    {
        DB::table('ms_trainer_lampiran')->insert(['id_trainer' => $this->trainer, 'urutan' => 1 + DB::table('ms_trainer_lampiran')->count(), 'dokumen' => $dokumen, 'tahun' => '2024', 'file_path' => $file]);
    }

    private function png(string $name): void
    {
        $image = imagecreatetruecolor(300, 200);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 220, 240));
        imagepng($image, $this->root.'/uploads/trainers/'.$name);
    }

    private function pages(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page[^s]#', $pdf);
    }

    public function test_requires_login_and_view_permission(): void
    {
        $this->get('/master/trainer/'.$this->trainer.'/cv.pdf')->assertRedirect('/login');
        $staff = $this->account();
        $this->login($staff)->get('/master/trainer/'.$this->trainer.'/cv.pdf')->assertForbidden();
        $this->allow($staff, 'master_trainer', 'lihat');
        $this->get('/master/trainer/'.$this->trainer.'/cv.pdf')->assertOk();
        $this->get('/master/trainer/99999/cv.pdf')->assertNotFound();
    }

    public function test_returns_inline_pdf_with_filename_and_logs_download(): void
    {
        $this->login($this->admin());
        $r = $this->get('/master/trainer/'.$this->trainer.'/cv.pdf')->assertOk();
        $this->assertStringStartsWith('%PDF', $r->getContent());
        $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
        $this->assertStringContainsString('filename="CV-TR-001-Ivan-Uji.pdf"', $r->headers->get('Content-Disposition'));
        $this->assertSame(1, DB::table('logs')->where('module', 'Master Trainer')->where('action', 'Unduh')->count());
    }

    public function test_image_attachment_adds_a_page_and_missing_file_adds_notice_page_without_failing(): void
    {
        $this->login($this->admin());
        $base = $this->pages($this->get('/master/trainer/'.$this->trainer.'/cv.pdf')->getContent());
        $this->png('a.png');
        $this->attach('Sertifikat A', 'a.png');
        $this->assertSame($base + 1, $this->pages($this->get('/master/trainer/'.$this->trainer.'/cv.pdf')->getContent()));
        $this->attach('Sertifikat Hilang', 'tidak-ada.png');
        $this->assertSame($base + 2, $this->pages($this->get('/master/trainer/'.$this->trainer.'/cv.pdf')->assertOk()->getContent()));
    }

    public function test_attachment_lookup_stays_inside_trainer_folder(): void
    {
        file_put_contents($this->root.'/rahasia.png', 'x');
        $this->png('ok.png');
        $this->assertNull(TrainerCvPdf::locate('../rahasia.png'));
        $this->assertNull(TrainerCvPdf::locate('..'));
        $this->assertNull(TrainerCvPdf::locate(''));
        $this->assertSame(realpath($this->root.'/uploads/trainers/ok.png'), TrainerCvPdf::locate('ok.png'));
        $this->assertSame(realpath($this->root.'/uploads/trainers/ok.png'), TrainerCvPdf::locate('C:\\x\\..\\ok.png'));
    }

    public function test_sections_use_dash_for_empty_values_and_split_expertise(): void
    {
        [$summary] = TrainerCvPdf::sections(DB::table('ms_trainer')->find($this->trainer));
        $map = array_column($summary, 1, 0);
        $this->assertSame('K3 Umum; Ahli K3', $map['Bidang keahlian']);
        $this->assertSame('—', $map['Alamat rumah']);
        $this->assertSame('—, 01-05-1985', $map['Tempat, tanggal lahir']);
    }
}
