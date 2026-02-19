<?php

namespace Mardok9185\Basemodelfields\Models;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use \Mardok9185\Basemodelfields\Exception\BasemodelfieldsInitException;


trait Basemodelfields
{
    public static $fileds = array();
    public static $valid_types = array('main', 'localized_fields', 'belongsTo', 'hasManyAndBelongsToMany', 'hasMany');

    /**
     * @return array
     */
    private function getPicturesFields()
    {
        $rules = self::getValidatorRules();
        $pictures = array();
        foreach ($rules as $key => $rules) {
            if ((strpos($rules, 'image') !== false)) {
                $pictures[$key] = $this->$key;
            }
        }
        return $pictures;
    }

    /**
     * @return array|bool
     */
    public static function getValidatorRules()
    {
        $rules = array();
        foreach (self::$fields as $k_type => $type) {
            if (in_array($k_type, self::$valid_types)) {
                foreach ($type as $key => $v) {
                    if (!isset($v['rules'])) {
                        return false;
                    }
                    if ($k_type == 'localized_fields') {
                        foreach (config('app.locales') as $l) {
                            $rules[$key . '.' . $l] = $v['rules'];
                        }
                    } else if ($k_type == 'hasManyAndBelongsToMany' || $k_type == 'hasMany') {
                        $rules[$key . '.*'] = $v['rules'];
                    } else {
                        $rules[$key] = $v['rules'];
                    }
                }
            }
        }
        return $rules;
    }

    /**
     * @param Request $request
     */
    private function delHasManyFromRequest(Request $request)
    {
        if (isset(self::$fields['hasMany'])) {
            foreach (self::$fields['hasMany'] as $key => $v) {
                if (isset($v['model'])) {
                    $model = $v['model'];
                    $post_items = ($request->has($key . '_del') && is_array($request->input($key . '_del'))) ? $request->input($key . '_del') : array();
                    $is_file = (isset($v['type']) && $v['type'] == 'file') ? true : false;

                    foreach ($post_items as $id) {
                        $id = intval($id);
                        $item = $model::findOrFail($id);
                        if ($is_file) {
                            if (strpos($v['rules'], 'image') !== false) {
                                $this->deleteImage($item->code_name);
                                $this->deleteImage('_thumb_' . $item->code_name);
                            } else {
                                $this->deleteFile($item->code_name);
                            }
                        }
                        $item->delete();
                    }
                }
            }
        }
    }


