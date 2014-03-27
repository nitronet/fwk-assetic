# fwk-assetic

Adds [Assetic](https://github.com/kriswallsmith/assetic) to [Fwk\Core](https://github.com/fwk/Core) Applications.

## Installation

### 1: Install the sources

Via [Composer](http://getcomposer.org):

```
{
    "require": {
        "nitronet/fwk-assetic": "dev-master",
    }
}
```

If you don't use Composer, you can still [download](https://github.com/nitronet/fwk-assetic/zipball/master) this repository and add it
to your ```include_path``` [PSR-0 compatible](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)

### 2: Configure Assetic as a Service

Configures Assetic classes in your [Di](https://github.com/fwk/Di) Container, and tweak it the way you want. To make it customizable,
we'll also use some INI properties:

``` ini
[services]
; :packageDir is the directory where your service.xml is.
assetic.assets.directory = :packageDir/MyApp/templates/assets
assetic.debug = true
assetic.action.name = Asset
```

services.xml:
``` xml
<!-- Assetic AssetFactory -->
<class-definition name="assetic.AssetFactory" class="Assetic\Factory\AssetFactory" shared="true">
    <argument>:assetic.assets.directory</argument>
    <argument>:assetic.debug</argument>
    <call method="setFilterManager">
        <argument>@assetic.FilterManager</argument>
    </call>
</class-definition>

<!-- Assetic FilterManager -->
<class-definition name="assetic.FilterManager" class="Assetic\FilterManager" shared="true" />

<!-- Fwk Service -->
<class-definition name="assetic" class="FwkAssetic\AssetsService" shared="true">
    <argument>@assetic.AssetFactory</argument>
</class-definition>

<!-- Fwk ViewHelper -->
<class-definition name="_vh.AsseticViewHelper" class="FwkAssetic\AssetViewHelper" shared="true">
    <argument desc="Name of the Fwk Service">assetic</argument>
    <argument desc="Name of your Url ViewHelper">url</argument>
    <argument desc="Enable Assetic debugging">:assetic.debug</argument>
    <argument desc="Asset action name">:assetic.action.name</argument>
</class-definition>
```

Next, you want to add the ViewHelper to your ViewHelperService:
``` xml
<!-- You probably already have this one -->
<class-definition name="viewHelper" class="Fwk\Core\Components\ViewHelper\ViewHelperService" shared="true">
    <!-- ... -->
    <call method="add">
        <argument desc="Name of the helper (to be used in templates)">asset</argument>
        <argument desc="Instance of the AsseticViewHelper">@_vh.AsseticViewHelper</argument>
    </call>
</class-definition>
``` 

### 3: Register action and routes to the Asset Action

fwk.xml:

``` xml
<action name="Asset" shortcut="FwkAssetic\Controllers\AssetAction:show" />
```

If you use the UrlRewriterService, you can also customize the action route:

``` xml 
<url-rewrite>
    <!-- ... --->
    <url route="/asset/:asset" action="Asset">
        <param name="asset" regex=".*" />
    </url>
</url-rewrite>
```

### 4: That's it!

You can now use the viewHelper in your templates, like so:

``` php
<?php foreach($this->_helper->asset(array('/path/to/site.css'), $filters = array(), $output = "site") as $asset): ?>
    <link href="<?php echo $asset; ?>" media="all" rel="stylesheet" type="text/css" />
<?php endforeach; ?>
```

Or if you use [Twig](https://github.com/nitronet/fwk-twig):
``` django
{% for asset in _helper.asset(['/path/to/site.css'], [], 'site', true) %}
    <link href="{{ asset }}" media="all" rel="stylesheet" type="text/css" />
{% endfor %}
```

## CssRewrite

To display images and resources referenced in your CSS, you'll have to use a "rewrite" filter. 

Back to your services.xml, declare the filter definition and change your ```assetic.FilterManager``` definition to this:
``` xml
<!-- Assetic FilterManager -->
<class-definition name="assetic.FilterManager" class="Assetic\FilterManager" shared="true">
    <call method="set">
        <argument desc="Filter alias">cssrewrite</argument>
        <argument desc="Filter instance">@assetic.CssRewriteFilter</argument>
    </call>
</class-definition>

<!-- CssRewriteFilter -->
<class-definition name="assetic.CssRewriteFilter" class="FwkAssetic\Filters\CssRewriteFilter" shared="true">
    <argument>@_vh.AsseticViewHelper</argument>
</class-definition>
```

Now, just call the *cssrewrite* filter in your templates:
``` php 
<?php foreach($this->_helper->asset('/path/to/site.css', 'cssrewrite') as $asset): ?>
    <link href="<?php echo $asset; ?>" media="all" rel="stylesheet" type="text/css" />
<?php endforeach; ?>
```

That's it!

## Configure Caching

To prevent 404 errors on assets urls, you'll have to use the Assetic caching mechanism, which require some more configuration...

### 1: Configuration variables and caching directory

Create a directory where Assetic can cache the assets:
``` sh
$ mkdir /path/to/app/cache 
$ chmod 777 /path/to/app/cache
```

Configure the application:
``` ini
assetic.use.cache = true
assetic.cache.directory = /path/to/app/cache
; could be content or modification 
assetic.cache.strategy = content
```

### 2: Configure Services

Adds the following definitions to your services.xml
``` xml
<!-- Assetic FilesystemCache -->
<class-definition name="assetic.FilesystemCache" class="Assetic\Cache\FilesystemCache" shared="true">
    <argument>:assetic.cache.directory</argument>
</class-definition>
<!-- Assetic CacheBustingWorker -->
<class-definition name="assetic.CacheBustingWorker" class="Assetic\Factory\Worker\CacheBustingWorker" shared="true">
    <argument>:assetic.cache.strategy</argument>
</class-definition>
```

And reconfigure previous definitions:
``` xml
<!-- Assetic AssetFactory -->
<class-definition name="assetic.AssetFactory" class="Assetic\Factory\AssetFactory" shared="true">
    <argument>:assetic.assets.directory</argument>
    <argument>:assetic.debug</argument>
    <call method="setFilterManager">
        <argument>@assetic.FilterManager</argument>
    </call>
    <!-- enable assetic cache -->
    <call method="addWorker">
        <argument>@assetic.CacheBustingWorker</argument>
    </call>
</class-definition>

<!-- Fwk Service -->
<class-definition name="assetic" class="FwkAssetic\AssetsService" shared="true">
    <argument>@assetic.AssetFactory</argument>
    <!-- enable assetic cache -->
    <argument>@assetic.FilesystemCache</argument>
</class-definition>
```

### 3: You're done!

Now you can refresh your pages and admire your frontend skills ;)


## Full XML configuration

``` xml
<!--
    ASSETIC (fwk-assetic)
-->
<class-definition name="assetic.CssRewriteFilter" class="FwkAssetic\Filters\CssRewriteFilter" shared="true">
    <argument>@_vh.AsseticViewHelper</argument>
</class-definition>
<class-definition name="assetic.FilterManager" class="Assetic\FilterManager" shared="true">
    <call method="set">
        <argument>cssrewrite</argument>
        <argument>@assetic.CssRewriteFilter</argument>
    </call>
</class-definition>
<class-definition name="assetic.FilesystemCache" class="Assetic\Cache\FilesystemCache" shared="true">
    <argument>:assetic.cache.directory</argument>
</class-definition>
<class-definition name="assetic.CacheBustingWorker" class="Assetic\Factory\Worker\CacheBustingWorker" shared="true">
    <argument>:assetic.cache.strategy</argument>
</class-definition>
<class-definition name="assetic.AssetFactory" class="Assetic\Factory\AssetFactory" shared="true">
    <argument>:assetic.assets.directory</argument>
    <argument>:assetic.debug</argument>
    <call method="setFilterManager">
        <argument>@assetic.FilterManager</argument>
    </call>
    <!-- enable assetic cache -->
    <call method="addWorker">
        <argument>@assetic.CacheBustingWorker</argument>
    </call>
</class-definition>

<class-definition name="assetic" class="FwkAssetic\AssetsService" shared="true">
    <argument>@assetic.AssetFactory</argument>
    <argument>@assetic.FilesystemCache</argument>
</class-definition>

<class-definition name="_vh.AsseticViewHelper" class="\FwkAssetic\AssetViewHelper" shared="true">
    <argument>assetic</argument>
    <argument>url</argument>
    <argument>:assetic.debug</argument>
    <argument>:assetic.action.name</argument>
</class-definition>
```

## Contributions / Community

- Issues on Github: https://github.com/nitronet/fwk-assetic/issues
- Follow *Fwk* on Twitter: [@phpfwk](https://twitter.com/phpfwk)
