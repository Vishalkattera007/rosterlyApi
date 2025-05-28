<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        th, td { border: 1px solid #999; padding: 4px; word-wrap: break-word; text-align: center; }
        th { background: #f0f0f0; }
        .name-col { text-align: left; font-weight: bold; }
    </style>
</head>
<body>
    
    <h3>Roster for {{ $location }} | {{ $startDate }} - {{ $endDate }}</h3>
    <p>Created By {{ $createdBy }}</p>
    <p>Generated {{ $currentDate }}</p>


    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                @foreach($dates as $date)
                    <th>{{ $date }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($roster as $row)
                <tr>
                    <td class="name-col">{{ $row['name'] }}</td>
                    @foreach($row['shifts'] as $shift)
                        <td>{!! $shift !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
