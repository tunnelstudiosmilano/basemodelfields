@extends('basemodelfields::master')

@section('content')


    <h1>Audit</h1>
    <hr>

    <table id="datatable" class="table table-bordered table-striped items-list display">
        {{--<thead>
        <tr>
            @if(array_key_exists('picture',$fields))
                <th>Picture</th>
            @endif
            @foreach($fields as $field => $values)
                @if(!isset($values['type']) /*|| $values['type'] != 'file'*/)
                    <th>{{ucfirst($field)}}</th>
                @endif

            @endforeach
            <th>Options</th>
        </tr>
        </thead>--}}
        <thead>
        <tr>
            <td>Event</td>
            <td>User</td>
            <td>Model</td>
            <td>Model ID</td>
            <td>Old</td>
            <td>New</td>
            <td>Url</td>
            <td>Date</td>
        </tr>
        </thead>
        @foreach($items as $item)
            {{--{{dd($item->old_values)}}--}}
            <tr>
                <td>{{$item->event}}</td>
                <td>{{$item->user->email}}</td>
                <td>{{$item->auditable_type}}</td>
                <td>{{$item->auditable_id}}</td>
                <td>{{json_encode($item->old_values)}}</td>
                <td>{{json_encode($item->new_values)}}</td>
                <td>{{$item->url}}</td>
                <td>{{$item->created_at}}</td>
                {{--<td>{{$item->updated_at}}</td>--}}
            </tr>

        @endforeach

    </table>

    {{--<div class="pagination"> {{ $items->links() }} </div>--}}
@endsection



