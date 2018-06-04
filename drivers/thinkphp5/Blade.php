<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace think\view\driver;
use think\App;
use think\exception\TemplateNotFoundException;
use think\Loader;
use think\Log;
use think\Request;
use terranc\Blade\Compilers\BladeCompiler;
use terranc\Blade\Engines\CompilerEngine;
use terranc\Blade\FileViewFinder;
use terranc\Blade\Factory;
use think\facade\Env;

class Blade
{
    // 模板引擎实例
    private $template;
    // 模板引擎参数
    protected $config = [
        // 视图基础目录（集中式）
        'view_base'   => '',
        // 是否开启模板编译缓存,设为false则每次都会重新编译
        'tpl_cache'          => true,
        // 模板起始路径
        'view_path'   => '',
        'tpl_begin'   => '{{',
        'tpl_end'   => '}}',
        'tpl_raw_begin'   => '{!!',
        'tpl_raw_end'   => '!!}',
        'view_cache_path'   => Env::get('runtime_path') . 'temp' . DIRECTORY_SEPARATOR, // 模板缓存目录
        // 模板文件后缀
        'view_suffix' => 'blade.php',
    ];
    public function __construct($config = [])
    {
        $this->config($config);
    }
    private function boot($config = []) {
        $this->config = array_merge($this->config, $config);
        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = App::$modulePath . 'view' . DIRECTORY_SEPARATOR;
        }
        if ($this->config['cache']['cache_subdir']) {
            // 使用子目录
            $this->config['view_cache_path'] = $this->config['view_cache_path'] . DIRECTORY_SEPARATOR . substr($this->config['view_cache_path'], 0, 2) . DIRECTORY_SEPARATOR . substr($this->config['view_cache_path'], 2);
        }
        if ($this->options['cache']['prefix']) {
            $name = $this->config['cache']['prefix'] . DIRECTORY_SEPARATOR . $name;
        }
        $compiler = new BladeCompiler($this->config['view_cache_path'], $this->config['tpl_cache']);
        $compiler->setContentTags($this->config['tpl_begin'], $this->config['tpl_end'], true);
        $compiler->setContentTags($this->config['tpl_begin'], $this->config['tpl_end'], false);
        $compiler->setRawTags($this->config['tpl_raw_begin'], $this->config['tpl_raw_end'], false);
        $engine = new CompilerEngine($compiler);
        $finder = new FileViewFinder([$this->config['view_path']], [$this->config['view_suffix'], 'tpl']);
        // 实例化 Factory
        $this->template = new Factory($engine, $finder);
    }
    /**
     * 检测是否存在模板文件
     * @access public
     * @param string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists($template)
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }
        return is_file($template);
    }
    /**
     * 渲染模板文件
     * @access public
     * @param string    $template 模板文件
     * @param array     $data 模板变量
     * @param array     $mergeData 附加变量
     * @param array     $config 参数
     * @return void
     */
    public function fetch($template, $data = [], $mergeData = [], $config = [])
    {
        $this->config($config);
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }
        // 模板不存在 抛出异常
        if (!is_file($template)) {
            throw new TemplateNotFoundException('template not exists:' . $template, $template);
        }
        // 记录视图信息
        App::$debug && Log::record('[ VIEW ] ' . $template . ' [ ' . var_export(array_keys($data), true) . ' ]', 'info');
        echo $this->template->file($template, $data, $mergeData)->render();
    }
    /**
     * 渲染模板内容
     * @access public
     * @param string    $template 模板内容
     * @param array     $data 模板变量
     * @param array     $mergeData 附加变量
     * @param array     $config 参数
     * @return void
     */
    public function display($template, $data = [], $mergeData = [], $config = [])
    {
        $this->config($config);
        return $this->template->make($template, $data, $mergeData)->render();
    }
    /**
     * 自动定位模板文件
     * @access private
     * @param string $template 模板文件规则
     * @return string
     */
    private function parseTemplate($template)
    {
        // 分析模板文件规则
        $request = Request::instance();
        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            list($module, $template) = explode('@', $template);
        }
        if ($this->config['view_base']) {
            // 基础视图目录
            $module = isset($module) ? $module : $request->module();
            $path   = $this->config['view_base'] . ($module ? $module . DIRECTORY_SEPARATOR : '');
        } else {
            $path = isset($module) ? Env::get('app_path') . $module . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR : $this->config['view_path'];
        }
        $depr = $this->config['view_depr'];
        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = Loader::parseName($request->controller());
            if ($controller) {
                if ('' == $template) {
                    // 如果模板文件名为空 按照默认规则定位
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $request->action();
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }
        return $path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
    }
    /**
     * 配置或者获取模板引擎参数
     * @access private
     * @param string|array  $name 参数名
     * @param mixed         $value 参数值
     * @return mixed
     */
    public function config($name, $value = null)
    {
        if (is_array($name)) {
            $this->config = array_merge($this->config, $name);
        } elseif (is_null($value)) {
            return $this->config[$name];
        } else {
            $this->config[$name]   = $value;
        }
        $this->boot();
    }
    public function __call($method, $params)
    {
        return call_user_func_array([$this->template, $method], $params);
    }
}
