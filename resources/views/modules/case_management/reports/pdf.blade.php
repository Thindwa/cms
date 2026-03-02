<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .meta { color: #666; margin-bottom: 12px; }
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
