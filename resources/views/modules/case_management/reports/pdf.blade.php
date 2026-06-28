<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: landscape; margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2 { font-size: 14px; margin: 0 0 4px; }
        .meta { color: #666; margin-bottom: 10px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; text-align: left; word-break: break-word; }
        th { background: #f5f5f5; font-size: 10px; }
        td { font-size: 9px; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <p class="meta">Period: {{ $dateFrom }} to {{ $dateTo }}</p>
    <table>
        <thead>
            <tr>
                @foreach(array_keys(reset($rows) ?: []) as $th)
                    <th>{{ $th }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
