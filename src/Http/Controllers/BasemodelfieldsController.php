<?php

namespace Mardok9185\Basemodelfields\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Validator;

class BasemodelfieldsController extends Controller
{
    /**
     * model
     * @var string
     */
    protected $model;

    protected $config = null;

    /**
     * BaseModelFieldsController constructor.
     */
    public function __construct()
    {
        $this->config = App::make('config');
        $route_prefix = $this->config->get('basemodelfields.route_prefix');
        $section = str_replace([$route_prefix . '.', '.post', '.form', '.delete'], '', Route::currentRouteName());

        $controller = str_replace('Controller', '', (new \ReflectionClass($this))->getShortName());
        if ($controller != 'Basemodelfields') {
            $this->model = $model = 'App\\Models\\' . Str::singular(ucfirst($controller));
        } else {
            $route_section = Str::camel($section);
            $this->model = $model = 'App\\Models\\' . Str::singular(ucfirst($route_section));
        }

        $model::checkIsInit();

        view()->share('basemodelfields_section', ucfirst(str_replace('_', ' ', Str::singular($section))));
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $model = $this->model;
        $query = $model::whereNotNull('id');
        $filters = [];

        if (isset($model::$fields['belongsTo'])) {
            foreach ($model::$fields['belongsTo'] as $parent => $fields) {
                if (Input::has($parent)) {
                    $filters[] = $parent;
                    $query = $query->where($parent, Input::get($parent));
                }
            }
        }

        if (method_exists($model, 'translate')) {
            $items = $query->withTranslation()->get();
        } else {
            $items = $query->get();
        }
        //$items = $model::getIndexList();
        $with = isset($model::$fields['list']['with']) ? $model::$fields['list']['with'] : null;
        if ($with) {
            $items->load($with);
            $items->transform(function ($item) use ($with) {
                if($item->$with) $item->code_name = $item->$with->code_name . ' > ' . $item->code_name;
                return $item;
            });
        }

        //AGGIUNGE BOTTONE A LIST.BLADE CON CATEGORIA HAS MANY SE NON SONO FILE
        $has_many = [];
        if (isset($model::$fields['hasMany'])) {
            foreach ($model::$fields['hasMany'] as $key => $values) {
                if ((!isset($values['type']) || $values['type'] != 'file') && isset($values['foreign'])) {
                    $has_many[$key] = $values['foreign'];
                }
            }
        }
        $fields = $model::$fields['main'];

        return view('basemodelfields::list', compact('items', 'has_many', 'filters', 'fields'));
    }

