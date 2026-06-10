<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .report-meta { margin-bottom: 16px; padding: 10px 12px; background: #E8F5E9; border: 1px solid #A5D6A7; border-radius: 4px; }
        .report-meta p { margin: 4px 0; }
        .report-meta strong { color: #1B5E20; }
        h2 { color: #2E7D32; margin: 0 0 8px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #2E7D32; color: #fff; font-weight: bold; }
        tr:nth-child(even) td { background: #f9f9f9; }
    </style>
</head>
<body>
<div class="report-meta">
    <h2>{{ $reportTitle }}</h2>
    <p><strong>Период отчёта:</strong> {{ $periodLabel }}</p>
    <p><strong>Сформировал:</strong> {{ $authorName }}</p>
    <p><strong>Дата и время формирования:</strong> {{ $generatedAt }}</p>
</div>
<table>
    <thead>
    <tr>
        <th>Название</th>
        <th>Остаток</th>
        <th>Цена</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($products as $p)
        <tr>
            <td>{{ $p->name }}</td>
            <td>{{ $p->quantity }}</td>
            <td>{{ $p->price }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
