# Think-Blade
Blade template engine with thinkphp 5. (component & slot support)

# Installation
composer require terranc/think-blade

conig.php:
```php
'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Blade',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'blade.php',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}}',
        'tpl_raw_begin'    => '{!!',
        'tpl_raw_end'    => '{!!',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],
```

# Usage
```html
<header id="navbar">
	<div class="row navbar-inner">
		<div class="col-xs-6 brand-block">
			<h4><a href="{{ url('/admin') }}"><img src="/assets/admin/images/logo.png"></a> · 管理后台
			</h4>
			<a href="javascript:;" class="cd_nav_trigger"><span></span></a>
		</div>
		<div class="col-xs-6 text-right user-block">
			你好，{{ $manage_user->nickname }}({{ $manage_user->username }})
			<span class="gap-line"></span>
			<a href="{{ url('/manage/index/account') }}" class="item">修改资料</a>
			<span class="gap-line"></span>
			<a href="{{ url('/manage/start/logout') }}" class="confirm item" title="确认要退出吗？">退出</a>
		</div>
	</div>
</header>
```

# DOC

https://laravel.com/docs/5.4/blade

http://d.laravel-china.org/docs/5.4/blade (中文)
