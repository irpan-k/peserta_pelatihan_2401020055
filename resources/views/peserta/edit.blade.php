<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Peserta</title>
</head>
<body>
    <h1>Edit Peserta</h1>

    @if (session('error'))
        <p><strong>{{ session('error') }}</strong></p>
    @endif

    @if ($errors->any())
        <p>Perubahan belum dapat disimpan:</p>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('peserta.update', $peserta['id']) }}">
        @csrf
        @method('PUT')
        <p>
            <label>Kode Peserta (10 karakter):<br>
                <input type="text" name="kode_peserta" value="{{ old('kode_peserta', $peserta['kode_peserta']) }}">
            </label>
        </p>
        <p>
            <label>Nama Peserta:<br>
                <input type="text" name="nama_peserta" value="{{ old('nama_peserta', $peserta['nama_peserta']) }}">
            </label>
        </p>
        <p>
            <label>Email:<br>
                <input type="text" name="email" value="{{ old('email', $peserta['email']) }}">
            </label>
        </p>
        <p>
            <label>Usia:<br>
                <input type="text" name="usia" value="{{ old('usia', $peserta['usia']) }}">
            </label>
        </p>
        <p>
            <label>Program Pelatihan:<br>
                <select name="program_pelatihan_id">
                    <option value="">-- Pilih program --</option>
                    @foreach ($daftarProgram as $prog)
                        <option value="{{ $prog['id'] }}"
                            @if ((string) old('program_pelatihan_id', $peserta['program_pelatihan_id']) === (string) $prog['id']) selected @endif>
                            {{ $prog['nama_program'] }}
                        </option>
                    @endforeach
                </select>
            </label>
        </p>
        <p>
            <button type="submit">Simpan Perubahan</button>
            <a href="{{ route('peserta.index') }}">Batal</a>
        </p>
    </form>
</body>
</html>