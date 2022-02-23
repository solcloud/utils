## Utils

some utils for many things

### Workers

_container.php_

```php
$container = new \Pimple\Container();
$container->register(new \Solcloud\Utils\SolcloudProvider());
return $container;
```

_run.php_

```php
if ('dev' === getenv('DEVTOKEN')) {
    require __DIR__ . '/vendor/solcloud/utils/worker/baseDebug.php';
} else {
    require __DIR__ . '/vendor/solcloud/utils/worker/base.php';
}
```
