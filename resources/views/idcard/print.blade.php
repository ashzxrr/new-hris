@php use SimpleSoftwareIO\QrCode\Facades\QrCode; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ID Card</title>
    <style>
        @page { margin: 8mm; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; }

        .page-break { page-break-after: always; }

        table.grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.grid td {
            width: 33.33%;
            padding: 6px;
            vertical-align: top;
            page-break-inside: avoid;
        }

        .card {
            border: 2px solid #F2C200;
            border-radius: 12px;
            text-align: center;
            box-sizing: border-box;
            overflow: hidden;
            background: #fff;
            page-break-inside: avoid;
        }
        .card-logo {
            padding: 10px 0 2px;
        }
        .card-logo img {
            height: 26px;
        }
        .card-company {
            font-size: 7px;
            font-style: italic;
            color: #B8960A;
            margin-bottom: 6px;
        }
        .qr-wrap {
            background: #FFF6D9;
            border-radius: 10px;
            padding: 6px;
            display: inline-block;
        }
        .qr-wrap img {
            width: 110px;
            height: 110px;
            display: block;
        }
        .card-body {
            padding: 6px 8px 10px;
        }
        .card-body .bagian {
            font-size: 7px;
            font-weight: 600;
            color: #B8960A;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .card-body .nama {
            font-size: 10px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.2;
        }
        .card-body .nip {
            font-size: 9px;
            color: #555;
            margin-top: 3px;
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
                                <div class="card-logo">
                                    <img src="{{ public_path('images/logo-waj.png') }}">
                                </div>
                                <div class="card-company">Walet Abdillah Jabli</div>
                                <div class="qr-wrap">
                                    <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::format('svg')->size(200)->generate($k->nip)) }}">
                                </div>
                                <div class="card-body">
                                    <div class="bagian">{{ $k->bagian ?? 'UMUM' }}</div>
                                    <div class="nama">{{ strtoupper($k->nama) }}</div>
                                    <div class="nip">NIP. {{ $k->nip }}</div>
                                </div>
                            </div>
                        </td>
                    @endforeach
                    {{-- Pad kolom jika kurang dari 3 --}}
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