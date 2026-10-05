<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PKWT - {{ $user->nama }}</title>
    <style>
        @page { margin: 16mm 18mm; }
       body {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.32;
            color: #000;
            margin: 0;
            padding: 0;
        }
        /* ========== HEADER ========== */
        .header {
            margin: -16mm -18mm 8px -18mm; /* nilai sama persis dengan @page margin di atas */
            text-align: center;
        }
        .header img {
            width: 80%;
            display: block;
        }

        /* ========== TITLE ========== */
        .title {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            margin: 0 0 1px;
        }
        .no-surat {
            text-align: center;
            font-size: 9,5pt;
            margin-bottom: 9px;
        }
        .no-surat .label {
            display: inline-block;
        }

        p { margin: 6px 0; text-align: justify; }

        /* ========== IDENTITAS A / B ========== */
        .identitas-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0 1px;
        }
        .identitas-table td {
            padding: 1px 2px;
            vertical-align: top;
            font-size: 9pt;
        }
        .identitas-table .huruf {
            width: 18px;
            font-weight: normal;
        }
        .identitas-table .label {
            width: 130px;
            font-weight: normal;
        }
        .identitas-table .sep {
            width: 14px;
        }

        /* ========== LIST KETENTUAN ========== */
        ol.ketentuan {
            margin: 7px 0;
            padding-left: 20px;
        }
        ol.ketentuan > li {
            margin-bottom: 3px;
            text-align: justify;
        }
        ol.sub-ketentuan {
            margin: 2px 0;
            padding-left: 18px;
        }
        ol.sub-ketentuan > li {
            margin-bottom: 2px;
            text-align: justify;
        }

        /* ========== PENUTUP ========== */
        .dibuat-table {
            margin-top: 9px;
            border-collapse: collapse;
        }
        .dibuat-table td {
            padding: 0px 2px;
            font-size: 9pt;
        }
        .dibuat-table .label {
            width: 100px;
        }

        /* ========== TANDA TANGAN ========== */
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .ttd-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 9pt;
        }
        .ttd-space {
            height: 50px;
        }
        .ttd-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* ========== FOOTER ========== */
        .footer {
            margin-top: 1px;
        }
        .footer img {
            display: block;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <img src="{{ public_path('images/pkwt-header.jpg') }}" alt="Header" style="width: 100%;">
    </div>
    
    

    <!-- TITLE -->
    <div class="title">PERJANJIAN KERJA WAKTU TERTENTU</div>
    <div class="no-surat">No.&nbsp;&nbsp;&nbsp;&nbsp;{{ $pkwt->nomor_surat }}</div>

    <p>Kami yang bertandatangan dibawah ini sepakat untuk melakukan perjanjian kerja antara :</p>

    <!-- A. PIHAK PERTAMA -->
    <table class="identitas-table">
        <tr>
            <td class="huruf">A.</td>
            <td class="label">Nama Lengkap</td>
            <td class="sep">:</td>
            <td>{{ $pihakPertama }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="label">Jabatan</td>
            <td class="sep">:</td>
            <td>{{ $jabatanPihakPertama }}</td>
        </tr>
    </table>
    <p>Bertindak untuk dan atas nama perusahaan pemberi kerja, untuk selanjutnya disebut <strong>Pihak Pertama</strong>.</p>

    <!-- B. PIHAK KEDUA -->
    <table class="identitas-table">
        <tr>
            <td class="huruf">B.</td>
            <td class="label">Nama Lengkap</td>
            <td class="sep">:</td>
            <td>{{ $user->nama }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="label">Tempat tanggal lahir</td>
            <td class="sep">:</td>
            <td>{{ $user->tempat_lahir ?: '—' }}, {{ $user->tanggal_lahir ? $user->tanggal_lahir->format('d F Y') : '—' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="label">Alamat</td>
            <td class="sep">:</td>
            <td>{{ $user->alamat ?: '—' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="label">No. KTP</td>
            <td class="sep">:</td>
            <td>{{ $user->nik ?: '—' }}</td>
        </tr>
    </table>
    <p>Bertindak untuk dan atas nama diri sendiri, untuk selanjutnya disebut <strong>Pihak Kedua</strong></p>

    <p>Para Pihak sepakat melakukan perjanjian kerja dengan ketentuan sebagai berikut:</p>

    <!-- 14 KETENTUAN -->
    <ol class="ketentuan">
        <li><strong>Pihak Pertama</strong> menyatakan menerima <strong>Pihak Kedua</strong> untuk bekerja di PT Walet Abdillah Jabli.</li>
        <li><strong>Pihak Pertama</strong> mempekerjakan <strong>Pihak Kedua</strong> sebagai Karyawan.</li>
        <li><strong>Pihak Pertama</strong> menerima <strong>Pihak Kedua</strong> sebagai tenaga kontrak mulai tanggal {{ $pkwt->tanggal_mulai->format('j/n/Y') }} sampai dengan {{ $pkwt->tanggal_selesai->format('j/n/Y') }}.</li>
        <li><strong>Pihak Pertama</strong> membayar <strong>Pihak Kedua</strong> sesuai yang telah disepakati dan dibayarkan setiap 1 (satu) bulan berjalan dan dapat dievaluasi sesuai dengan keputusan management perusahaan.</li>
        <li><strong>Pihak Kedua</strong> siap dan sepakat untuk memenuhi target sebagaimana yang ditentukan oleh perusahaan.</li>
        <li>Apabila <strong>Pihak Kedua</strong> tidak dapat memenuhi target yang telah ditetapkan maka <strong>Pihak Kedua</strong> bersedia untuk tidak dilanjutkan sisa kontraknya dan tidak menggugat kepada pihak manapun.</li>
        <li><strong>Pihak Kedua</strong> menyatakan taat dan tunduk pada peraturan perusahaan yang ada di PT Walet Abdillah Jabli.</li>
        <li><strong>Pihak Kedua</strong> bersedia menyerahkan data diri ke <strong>Pihak Pertama</strong> untuk kepentingan data kepersonaliaan perusahaan.</li>
        <li><strong>Pihak Kedua</strong> bersedia dan sanggup bekerja sesuai kebutuhan <strong>Pihak Pertama</strong> dengan hari dan jam kerja yang telah ditentukan oleh <strong>Pihak Pertama</strong>.</li>
        <li>Perjanjian kerja ini berakhir dengan sendirinya apabila <strong>Pihak Kedua</strong> mengundurkan diri, meninggal dunia, berakhirnya jangka waktu perjanjian ini, dan tidak ada lagi pekerjaan dari <strong>Pihak Pertama</strong> selaku pemberi kerja.</li>
        <li>
            <strong>Pihak Pertama</strong> dapat memutuskan hubungan kerja dengan <strong>Pihak Kedua</strong> apabila:
            <ol class="sub-ketentuan" type="a">
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai tidak mematuhi peraturan perusahaan yang telah ditentukan</li>
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai tidak menjaga kerahasiaan Perusahaan.</li>
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai menentang perintah atasan/pimpinan.</li>
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai membuat keributan di area perusahaan.</li>
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai membuat hal yang merugikan perusahaan.</li>
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai menghina atau mencemarkan nama baik Perusahaan.</li>
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai tidak masuk kerja selama 3 hari kerja berturut-turut atau 5 hari dalam satu bulan tanpa alasan yang jelas.</li>
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai mengambil barang milik Perusahaan.</li>
                <li><strong>Pihak Kedua</strong> dengan sengaja dan atau lalai tidak melakukan pekerjaan untuk memenuhi target yang telah ditentukan oleh atasannya secara berturut-turut selama 3 kali dalam masa perjanjian ini.</li>
            </ol>
        </li>
        <li>Bilamana terjadi Perselisihan Hubungan Industrial <strong>Para Pihak</strong> sepakat menyelesaikan dengan cara musyawarah.</li>
        <li><strong>Para Pihak</strong> Sepakat perjanjian ini berakhir apabila tidak ada lagi pekerjaan yang diberikan <strong>Pihak Pertama</strong></li>
        <li>Apabila perjanjian ini berakhir dan salah satu pihak mengakhiri perjanjian ini, <strong>Para Pihak</strong> sepakat untuk tidak menuntut kompensasi dan ganti rugi berupa apapun kepada masing-masing pihak.</li>
    </ol>

    <!-- DIBUAT DAN DISEPAKATI -->
    <table class="dibuat-table">
        <tr>
            <td class="label">Dibuat dan disepakati</td>
            <td>: Di {{ $pkwt->tempat_dibuat }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td>: {{ $pkwt->tanggal_dibuat->format('j F Y') }}</td>
        </tr>
    </table>

    <!-- TANDA TANGAN -->
    <table class="ttd-table">
        <tr>
            <td>Pihak Pertama</td>
            <td>Pihak Kedua</td>
        </tr>
        <tr>
            <td class="ttd-space"></td>
            <td class="ttd-space"></td>
        </tr>
        <tr>
            <td class="ttd-name">{{ $pihakPertama }}</td>
            <td class="ttd-name">{{ $user->nama }}</td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <img src="{{ public_path('images/pkwt-footer.jpg') }}" alt="Footer" style="width: 100%;">
    </div>
</body>
</html>