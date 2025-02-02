@if (!empty($dataTotal) && count($dataTotal))
<table class="table box table-bordered" id="gp247_showTotal">
    @foreach ($dataTotal as $key => $element)
        @if ($element['code']=='total')
            <tr class="gp247_showTotal" style="background:#f5f3f3;font-weight: bold;">
                <th>{!! $element['title'] !!}</th>
                <td style="text-align: right" id="{{ $element['code'] }}">
                    {{$element['text'] }}
                </td>
            </tr>
        @elseif($element['value'] !=0)
            <tr class="gp247_showTotal">
                <th>{!! $element['title'] !!}</th>
                <td style="text-align: right" id="{{ $element['code'] }}">
                    {{$element['text'] }}
                </td>
            </tr>
        @endif
    @endforeach
</table>
@endif
