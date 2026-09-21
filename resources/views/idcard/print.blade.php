@php use SimpleSoftwareIO\QrCode\Facades\QrCode; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ID Card</title>
    <style>
        @page { margin: 7mm; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; margin: 0; padding: 0; color: #17324D; }

        .page-break { page-break-after: always; }

        table.grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.grid td {
            width: 33.33%;
            padding: 3px;
            vertical-align: top;
            page-break-inside: avoid;
        }

        .card {
            position: relative;
            width: 5.5cm;
            height: 8.5cm;
            margin: 0 auto;
            page-break-inside: avoid;
        }
        .card-bg {
            position: absolute;
            top: 0; left: 0;
            width: 5.5cm;
            height: 8.5cm;
        }

        /* QR, di dalam kotak putih border gold */
        .qr-overlay {
            position: absolute;
            top: 2.79cm;
            left: 1.88cm;
            width: 1.75cm;
            height: 1.75cm;
        }
        .qr-overlay img {
            width: 1.75cm;
            height: 1.75cm;
            display: block;
        }

        /* nama karyawan */
        .nama-overlay {
            position: absolute;
            top: 4.72cm;
            left: 0;
            width: 5.5cm;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            color: #17324D;
            letter-spacing: .2px;
        }

        /* NIP */
        .nip-overlay {
            position: absolute;
            top: 5.23cm;
            left: 0;
            width: 5.5cm;
            text-align: center;
            font-size: 8px;
            font-weight: 600;
            color: #3A5468;
        }

        /* jabatan, baris atas di dalam pill */
        .jabatan-overlay {
            position: absolute;
            top: 5.71cm;
            left: 0;
            width: 5.5cm;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            color: #17324D;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        /* kelompok bagian, baris bawah di dalam pill */
        .kelompok-overlay {
            position: absolute;
            top: 6.05cm;
            left: 0;
            width: 5.5cm;
            text-align: center;
            font-size: 7.5px;
            font-weight: 600;
            color: #3D2A08;
            letter-spacing: .2px;
        }
        .kelompok-overlay b {
            font-weight: 800;
        }
    </style>
</head>
<body>
    @foreach($karyawan->chunk(9) as $page)
        <table class="grid">
            @foreach($page->chunk(3) as $row)
                <tr>
                    @foreach($row as $k)
                        <td>
                            <div class="card">
                                <img class="card-bg" src="{{ public_path('images/card-bg-waj-3.png') }}">

                                <div class="qr-overlay">
                                    <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::format('svg')->size(200)->generate($k->nip)) }}">
                                </div>

                                <div class="nama-overlay">{{ strtoupper($k->nama) }}</div>
                                <div class="nip-overlay">NIP. {{ $k->nip }}</div>

                                @php
                                    $levelJabatan = trim((string) ($k->job_level ?? $k->job_title ?? 'STAFF'));
                                    if ($levelJabatan === '') {
                                        $levelJabatan = 'STAFF';
                                    }
                                @endphp
                                <div class="jabatan-overlay">{{ strtoupper($levelJabatan) }}</div>
                                <div class="kelompok-overlay">Bagian: <b>{{ strtoupper($k->bagian ?? 'UMUM') }}</b></div>
                            </div>
                        </td>
                    @endforeach
                    @for($i = $row->count(); $i < 3; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>