    /**
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function form($id = 0)
    {
        $model = $this->model;
        $item = (!$id) ? null : $model::findOrFail($id);
        if ($item && method_exists($model, 'translate')) {
            $item->load('translations');
        }
        $view = (!isset($model::$form_view)) ? 'basemodelfields::shared_form' : $model::$form_view;
        $fields_resources = $this->getFieldsResources($item, $model::$fields);
        $old_or_stored_values = $this->getOldOrStoredValues($item, $model::$fields);
        return view($view, [
            'item' => $item,
            'fields' => $model::$fields,
            'resource_dir' => $model::getResourceDir(),
            'resources_list' => $fields_resources,
            'resources_values' => $old_or_stored_values
        ]);
    }

    /**
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $model = $this->model;
        $item = $model::findOrFail($id);
        //delete from DB and Cache App\Models\BaseModelFields->delete()
        $item->delete();
        return ["success" => true];
    }


    /**
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store($id = 0, Request $request)
    {
        $model = $this->model;
        $item = (!$id) ? new $model() : $model::findOrFail($id);
        $rules = $model::getValidatorRules();


        \Illuminate\Support\Facades\Validator::extend('base64image', function ($attribute, $value, $parameters, $validator) {
            if(!$value) return true;
            $explode = explode(',', $value);
            $format = str_replace(['data:image/', ';', 'base64',], ['', '', '',], $explode[0]);
            // check base64 format
            if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $explode[1])) {
                return false;
            }
            foreach ($parameters as $p){
                $p = explode('=',$p);
                $rule = $p[0];
                $conditions = $p[1];

                if($rule == 'mime'){
                    // check file format
                    $allowed_mimes = explode('/',$conditions);
                    if (!in_array($format, $allowed_mimes)) {
                        return false;
                    }
                }
                /*more rules
                 * else if($rule == ''){

                }*/
            }



            return true;
        });





        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        $item->saveFromRequest($request);
        DB::commit();

        if (isset($model::$redirectToRouteAfterStore)) {
            if ($model::$redirectToRouteAfterStore === 'back') {
                return redirect()->back()->with('success', 'true');
            }
            return redirect()->route($model::$redirectToRouteAfterStore)->with('success', 'true');
        }

        $route = Route::currentRouteName();
        $route = str_replace('post', 'form', $route);

        return redirect()->route($route, ['id' => $item->id])->with('success', 'true');
    }

    public function getOldOrStoredValues($item = null, $fields = [])
    {
        $values = array();
        foreach ($fields as $level => $rules) {
            if ($level == 'hasManyAndBelongsToMany') {
                foreach ($rules as $key => $field) {
                    $values[$level][$key] = [];
                    $old = old($key);
                    if (is_array($old)) {
                        $values[$level][$key] = $old;
                    } else if ($item) {
                        $function = Str::plural(str_replace('_id', '', $key));
                        $stored_values = $item->$function()->get();
                        $stored_values->transform(function ($item) {
                            return $item->id;
                        });

                        $values[$level][$key] = $stored_values->toArray();
                    }
                }
            }
        }

        return $values;
    }

    public function getFieldsResources($item = null, $fields = [])
    {
        $fields_resources = [];
        $list = null;
        foreach ($fields as $level => $rules) {
            foreach ($rules as $key => $field) {
                $fields_resources[$level][$key] = [];

                if ($level == 'belongsTo' || $level == 'hasManyAndBelongsToMany') {

                    ////INIT QUERY////
                    $list = $field['model']::whereNotNull('id');

                    /////ORDERBY//////
                    if (isset($field['orderby']) && is_array($field['orderby'])) {
                        foreach ($field['orderby'] as $column => $direction) {
                            $list->orderBy($column, $direction);
                        }
                    } else {
                        $list = $list->orderBy('id');
                    }

                    /////WHERE CONDITION//////
                    if (isset($field['where']) && is_array($field['where'])) {
                        $route_params = Route::current()->parameters();
                        foreach ($field['where'] as $column => $where) {
                            if (!isset($where['value']) && isset($route_params[$column])) {
                                $list = $list->where($column, $where['condition'], $route_params[$column]);
                            } else if (isset($where['value'])) {
                                $list = $list->where($column, $where['condition'], $where['value']);
                            }
                        }
                    }

                    /////RELATED WHIT/////
                    if (isset($field['with']) && is_array($field['with'])) {
                        foreach ($field['with'] as $related) {
                            $list->with($related);
                        }

                    }

                    $fields_resources[$level][$key] = $list = $list->get();

                    if (isset($field['with']) && is_array($field['with'])) {
                        $list->transform(function ($item) use ($field) {
                            $name = '';
                            foreach ($field['with'] as $related) {
                                $name .= $item->$related->code_name . ' - ';
                            }
                            $item->code_name = $name . $item->code_name;
                            /*if(isset($field['append'])){

                                $append = $field['append'];
                                $item->code_name = $item->code_name. ' - '.$item->$append;
                            }*/
                            return $item;
                        });
                    }

                } else if ($level == 'hasMany') {

                    if ($item) {
                        $fields_resources[$level][$key] = $item->$key;
                    }

                }

            }
        }
        //dd($fields_resources);
        return $fields_resources;
    }
}
