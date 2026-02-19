<?php
$field_name = $key;
$is_array = false;
if ($level == 'hasMany' || $level == 'hasManyAndBelongsToMany') {
    $field_name .= '[]';
    $is_array = true;
} else if ($level == 'localized_fields') {
    $field_name .= '[' . $l . ']';
    $field_value = old($key . '.' . $l, ($item && isset($item->translate($l)->$key)) ? $item->translate($l)->$key : '');
} else if ($level == 'main' || $level == 'belongsTo') {
    $field_value = old($key, ($item) ? $item->$key : '');
}

$w = isset($field['width']) ? intval($field['width']) : '700';
$h = isset($field['height']) ? intval($field['height']) : '500';
/*not editable: disabled if already saved*/
$disabled = (isset($field['editable']) && !$field['editable'] && $field_value !== '') ? 'readonly' : '';
$field_label = (isset($field['label'])) ? ucfirst($field['label']) : str_replace(['_id', '_'], ' ', ucfirst($key));
$field_class = (isset($field['class'])) ? $field['class'] : '';
$field_icon = (isset($field['icon'])) ? $field['icon'] : 'glyphicon-font';
$required = (strpos($field['rules'], 'required') !== false) ? ' required ' : '';
$field_class .= $required;

$rules = explode('|', $field['rules']);
foreach ($rules as $k => $rule) {
    if (substr($rule, 0, 3) == 'in:') {
        $enum_values_s = str_replace('in:', '', $rule);
        $enum_values = explode(',', $enum_values_s);
    }
}

?>
<div class="form-group" style="clear: both;overflow: hidden">

    <label><i class="glyphicon {{$field_icon}}"></i> {{$field_label}} @if(!$required)
            <small>(optional)</small> @endif</label>

    @if($level == 'belongsTo')
        <select name="{{$key}}" class="form-control {{$field_class}}">
            <option value="">--Select--</option>
            @foreach($list as $c)
                <option @if($c->id == old($key,($item) ? $item->$key : '--')) selected
                        @endif value="{{$c->id}}">{{$c->code_name}}</option>
            @endforeach
        </select>
    @elseif($level == 'hasManyAndBelongsToMany')
        <div>
            @foreach($list as $c)
                <input @if(in_array($c->id,$values)) checked @endif  type="checkbox" name="{{$key}}[]"
                       value="{{$c->id}}" class="{{$field_class}}"> {{$c->code_name}}
            @endforeach
        </div>
    @elseif($level == 'hasMany')
        @if(isset($field['type']) && $field['type']=='file')
            <input type="file" name="{{$key}}[]" class="{{$field_class}}">
            @if($item)
                @if(count($list))
                    <b>File caricati</b><br><br>
                    @foreach($list as $pic)
                        <div>
                            <div style="float: left;margin-right: 10px">
                                <input type="checkbox" value="{{$pic->id}}" name="{{$key}}_del[]"> <i
                                        class="glyphicon glyphicon-trash"></i>
                            </div>
                            <div style="margin-right: 20px"><img
                                        src="{{BaseModelFields::getResourceAsset().'/'.$pic->code_name}}"
                                        width="50" alt=""/></div>
                        </div><br>
                    @endforeach
                @else
                    <br>
                    <div class="alert alert-warning">
                        <b>No files uploaded</b><br><br>
                    </div>

                @endif
            @endif
        @else
            {{--HANDLE NO FILE HAS MANY--}}
            <div>
                <ul>
                    @foreach($list as $c)
                        <li>
                            <a href="{{route(Basemodelfields::form_route($key),['id'=>$c->id])}}"
                               target="_blank">{{$c->code_name}}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    @else
        @if(isset($field['type']))
            @if($field['type'] == 'textarea')

                <textarea name="{{$field_name}}" class="form-control {{$field_class}}">{{$field_value}}</textarea>

            @elseif($field['type'] == 'file' && ((strpos($field['rules'], 'base64image') !== false)))

                @if($item)
                    @php $current = ($level == 'localized_fields') ? $item->translate($l) : $item  @endphp
                @endif

                <div class="cropper-preview-cont"  data-width="{{$w}}" data-height="{{$h}}">
                    <input class="cropper-image-data" name="{{$field_name}}" type="text" style="display: none" data-value="@if(isset($current->$key) && $current->$key){{BaseModelFields::getResourceAsset().'/'.$current->$key}}@endif" value=""/>

                    <div class="cropper-input-btn">
                        <input type="file" name="{{$field_name}}_data"/>
                    </div>
                    <div class="cropper-preview">
                    </div>
                    <br>
                </div>

            @elseif($field['type'] == 'file' && ((strpos($field['rules'], 'image') !== false)))

                @if($item)
                    @php $current = ($level == 'localized_fields') ? $item->translate($l) : $item  @endphp
                    @if(isset($current->$key) && $current->$key)
                        <div style="float: left;margin-right: 20px"><img
                                    src="{{BaseModelFields::getResourceAsset().'/'.$current->$key}}" width="50"
                                    alt=""/></div>
                    @endif
                @endif
                    <input type="file" name="{{$field_name}}" class="{{$field_class}}">

            @else
                @if($item)
                    @php $current = ($level == 'localized_fields') ? $item->translate($l) : $item  @endphp
                    @if(isset($current->$key) && $current->$key)
                    - <a href="{{BaseModelFields::getResourceAsset('FILE').'/'.$current->$key}}"
                                               target="_blank"><u style="color: green">Already uploaded =&gt;
                        open</u></a>
                    @endif
                @endif
                    <input type="file" name="{{$field_name}}" class="{{$field_class}}">
            @endif
        @else
            @if(isset($enum_values))
                <select name="{{$field_name}}" class="form-control {{$field_class}}" {{$disabled}}>
                    <option value="">--Select--</option>
                    @foreach($enum_values as $c)
                        <option @if($c == $field_value) selected
                                @endif value="{{$c}}">{{$c}}</option>
                    @endforeach
                </select>
            @else
                <input type="text" name="{{$field_name}}" class="form-control {{$field_class}}" {{$disabled}}
                       value="{{$field_value}}">
            @endif
        @endif

    @endif
</div>
