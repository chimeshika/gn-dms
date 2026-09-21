<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <title>Batch Letters Print</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Sinhala', 'iskoola pota', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 15mm 20mm 15mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }

        /* Letterhead Container */
        .letter-head {
            width: 100%;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .letter-head img {
            max-width: 100%;
            height: auto;
            max-height: 120px; /* Letterhead image height */
        }

        .letter-content {
            font-size: 14px;
            line-height: 1.6;
        }

        /* Print Settings */
        @media print {
            body {
                background: none;
            }
            .page {
                margin: 0;
                box-shadow: none;
                page-break-after: always; /* සාර්ථකව ඊළඟ පිටුවට ලිපිය වෙන් කිරීම */
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar (Print Button) -->
    <div class="no-print" style="text-align: center; padding: 15px; background: #333;">
        <button onclick="window.print()" style="padding: 10px 25px; font-size: 16px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px;">
            🖨️ සියලුම ලිපි මුද්‍රණය කරන්න (Print All)
        </button>
    </div>

    <!-- Loop through all letters in the batch -->
    @foreach($letters as $letter)
        <div class="page">
            <!-- Letterhead Section -->
            <div class="letter-head">
                @if(!empty($letterhead_image))
                    <img src="{{ asset('storage/' . $letterhead_image) }}" alt="Letterhead">
                @else
                    <!-- Default Text Header if no image -->
                    <h2>රාජ්‍ය පරිපාලන, පළාත් සභා හා පළාත් පාලන අමාත්‍යාංශය</h2>
                    <p>ස්වදේශ කටයුතු අංශය</p>
                @endif
            </div>

            <!-- Letter Body -->
            <div class="letter-content">
                {!! $letter->formatted_body !!}
            </div>
        </div>
    @endforeach

</body>
</html>