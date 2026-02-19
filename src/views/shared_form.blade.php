@extends('basemodelfields::master')

@section('content')
    @include('basemodelfields::picture_crop_modal')
    @if($item)
        <h1>Edit {{$basemodelfields_section}}
        </h1>
    @else
        <h1>Add {{$basemodelfields_section}}</h1>
    @endif
    <hr>
    <form method="post" action="{{URL::route(BasemodelFields::post_route(),['id'=>($item) ? $item->id : 0])}}"
          class="main_form"
          enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        @if(isset($fields))
            @foreach($fields as $level => $rules)
                @if($level == 'main')
                    @foreach($rules as $key => $field)
                        @include('basemodelfields::form_input')
                    @endforeach
                @elseif($level == 'localized_fields')
                    <div style="clear: both; overflow: hidden" class="localized-fields">
                        @foreach(config('app.locales') as $l)
                            <div data-lang="{{$l}}" class="panel panel-default @if(count(config('app.locales')) > 1) col-md-6 @endif">
                                <div class="panel-heading">
                                    <b><i class="glyphicon glyphicon-flag"></i>Localization: {{strtoupper($l)}}</b>
                                </div>
                                <div class="panel-body">
                                    @foreach($rules as $key => $field)
                                        @include('basemodelfields::form_input')
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($level == 'belongsTo')
                    @foreach($rules as $key => $field)
                        <div class="form-group">
                            @include('basemodelfields::form_input',['list'=>$resources_list[$level][$key]])
                        </div>
                    @endforeach
                @elseif($level == 'hasManyAndBelongsToMany')
                    @foreach($rules as $key => $field)
                        <div class="form-group">
                            @include(
                              'basemodelfields::form_input',
                              [
                                'list'=>$resources_list[$level][$key],
                                'values'=>$resources_values[$level][$key]
                              ]
                            )
                        </div>
                    @endforeach
                @elseif($level == 'hasMany')
                    @foreach($rules as $key => $field)
                        <div class="form-group">
                            <?php
                            $file = true;
                            if (!isset($field['type']) || $field['type'] != 'file') {
                                $file = false;
                            }
                            ?>
                            @if($file || (!$file && $item))
                                <div class="panel panel-default @if(isset($field['type']) && $field['type']=='file') has-many-files @endif">
                                    <div class="panel-heading">
                                        <b>{{ucfirst(str_replace('_',' ',$key))}}</b>
                                    </div>
                                    <div class="panel-body">
                                        @include('basemodelfields::form_input',['list'=>$resources_list[$level][$key]])
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            @endforeach
        @endif
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>

    @yield('extend')
@endsection