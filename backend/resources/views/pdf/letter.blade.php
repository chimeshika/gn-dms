<!DOCTYPE html>
<html lang="{{ $letter->officer?->medium?->value ?? 'si' }}">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {{ $font }}, 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.7;
            color: #1e293b;
        }

        .letterhead {
            width: 100%;
            border-bottom: 2.5pt solid #1e293b;
            padding-bottom: 10pt;
            margin-bottom: 16pt;
            text-align: center;
        }

        .letterhead .repub { font-size: 14pt; font-weight: bold; }
        .letterhead .ministry { font-size: 13pt; font-weight: bold; margin-top: 2pt; }
        .letterhead .division { font-size: 10.5pt; margin-top: 2pt; }
        .letterhead .system { font-size: 8.5pt; color: #475569; margin-top: 3pt; }

        .reference-block {
            margin-bottom: 12pt;
        }

        .reference-block table {
            width: 100%;
            border-collapse: collapse;
        }

        .reference-block td {
            font-size: 10.5pt;
            padding: 1pt 0;
            vertical-align: top;
        }

        .to-block {
            margin: 12pt 0;
        }

        .subject {
            margin: 12pt 0;
            font-weight: bold;
            text-decoration: underline;
        }

        .body-content {
            margin: 10pt 0;
        }

        .body-content p {
            margin: 6pt 0;
            text-align: justify;
        }

        .cc-block {
            margin-top: 20pt;
        }

        .cc-block .cc-title {
            font-weight: bold;
            margin-bottom: 4pt;
        }

        .signature-block {
            margin-top: 30pt;
        }

        .sig {
            margin-bottom: 8pt;
        }

        .sig .sigline {
            height: 0;
            border-top: 1pt solid #1e293b;
            width: 220px;
            margin: 48pt 0 6pt;
        }

        .sig .name {
            font-weight: bold;
        }

        .sig .role {
            font-style: italic;
        }

        .footer {
            margin-top: 28pt;
            border-top: 0.5pt solid #cbd5e1;
            padding-top: 6pt;
            font-size: 8.5pt;
            color: #64748b;
            text-align: center;
        }

        .sigimg { height: 46pt; display: block; margin-bottom: 2pt; }
    </style>
</head>
<body>
    <div class="letterhead">
        <div class="repub">ශ්‍රී ලංකා ප්‍රජාතන්ත්‍රවාදී සමාජවාදී ජනරජය</div>
        <div class="ministry">ස්වදේශ කටයුතු අමාත්‍යාංශය</div>
        <div class="division">ග්‍රාම නිලධාරී පාලන අංශය</div>
        <div class="system">මෙරට ග්‍රාම නිලධාරි කළමනාකරණ පද්ධතිය</div>
    </div>

    <div class="reference-block">
        <table>
            <tr>
                <td width="60%">ප්‍රවෘත්ති අංකය: <strong>{{ $letter->ref_no }}</strong></td>
                <td width="40%" style="text-align:right;">දිනය: <strong>{{ $letter->letterBatch?->letter_date?->format('d-m-Y') ?? now()->format('d-m-Y') }}</strong></td>
            </tr>
        </table>
    </div>

    @if ($letter->signatory)
        <div class="reference-block">
            {{ $letter->signatory->designation }}<br>
            @if ($letter->signatory->dsDivision)
                Divisional Secretariat, {{ $letter->signatory->dsDivision->name_en }}<br>
            @elseif ($letter->signatory->district)
                District Secretariat, {{ $letter->signatory->district->name_en }}<br>
            @else
                Ministry of Home Affairs<br>
            @endif
        </div>
    @endif

    <div class="to-block">
        <strong>To,</strong><br>
        @if ($letter->officer)
            {{ $letter->officer->full_name_en }}<br>
            @if ($letter->officer->full_name_si) {{ $letter->officer->full_name_si }}<br> @endif
            @if ($letter->officer->full_name_ta) {{ $letter->officer->full_name_ta }}<br> @endif
            NIC No: {{ $letter->officer->nic_no }}<br>
            @if ($letter->officer->address_line1) {{ $letter->officer->address_line1 }}<br> @endif
            @if ($letter->officer->address_line2) {{ $letter->officer->address_line2 }}<br> @endif
            @if ($letter->officer->address_line3) {{ $letter->officer->address_line3 }} @endif
        @endif
    </div>

    <div class="subject">
        Subject: {{ $letter->subject }}
    </div>

    <div class="body-content">
        {!! preg_replace('/<script\b[^>]*>.*?<\/script>|<iframe\b[^>]*>.*?<\/iframe>/is', '', (string) $letter->body) !!}
    </div>

    @if (! empty($letter->cc_to))
        <div class="cc-block">
            <div class="cc-title">Copies to:</div>
            @foreach ($letter->cc_to as $cc)
                <div>{{ $cc }}</div>
            @endforeach
        </div>
    @endif

    <div class="signature-block">
        <div class="sig">
            <div>ස්වදේශ කටයුතු අමාත්‍යාංශයේ ලේකම් වෙනුවෙන්,</div>
            <div class="sigline"></div>
            @if ($letter->signatory && $letter->signatory->digital_signature_path)
                @php
                    $sig = \Illuminate\Support\Facades\Storage::disk('public')->get($letter->signatory->digital_signature_path);
                    $sigData = $sig ? 'data:image/png;base64,'.base64_encode($sig) : null;
                @endphp
                @if ($sigData)<img class="sigimg" src="{{ $sigData }}">@endif
            @endif
            <div class="name">{{ $letter->signatory?->officer_name }}</div>
            <div class="role">{{ $letter->signatory?->designation }}</div>
        </div>
        @if ($letter->controllingOfficer)
            <div class="sig">
                <div>{{ $letter->controllingOfficer->designation }} ලෙස අනුමත කරමින්,</div>
                <div class="sigline"></div>
                <div class="name">{{ $letter->controllingOfficer->officer_name }}</div>
                <div class="role">{{ $letter->controllingOfficer->designation }}</div>
            </div>
        @endif
    </div>

    <div class="footer">
        Computer-generated document &middot; {{ $letter->ref_no }}
    </div>
</body>
</html>
