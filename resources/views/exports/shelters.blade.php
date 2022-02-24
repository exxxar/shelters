<table>
    <thead>
    <tr>
        <th>#</th>
        <th style="width:150px;">Город</th>
        <th style="width:150px;">Регион</th>
        <th style="width:250px;">Адрес</th>
        <th style="width:300px;">У кого на балансе</th>
        <th style="width:300px;">Ответственная персона</th>
        <th style="width:200px;">Описание</th>
    </tr>
    </thead>
    <tbody>
    @foreach($shelters as $shelter)
        <tr>
            <td>{{ $shelter->id }}</td>
            <td style="width:150px;">{{ $shelter->city }}</td>
            <td style="width:150px;">{{ $shelter->region }}</td>
            <td style="width:250px;">{{ $shelter->address }}</td>
            <td style="width:300px;">{{ $shelter->balance_holder }}</td>
            <td style="width:300px;">{{ $shelter->responsible_person }}</td>
            <td style="width:200px;">{{ $shelter->description }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
