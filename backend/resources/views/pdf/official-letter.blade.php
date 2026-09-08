<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: {{ $font ?? 'Abhaya Libre' }}, 'iskoola pota', 'DejaVu Sans', sans-serif; font-size: 11.5pt; line-height: 1.8; color: #1e293b; }

        .letterhead { width: 100%; border-bottom: 2.5pt solid #1e293b; padding-bottom: 10pt; margin-bottom: 16pt; text-align: center; }
        .letterhead .repub { font-size: 14pt; font-weight: bold; }
        .letterhead .ministry { font-size: 13pt; font-weight: bold; margin-top: 2pt; }
        .letterhead .division { font-size: 10.5pt; margin-top: 2pt; }
        .letterhead .system { font-size: 8.5pt; color: #475569; margin-top: 3pt; }

        .reference-block { width: 100%; margin-bottom: 12pt; }
        .reference-block table { width: 100%; border-collapse: collapse; }
        .reference-block td { font-size: 11pt; padding: 1pt 0; vertical-align: top; }

        .to-block { margin: 14pt 0 8pt; }
        .subject { margin: 12pt 0 10pt; font-weight: bold; text-decoration: underline; }
        .body-content p { margin: 8pt 0; text-align: justify; }
        .body-content .num { font-weight: bold; }

        .cc-block { margin-top: 20pt; }
        .cc-block .cc-title { font-weight: bold; margin-bottom: 4pt; }

        .signature-block { margin-top: 30pt; }
        .sig { margin-bottom: 8pt; }
        .sig .sigline { height: 0; border-top: 1pt solid #1e293b; width: 220px; margin: 52pt 0 6pt; }
        .sig .name { font-weight: bold; }
        .sig .role { font-style: italic; }

        .footer { margin-top: 28pt; border-top: 0.5pt solid #cbd5e1; padding-top: 6pt; font-size: 8.5pt; color: #64748b; text-align: center; }
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
                <td width="60%">ප්‍රවෘත්ති අංකය: <strong>{{ $letter->ref_no ?? '—' }}</strong></td>
                <td width="40%" style="text-align:right;">දිනය: <strong>{{ $letter->letter_date?->format('d-m-Y') ?? now()->format('d-m-Y') }}</strong></td>
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
        @if ($letter->officer)
            <div>{{ $letter->officer->full_name_en }},</div>
            @if ($letter->officer->address_line1) <div>{{ $letter->officer->address_line1 }}</div> @endif
            @if ($letter->officer->address_line2) <div>{{ $letter->officer->address_line2 }}</div> @endif
            @if ($letter->officer->address_line3) <div>{{ $letter->officer->address_line3 }}</div> @endif
            <div>ජා.හැ. අංකය: {{ $letter->officer->nic_no }}</div>
        @endif
    </div>

    <div class="subject">මාතෘකාව: {{ $letter->subject ?? $letter->letterBatch?->document_type?->label() ?? 'ලිපිය' }}</div>

    <div class="body-content">
        @if ($letter->body)
            {!! preg_replace('/<script\b[^>]*>.*?<\/script>|<iframe\b[^>]*>.*?<\/iframe>/is', '', $letter->body) !!}
        @endif
    </div>

    @if (! empty($letter->cc_to) && is_array($letter->cc_to))
        <div class="cc-block">
            <div class="cc-title">පිටපත්:</div>
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
                    $sigPath = $letter->signatory->digital_signature_path;
                    $sigData = null;
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($sigPath)) {
                        $raw = \Illuminate\Support\Facades\Storage::disk('public')->get($sigPath);
                        $sigData = 'data:image/png;base64,' . base64_encode($raw);
                    }
                @endphp
                @if ($sigData)<img class="sigimg" src="{{ $sigData }}">@endif
            @endif
            <div class="name">{{ $letter->signatory?->officer_name ?? '' }}</div>
            <div class="role">{{ $letter->signatory?->designation ?? '' }}</div>
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
        පරිගණකගත ලේඛනයකි &middot; {{ $letter->ref_no ?? '' }}
    </div>
</body>
</html>
