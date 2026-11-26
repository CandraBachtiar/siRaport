<header class="rapor-identitas">
    <table aria-label="Identitas rapor">
        <colgroup>
            <col style="width:20%">
            <col style="width:2%">
            <col style="width:40%">
            <col style="width:19%">
            <col style="width:2%">
            <col style="width:17%">
        </colgroup>

        <tbody>
            <tr>
                <td>Nama Murid</td>
                <td>:</td>
                <td>{{ $murid->nama }}</td>
                <td>Kelas</td>
                <td>:</td>
                <td>{{ $kelasRapor }}</td>
            </tr>

            <tr>
                <td>NIS/NISN</td>
                <td>:</td>
                <td>
                    {{ $murid->nis }} /
                    {{ $murid->nisn ?: '—' }}
                </td>
                <td>Fase</td>
                <td>:</td>
                <td>{{ $fase }}</td>
            </tr>

            <tr>
                <td>Sekolah</td>
                <td>:</td>
                <td>{{ $school?->nama_sekolah ?: '—' }}</td>
                <td>Semester</td>
                <td>:</td>
                <td>{{ $semesterRapor }}</td>
            </tr>

            <tr>
                <td>Alamat</td>
                <td>:</td>
                <td>{{ $school?->alamat ?: '—' }}</td>
                <td>Tahun Ajaran</td>
                <td>:</td>
                <td>{{ $selectedSchoolYear->tahun }}</td>
            </tr>
        </tbody>
    </table>
</header>