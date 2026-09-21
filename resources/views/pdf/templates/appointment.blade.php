<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: '{{ $font ?? "Iskoola Pota" }}', 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.75;
            color: #111;
        }

        .letterhead { width: 100%; text-align: center; padding-bottom: 8pt; border-bottom: 2pt solid #000; margin-bottom: 14pt; }
        .letterhead .repub { font-size: 13pt; font-weight: bold; letter-spacing: 0.5pt; }
        .letterhead .ministry { font-size: 12.5pt; font-weight: bold; margin-top: 1pt; }
        .letterhead .division { font-size: 10pt; margin-top: 1pt; }
        .letterhead .system { font-size: 8pt; color: #444; margin-top: 2pt; }

        .ref-table { width: 100%; margin-bottom: 10pt; }
        .ref-table td { font-size: 10pt; vertical-align: top; padding: 1pt 0; }

        .to-block { margin: 12pt 0 6pt; font-size: 10.5pt; }
        .to-block .label { font-weight: bold; }

        .subject { margin: 12pt 0; font-weight: bold; font-size: 11pt; text-decoration: underline; }

        .body-content { margin: 8pt 0; }
        .body-content p { margin: 6pt 0; text-align: justify; }
        .body-content .para-num { font-weight: bold; }

        .signature-block { margin-top: 28pt; page-break-inside: avoid; }
        .sig { margin-bottom: 6pt; }
        .sig .sigline { border-top: 1pt solid #000; width: 200px; margin: 48pt 0 4pt; }
        .sig .name { font-weight: bold; }
        .sig .role { font-style: italic; font-size: 10pt; }

        .cc-block { margin-top: 20pt; border-top: 0.5pt solid #ccc; padding-top: 6pt; }
        .cc-block .cc-title { font-weight: bold; font-size: 10pt; margin-bottom: 3pt; }
        .cc-block .cc-line { font-size: 9.5pt; margin: 1pt 0; }

        .footer { margin-top: 24pt; border-top: 0.5pt solid #ccc; padding-top: 4pt; font-size: 8pt; color: #666; text-align: center; }
        .sigimg { height: 42pt; display: block; margin-bottom: 2pt; }
    </style>
</head>
<body>
    <div class="letterhead">
        <div class="repub">ශ්‍රී ලංකා ප්‍රජාතන්ත්‍රවාදී සමාජවාදී ජනරජය</div>
        <div class="ministry">රාජ්‍ය පරිපාලන, පළාත් සභා හා පළාත් පාලන අමාත්‍යාංශය</div>
        <div class="division">ස්වදේශ කටයුතු අංශය - ග්‍රාම නිලධාරී පාලන අංශය</div>
        <div class="system">මෙරට ග්‍රාම නිලධාරි කළමනාකරණ පද්ධතිය</div>
    </div>

    <table class="ref-table">
        <tr>
            <td width="60%">මාගේ අංකය: <strong>{{ $refNo ?? '' }}</strong></td>
            <td width="40%" style="text-align:right;">දිනය: <strong>{{ $letterDate ?? '' }}</strong></td>
        </tr>
    </table>

    @if (empty($addressLines))
        <div class="to-block">
            <div>{{ $officerName ?? '' }},</div>
            <div>ජා.හැ. අංකය: {{ $nicNo ?? '' }}</div>
        </div>
    @else
        <div class="to-block">
            <div>{{ $officerName ?? '' }},</div>
            @foreach ($addressLines as $line)
                <div>{{ $line }}</div>
            @endforeach
            <div>ජා.හැ. අංකය: {{ $nicNo ?? '' }}</div>
        </div>
    @endif

    <div class="subject">මාතෘකාව: ග්‍රාම නිලධාරී (III ශ්‍රේණිය) තනතුරට පත් කිරීම</div>

    <div class="body-content">
        <p>{{ $officerName ?? '' }} {{ $officerSalutation ?? '' }},</p>

        <p><span class="para-num">01.</span> ඔබව අමාත්‍ය මණ්ඩලයේ අනුමැතිය ලැබ @if (!empty($examDate)){{ $examDate }} දින @endif පැවති ප්‍රාදේශීය ලේකම් පරීක්ෂණයේ සමත්වීම මත {{ $dsDivisionSi ?? '' }} ප්‍රාදේශීය ලේකම් කොට්ඨාසයේ {{ $gnDivision ?? '' }} ග්‍රාම නිලධාරී වසම සඳහා ග්‍රාම නිලධාරී (III ශ්‍රේණිය) තනතුරට {{ $letterDate ?? '' }} දින සිට බලපැවැත්වෙන පරිදි පත් කර ඇත.</p>

        <p><span class="para-num">02.</span> ඔබගේ පත්කිරීම @if (!empty($probationEffectiveDate)){{ $probationEffectiveDate }} දින සිට @endif වසරක පරිවාස කාලයකට යටත් වන අතර, පරිවාස කාලය තෘප්තිමත් ලෙස නිම කිරීමෙන් අනතුරුව ඔබව ස්ථිර කෙරේ.</p>

        <p><span class="para-num">03.</span> ඒ අනුව, {{ $dsDivisionSi ?? '' }} ප්‍රාදේශීය ලේකම්වරයා වෙත වාර්තා කර, උපදෙස් මත රාජකාරි භාරගනු මැනවි.</p>

        <p><span class="para-num">04.</span> ග්‍රාම නිලධාරී සේවයේ නීති හා විධිවිධාන, රාජ්‍ය සේවා විධිවිධාන හා වරින් වර නිකුත් කරන චක්‍රලේඛ අනුව ඔබේ රාජකාරි ඉටු කළ යුතුය.</p>

        @if (!empty($cabinetAppNo))
            <p><span class="para-num">05.</span> අමාත්‍ය මණ්ඩල අනුමැතිය — {{ $cabinetAppNo }} (දින: {{ $cabinetAppDate ?? '' }}).</p>
        @endif
    </div>

    <div class="signature-block">
        <div class="sig">
            <div>ස්වදේශ කටයුතු අමාත්‍යාංශයේ ලේකම් වෙනුවෙන්,</div>
            <div class="sigline"></div>
            @if (!empty($signatureDataUri))<img class="sigimg" src="{{ $signatureDataUri }}">@endif
            <div class="name">{{ $signatoryName ?? '' }}</div>
            <div class="role">{{ $signatoryDesignation ?? '' }}</div>
        </div>

        @if (!empty($controllingOfficerName))
            <div class="sig">
                <div>{{ $controllingOfficerDesignation ?? '' }} ලෙස අනුමත කරමින්,</div>
                <div class="sigline"></div>
                <div class="name">{{ $controllingOfficerName }}</div>
                <div class="role">{{ $controllingOfficerDesignation }}</div>
            </div>
        @endif
    </div>

    @if (! empty($ccLines) && is_array($ccLines))
        <div class="cc-block">
            <div class="cc-title">පිටපත්:</div>
            @foreach ($ccLines as $cc)
                <div class="cc-line">{{ $cc }}</div>
            @endforeach
        </div>
    @endif

    <div class="footer">පරිගණකගත ලේඛනයකි &middot; {{ $refNo ?? '' }}</div>
</body>
</html>