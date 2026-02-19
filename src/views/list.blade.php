@extends('basemodelfields::master')

@section('content')


    <h1>{{ \Illuminate\Support\Str::plural($basemodelfields_section)}} @if($filters)
            <small> => Filters: @foreach($filters as $filter) <i
                        class="glyphicon glyphicon-filter"></i>{{$filter}} @endforeach </small> @endif</h1>
    <hr>

    <table id="datatable" class="table table-bordered table-striped items-list display">
        {{ csrf_field() }}
        <thead>
        <tr>
            @if(array_key_exists('picture',$fields))
                <th>Picture</th>
            @endif
            @foreach($fields as $field => $values)
                    @if(!isset($values['type']) || ($values['type'] != 'file' && $values['type'] != 'textarea'))
                    <th>{{ucfirst($field)}}</th>
                @endif

            @endforeach
            <th>Options</th>
        </tr>
        </thead>
        @foreach($items as $item)
            <tr>
                @if(array_key_exists('picture',$fields))
                    <td width="100" align="center">
                        @if ($item->picture)<img width="100"
                                                 src="{{BaseModelFields::getResourceAsset().'/'.$item->picture}}"/>
                        @else
                            No image
                        @endif

                    </td>
                @endif
                @foreach($fields as $field => $values)
                    @if(!isset($values['type']) || ($values['type'] != 'file' && $values['type'] != 'textarea'))
                        <td>{{ucfirst($item->$field)}}</td>
                    @endif

                @endforeach
                <td>
                    <div class="btn-group">
                        <button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">Options <span
                                    class="caret"></span></button>
                        <ul class="dropdown-menu">
                            <li><a href="{{URL::route(BasemodelFields::form_route(),['id'=>$item->id])}}"><i
                                            class="glyphicon glyphicon-pencil"></i> Edit</a></li>

                            <li class="delete"><a
                                        href="{{URL::route(BasemodelFields::delete_route(),['id'=>$item->id])}}"><i
                                            class="glyphicon glyphicon-trash"></i> Delete</a></li>
                            @if(isset($has_many) && !empty($has_many))
                                @foreach($has_many as $has_many_key => $has_many_foreign)
                                    <li><a
                                                href="{{route(BasemodelFields::get_route($has_many_key)).'?'.$has_many_foreign.'='.$item->id}}"><i
                                                    class="glyphicon glyphicon-list"></i> {{ucfirst($has_many_key)}}</a>
                                    </li>
                                @endforeach
                            @endif

                        </ul>
                    </div>
                </td>
            </tr>

        @endforeach

    </table>

    {{--<div class="pagination"> {{ $items->links() }} </div>--}}
@endsection



