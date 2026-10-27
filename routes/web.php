<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


if (!function_exists('statusUsia')) {

    // Fungsi sederhana sesuai soal: 'Dewasa' jika usia >= 21, selain itu 'Remaja Akhir'
    function statusUsia(int $usia): string
    {
        if ($usia >= 21) {
            return 'Dewasa';
        }

        return 'Remaja Akhir';
    }

    function pesanSistem(): string
    {
        return 'Terjadi kesalahan pada sistem. Silakan coba lagi nanti.';
    }

    // Ambil objek PDO dari koneksi Laravel
    function pdoPeserta(): PDO
    {
        return DB::connection()->getPdo();
    }

    // trim + strip_tags pada input teks
    function bersihkanInput(Request $request): array
    {
        return [
            'kode_peserta'         => trim(strip_tags((string) $request->input('kode_peserta', ''))),
            'nama_peserta'         => trim(strip_tags((string) $request->input('nama_peserta', ''))),
            'email'                => trim(strip_tags((string) $request->input('email', ''))),
            'usia'                 => trim((string) $request->input('usia', '')),
            'program_pelatihan_id' => trim((string) $request->input('program_pelatihan_id', '')),
        ];
    }

    function daftarProgram(PDO $pdo): array
    {
        return $pdo->query('SELECT id, nama_program FROM program_pelatihan ORDER BY nama_program')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    function ambilPeserta(PDO $pdo, string $id): ?array
    {
        if (!ctype_digit($id)) {
            return null;
        }

        $stmt = $pdo->prepare(
            'SELECT id, kode_peserta, nama_peserta, email, usia, program_pelatihan_id
             FROM peserta WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $baris = $stmt->fetch(PDO::FETCH_ASSOC);

        return $baris ?: null;
    }

    // Cek kode/email sudah dipakai (kecuali data dengan id tertentu saat Update)
    function sudahAda(PDO $pdo, string $kolom, string $nilai, ?int $kecualiId): bool
    {
        $sql = $kolom === 'email'
            ? 'SELECT COUNT(*) FROM peserta WHERE email = :nilai'
            : 'SELECT COUNT(*) FROM peserta WHERE kode_peserta = :nilai';
        $params = [':nilai' => $nilai];

        if ($kecualiId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $kecualiId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    // Validasi sisi server. Mengembalikan array [field => pesan]
    function validasiPeserta(PDO $pdo, array $d, ?int $kecualiId = null): array
    {
        $e = [];

        if (!preg_match('/^[A-Za-z0-9]{10}$/', $d['kode_peserta'])) {
            $e['kode_peserta'] = 'Kode peserta harus tepat 10 karakter huruf atau angka.';
        }

        $panjangNama = mb_strlen($d['nama_peserta']);
        if ($panjangNama < 3 || $panjangNama > 100) {
            $e['nama_peserta'] = 'Nama peserta harus 3 sampai 100 karakter.';
        }

        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 100) {
            $e['email'] = 'Format email tidak valid (maksimal 100 karakter).';
        }

        $usia = filter_var($d['usia'], FILTER_VALIDATE_INT);
        if ($usia === false || $usia < 17 || $usia > 60) {
            $e['usia'] = 'Usia harus bilangan bulat antara 17 sampai 60.';
        }

        if (!ctype_digit($d['program_pelatihan_id'])) {
            $e['program_pelatihan_id'] = 'Program pelatihan wajib dipilih.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM program_pelatihan WHERE id = :id');
            $stmt->execute([':id' => $d['program_pelatihan_id']]);
            if ((int) $stmt->fetchColumn() === 0) {
                $e['program_pelatihan_id'] = 'Program pelatihan yang dipilih tidak tersedia.';
            }
        }

        // Cek duplikat hanya jika formatnya sudah benar
        if (!isset($e['kode_peserta']) && sudahAda($pdo, 'kode_peserta', $d['kode_peserta'], $kecualiId)) {
            $e['kode_peserta'] = 'Kode peserta sudah digunakan peserta lain.';
        }
        if (!isset($e['email']) && sudahAda($pdo, 'email', $d['email'], $kecualiId)) {
            $e['email'] = 'Email sudah digunakan peserta lain.';
        }

        return $e;
    }
}

// ---------- Route ----------
Route::get('/', function () {
    return view('welcome');
});

// READ + pencarian + filter
Route::get('/peserta', function (Request $request) {
    $q = trim(strip_tags((string) $request->query('q', '')));
    $filter = trim((string) $request->query('program', ''));

    $daftarProgram = [];
    $daftarPeserta = [];
    $errorSistem = null;

    try {
        $pdo = pdoPeserta();
        $daftarProgram = daftarProgram($pdo);

        $sql = 'SELECT p.id, p.kode_peserta, p.nama_peserta, p.email, p.usia, g.nama_program
                FROM peserta p
                JOIN program_pelatihan g ON g.id = p.program_pelatihan_id
                WHERE 1 = 1';
        $params = [];

        if ($q !== '') {
            $sql .= ' AND (p.kode_peserta LIKE :q1 OR p.nama_peserta LIKE :q2 OR p.email LIKE :q3)';
            $like = '%' . $q . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        if ($filter !== '' && ctype_digit($filter)) {
            $sql .= ' AND p.program_pelatihan_id = :pid';
            $params[':pid'] = $filter;
        }

        $sql .= ' ORDER BY p.id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $daftarPeserta = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        report($e);
        $errorSistem = pesanSistem();
    }

    return view('peserta.index', [
        'daftarPeserta' => $daftarPeserta,
        'daftarProgram' => $daftarProgram,
        'q'             => $q,
        'filter'        => $filter,
        'errorSistem'   => $errorSistem,
    ]);
})->name('peserta.index');

// CREATE
Route::post('/peserta', function (Request $request) {
    $d = bersihkanInput($request);

    try {
        $pdo = pdoPeserta();
        $errors = validasiPeserta($pdo, $d);

        if ($errors) {
            return redirect()->route('peserta.index')->withErrors($errors)->withInput($d);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO peserta
                (kode_peserta, nama_peserta, email, usia, program_pelatihan_id, created_at, updated_at)
             VALUES
                (:kode, :nama, :email, :usia, :program, NOW(), NOW())'
        );
        $stmt->execute([
            ':kode'    => $d['kode_peserta'],
            ':nama'    => $d['nama_peserta'],
            ':email'   => $d['email'],
            ':usia'    => (int) $d['usia'],
            ':program' => (int) $d['program_pelatihan_id'],
        ]);

        return redirect()->route('peserta.index')->with('sukses', 'Peserta berhasil ditambahkan.');
    } catch (Throwable $e) {
        report($e);

        // 23000 = pelanggaran constraint (mis. UNIQUE)
        $pesan = ($e instanceof PDOException && $e->getCode() === '23000')
            ? 'Kode peserta atau email sudah digunakan.'
            : pesanSistem();

        return redirect()->route('peserta.index')->withInput($d)->with('error', $pesan);
    }
})->name('peserta.store');

// Form edit
Route::get('/peserta/{id}/edit', function (string $id) {
    try {
        $pdo = pdoPeserta();
        $peserta = ambilPeserta($pdo, $id);

        if ($peserta === null) {
            return redirect()->route('peserta.index')->with('error', 'Data peserta tidak ditemukan.');
        }

        return view('peserta.edit', [
            'peserta'       => $peserta,
            'daftarProgram' => daftarProgram($pdo),
        ]);
    } catch (Throwable $e) {
        report($e);

        return redirect()->route('peserta.index')->with('error', pesanSistem());
    }
})->name('peserta.edit');

// UPDATE
Route::put('/peserta/{id}', function (Request $request, string $id) {
    $d = bersihkanInput($request);

    try {
        $pdo = pdoPeserta();

        if (ambilPeserta($pdo, $id) === null) {
            return redirect()->route('peserta.index')->with('error', 'Data peserta tidak ditemukan.');
        }

        $errors = validasiPeserta($pdo, $d, (int) $id);

        if ($errors) {
            return redirect()->route('peserta.edit', $id)->withErrors($errors)->withInput($d);
        }

        $stmt = $pdo->prepare(
            'UPDATE peserta
             SET kode_peserta = :kode, nama_peserta = :nama, email = :email,
                 usia = :usia, program_pelatihan_id = :program, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':kode'    => $d['kode_peserta'],
            ':nama'    => $d['nama_peserta'],
            ':email'   => $d['email'],
            ':usia'    => (int) $d['usia'],
            ':program' => (int) $d['program_pelatihan_id'],
            ':id'      => (int) $id,
        ]);

        return redirect()->route('peserta.index')->with('sukses', 'Data peserta berhasil diperbarui.');
    } catch (Throwable $e) {
        report($e);

        $pesan = ($e instanceof PDOException && $e->getCode() === '23000')
            ? 'Kode peserta atau email sudah digunakan.'
            : pesanSistem();

        return redirect()->route('peserta.edit', $id)->withInput($d)->with('error', $pesan);
    }
})->name('peserta.update');

// DELETE
Route::delete('/peserta/{id}', function (string $id) {
    try {
        if (!ctype_digit($id)) {
            return redirect()->route('peserta.index')->with('error', 'Data peserta tidak ditemukan.');
        }

        $stmt = pdoPeserta()->prepare('DELETE FROM peserta WHERE id = :id');
        $stmt->execute([':id' => (int) $id]);

        if ($stmt->rowCount() === 0) {
            return redirect()->route('peserta.index')->with('error', 'Data peserta tidak ditemukan.');
        }

        return redirect()->route('peserta.index')->with('sukses', 'Peserta berhasil dihapus.');
    } catch (Throwable $e) {
        report($e);

        return redirect()->route('peserta.index')->with('error', pesanSistem());
    }
})->name('peserta.destroy');