    private function delAllHasManyFiles()
    {
        if (isset(self::$fields['hasMany'])) {
            foreach (self::$fields['hasMany'] as $key => $v) {
                if (isset($v['type']) && $v['type'] == 'file') {
                    foreach ($this->$key as $f) {
                        if (strpos($v['rules'], 'image') !== false) {
                            $this->deleteImage($f->code_name);
                            $this->deleteImage('thumb_' . $f->code_name);
                        } else {
                            $this->deleteFile($f->code_name);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Request $request
     */
    private function mapHasManyFromRequest(Request $request)
    {
        if (isset(self::$fields['hasMany'])) {
            foreach (self::$fields['hasMany'] as $key => $v) {
                if (isset($v['model'])) {
                    $function = Str::plural(str_replace('_id', '', $key));
                    $model = $v['model'];
                    $items = array();
                    if (isset($v['type'])) {
                        if ($v['type'] == 'file') {
                            $post_items = ($request->hasFile($key) && is_array($request->file($key))) ? $request->file($key) : array();
                            foreach ($post_items as $file) {
                                if ($file->isValid()) {
                                    $item = new $model();
                                    $img = $this->storeImage($file, $v);
                                    $item->code_name = $img;
                                    $items[] = $item;
                                }
                            }
                        } else {
                            //HANDLE NO FILE HAS MANY
                            //$post_items = ($request->has($key) && is_array($request->input($key))) ? $request->input($key) : array();
                        }

                        $this->$function()->saveMany($items);
                    }
                }
            }
        }
    }

    /**
     * @param Request $request
     */
    private function mapHasManyAndBelongToManyFromRequest(Request $request)
    {
        if (isset(self::$fields['hasManyAndBelongsToMany'])) {
            foreach (self::$fields['hasManyAndBelongsToMany'] as $key => $model) {
                $function = Str::plural(str_replace('_id', '', $key));
                $values = ($request->has($key) && is_array($request->input($key))) ? $request->input($key) : array();
                $this->$function()->sync($values);
            }
        }
    }

    /**
     * @param Request $request
     */
    private function mapFieldsFromRequest(Request $request)
    {
        $slug = isset(self::$fields['slug']['from']);
        $localized_slug = ($slug && isset(self::$fields['slug']['localized']) && self::$fields['slug']['localized']) ? true : false;

        if (isset(self::$fields['main'])) {
            foreach (self::$fields['main'] as $key => $v) {
                if (!isset($v['type']) || $v['type'] == 'textarea') {
                    if($key != 'slug') $this->$key = $request->input($key);
                    if (($slug && !$localized_slug && $key == self::$fields['slug']['from']) || $key == 'slug') {
                        $slug = Str::slug(strip_tags($request->input($key)));
                        if(!empty($slug)) $this->slug = $slug;
                    }
                }
                if (isset($v['type'])) {
                    $this->handleFileUpload($v, $request, $key);
                }
            }
        }

        if (isset(self::$fields['localized_fields'])) {
            foreach (config('app.locales') as $l) {
                $translation = $this->translateOrNew($l);
                foreach (self::$fields['localized_fields'] as $key => $v) {

                    if (!isset($v['type']) || $v['type'] == 'textarea') {
                        if($key != 'slug') $translation->$key = $request->input($key . '.' . $l);
                    }
                    if (($localized_slug && $key == self::$fields['slug']['from']) || $key == 'slug') {
                        $slug = Str::slug(strip_tags($request->input($key . '.' . $l)));
                        if(!empty($slug)) $translation->slug = $slug;

                    }
                    if($key == 'tag'){
                        $translation->$key = Str::slug(strip_tags($request->input($key . '.' . $l)));;
                    }

                    if (isset($v['type'])) {
                        $this->handleFileUpload($v, $request, $key, $l);
                    }
                }
            }
        }

        if (isset(self::$fields['belongsTo'])) {
            foreach (self::$fields['belongsTo'] as $key => $model) {
                if ($request->has($key)) $this->$key = intval($request->input($key));
            }
        }

    }


    public function saveFromRequest($request)
    {
        $this->mapFieldsFromRequest($request);
        $this->save();
        $this->mapHasManyAndBelongToManyFromRequest($request);
        $this->delHasManyFromRequest($request);
        $this->mapHasManyFromRequest($request);
    }

    public function handleFileUpload($v, $request, $key, $l = null)
    {
        $request_key = ($l) ? $key . '.' . $l : $key;
        $current = ($l) ? $this->translateOrNew($l) : $this;

        $name = '';
        if($request->$key && strpos($v['rules'], 'base64image') !== false){
            $img = Image::make($request->$key);
            $mime = $img->mime();
            if($mime== 'image/jpeg') {
                $img->encode('jpg',100);
                $picture_name = (!$name) ? date('Ymd_His').'_'.str_random(40).'.jpg' : $name;
            }else{
                $img->encode('png',100);
                $picture_name = (!$name) ? date('Ymd_His').'_'.str_random(40).'.png' : $name;
            }
            $dir = self::getResourcePath('IMAGE', true);
            $img->save($dir.'/' . $picture_name,100);
            $img->destroy();

            if ($current->$key) $this->deleteImage($current->$key);
            $current->$key = $picture_name;
            return $picture_name;
        }




        $is_img = (strpos($v['rules'], 'image') !== false);
        if ($v['type'] == 'file') {
            if ($request->hasFile($request_key)) {
                if ($request->file($request_key)->isValid()) {
                    if ($is_img) {
                        //image
                        $resource_name = $this->storeImage($request->file($request_key), $v);
                    } else {
                        //text file
                        $resource_name = $this->storeFile($request->file($request_key));
                    }

                    if ($current->$key && $is_img) $this->deleteImage($current->$key);
                    else if ($current->$key) $this->deleteFile($current->$key);
                    $current->$key = $resource_name;
                }
            }
        }
    }

    public function delete()
    {
        $picture = $this->getPicturesFields();
        if (!empty($picture)) {
            foreach ($picture as $k => $p) {
                $this->deleteImage($p);
            }
        }
        $this->delAllHasManyFiles();
        $this->cache_forget();
        parent::delete();
    }

    /**
     * @param $file
     * @param array $v
     * @return string
     */
    protected function storeImage($file, $v = [])
    {
        ini_set('memory_limit', '256M');

        $w = (isset($v['w'])) ? intval($v['w']) : null;
        $h = (isset($v['h'])) ? intval($v['h']) : null;
        $fit = (isset($v['fit']) && $v['fit'] == true) ? true : false;
        $thumb = (isset($v['thumb']) && $v['thumb'] == true) ? true : false;
        $file_name = date('Ymd_His') . '_' . str_random(50) . '.' . $file->getClientOriginalExtension();
        $image = Image::make($file);
        if ($fit && $w && $h) {
            $image->fit($w, $h);
        } else if ($w) {
            $image->widen($w);
        }else if($image->width() > 1920){
            $image->widen(1920);
        }

        $dir = self::getResourcePath('IMAGE', true);

        if ($file->getClientOriginalExtension() == 'gif') {
            copy($file->getRealPath(), $dir . '/' . $file_name);
        }
        else {
            $image->save($dir . '/' . $file_name,90);
        }

        if ($thumb) {
            $image->widen(600);
            $image->save($dir . '/' . '_thumb_' . $file_name,90);
        }

        $image->destroy();
        return $file_name;
    }

    /**
     * @param $file
     * @return string
     */
    protected function storeFile($file)
    {
        $dir = self::getResourcePath('FILE', true);
        $file_name = date('Ymd_His') . '_' . str_random(50) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $file_name);
        return $file_name;
    }

    protected function deleteImage($file_name)
    {
        $dir = self::getResourcePath('IMAGE');
        $file = $dir . '/' . $file_name;
        if (is_readable($file)) {
            @unlink($file);
        }
        $file = $dir . '/' . '_thumb_' . $file_name;
        if (is_readable($file)) {
            @unlink($file);
        }
    }

    protected function deleteFile($file_name)
    {
        $dir = self::getResourcePath('FILE');
        $file = $dir . '/' . $file_name;
        if (is_readable($file)) {
            @unlink($file);
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function createPath($path)
    {
        if (File::isDirectory($path)) return true;
        $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
        $return = self::createPath($prev_path);
        if ($return && is_writable($prev_path)) {
            $result = File::makeDirectory($path, 0755, false, false);
            if ($result && basename($path) == 'images' || basename($path) == 'files') {
                File::copy(realpath(dirname(__FILE__)) . '/../Utils/' . basename($path) . '/.htaccess', $path . '.htaccess');
            }
            return $result;
        } else {
            return false;
        }


        //return ($return && is_writable($prev_path)) ? File::makeDirectory($path, 0755, false, false) : false;
    }

    public static function getResourcePath($type = 'IMAGE', $create = false)
    {
        $subdir = Str::plural(strtolower($type));
        $dir = (env('STORE_' . $type . '_PATH')) ?
            env('STORE_' . $type . '_PATH') . '/' . $subdir . '/' . self::getResourceDir() :
            public_path() . '/res' . '/' . $subdir . '/' . self::getResourceDir();

        if ($create) self::createPath($dir);
        return $dir;
    }


    public static function getResourceDir()
    {
        return with(new static)->getTable();

    }



    ////////////////////////////////HANDLE CACHE////////////////////////////////


    /**
     * @param int $minutes
     */
    public function cache_put($minutes = 60)
    {
        Cache::put(self::cache_get_key($this->id), $this, $minutes);
    }

    public function cache_forget()
    {
        Cache::forget(self::cache_get_key($this->id));
    }

    /**
     * @param int $id
     * @param int $minutes
     * @return mixed
     */
    public static function cache_remember($id, $minutes = 60)
    {
        $item = Cache::remember(self::cache_get_key($id), $minutes, function () use ($id) {
            return self::findOrFail($id);
        });
        return $item;
    }

    /**
     * @param int $id
     * @return mixed
     */
    public static function cache_get($id)
    {
        $item = Cache::get(self::cache_get_key($id), function () use ($id) {
            return self::findOrFail($id);
        });
        return $item;
    }

    /**
     * @param int $id
     * @return mixed
     */
    protected static function cache_get_key($id)
    {
        return md5(with(new static)->getTable() . '_' . $id);
    }


    public static function checkIsInit()
    {
        if (!isset(self::$fields) || empty(self::$fields)) {
            throw new BasemodelfieldsInitException('Attention: Set BaseModelFields fields');
        }
        if (!isset(self::$fields['main']['code_name']) || empty(self::$fields['main']['code_name'])) {
            throw new BasemodelfieldsInitException('Attention: Set code_name field in  BaseModelFields::fields.main');
        }
        $locales = config('app.locales');
        if (isset(self::$fields['localized_fields']) && (!$locales || !is_array($locales))) {
            throw new BasemodelfieldsInitException('Attention: Set config app locales');
        }

        if (!self::getValidatorRules()) {
            throw new BasemodelfieldsInitException('Attention: Set rules to all fields in BaseModelFields');
        }

        return true;
    }
}
