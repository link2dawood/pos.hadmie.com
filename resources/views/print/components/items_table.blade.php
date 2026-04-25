<section class="print-section">
    <table class="print-table">
        <thead>
            <tr>
                @foreach($document['items']['columns'] as $column)
                    <th class="@if(($column['align'] ?? 'left') === 'right') print-table__cell--right @elseif(($column['align'] ?? 'left') === 'center') print-table__cell--center @endif">
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($document['items']['rows'] as $row)
                <tr>
                    @foreach($document['items']['columns'] as $column)
                        <td class="@if(($column['align'] ?? 'left') === 'right') print-table__cell--right @elseif(($column['align'] ?? 'left') === 'center') print-table__cell--center @endif">
                            @if($column['key'] === 'item')
                                <div class="print-table__item">{{ $row['item'] }}</div>
                                @if(!empty($row['description_lines']))
                                    <div class="print-table__details">
                                        @foreach($row['description_lines'] as $line)
                                            <div>{!! $line !!}</div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                {{ $row[$column['key']] ?? '' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($document['items']['columns']) }}">{{ $document['items']['empty_state'] }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
