<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Peserta Pelatihan</title>
</head>
<body>
    <h1>Data Peserta Pelatihan</h1>

    {{-- Feedback --}}
    @if (session('sukses'))
        <p><strong>{{ session('sukses') }}</strong></p>
    @endif

    @if (session('error'))
        <p><strong>{{ session('error') }}</strong></p>
    @endif

    @if (!empty($errorSistem))
        <p><strong>{{ $errorSistem }}</strong></p>
    @endif

    @if ($errors->any())
        <p>Data belum dapat disimpan:</p>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    {{-- Form tambah (sengaja tanpa atribut required/maxlength agar validasi server teruji) --}}
    <h2>Tambah Peserta</h2>
    <form method="POST" action="{{ route('peserta.store') }}">
        @csrf
        <p>
            <label>Kode Peserta (10 karakter):<br>
                <input type="text" name="kode_peserta" value="{{ old('kode_peserta') }}">
            </label>
        </p>
        <p>
            <label>Nama Peserta:<br>
                <input type="text" name="nama_peserta" value="{{ old('nama_peserta') }}">
            </label>
        </p>
        <p>
            <label>Email:<br>
                <input type="text" name="email" value="{{ old('email') }}">
            </label>
        </p>
        <p>
            <label>Usia:<br>
                <input type="text" name="usia" value="{{ old('usia') }}">
            </label>
        </p>
        <p>
            <label>Program Pelatihan:<br>
                <select name="program_pelatihan_id">
                    <option value="">-- Pilih program --</option>
                    @foreach ($daftarProgram as $prog)
                        <option value="{{ $prog['id'] }}" @if ((string) old('program_pelatihan_id') === (string) $prog['id']) selected @endif>
                            {{ $prog['nama_program'] }}
                        </option>
                    @endforeach
                </select>
            </label>
        </p>
        <p><button type="submit">Simpan</button></p>
    </form>

    <hr>

    {{-- Pencarian dan filter --}}
    <h2>Cari dan Filter</h2>
    <form method="GET" action="{{ route('peserta.index') }}">
        <p>
            <label>Kata kunci (kode, nama, atau email):<br>
                <input type="text" name="q" value="{{ $q }}">
            </label>
        </p>
        <p>
            <label>Program:<br>
                <select name="program">
                    <option value="">-- Semua program --</option>
                    @foreach ($daftarProgram as $prog)
                        <option value="{{ $prog['id'] }}" @if ($filter === (string) $prog['id']) selected @endif>
                            {{ $prog['nama_program'] }}
                        </option>
                    @endforeach
                </select>
            </label>
        </p>
        <p>
            <button type="submit">Terapkan</button>
            <a href="{{ route('peserta.index') }}">Reset</a>
        </p>
    </form>

    <hr>

    {{-- Tabel data --}}
    <h2>Daftar Peserta</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Usia</th>
                <th>Status Usia</th>
                <th>Program</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @if (count($daftarPeserta) === 0)
                <tr>
                    <td colspan="8">Tidak ada data peserta.</td>
                </tr>
            @else
                @foreach ($daftarPeserta as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $p['kode_peserta'] }}</td>
                        <td>{{ $p['nama_peserta'] }}</td>
                        <td>{{ $p['email'] }}</td>
                        <td>{{ $p['usia'] }}</td>
                        <td>{{ statusUsia((int) $p['usia']) }}</td>
                        <td>{{ $p['nama_program'] }}</td>
                        <td>
                            <a href="{{ route('peserta.edit', $p['id']) }}">Edit</a>
                            <form method="POST" action="{{ route('peserta.destroy', $p['id']) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>
</html>