# copy-database

## Instalation
1. Install package
```bash
composer require mykolavoitovych/copy-database
```

2. Publish config file

```bash
php artisan vendor:publish --provider="Mykolavoitovych\CopyDatabase\Providers\CopyDatabaseServiceProvider"
```
3. Add provider to config/app.php
```php
\Mykolavoitovych\CopyDatabase\Providers\CopyDatabaseServiceProvider::class
```

## Usage
```bash
php artisan database:copy
```
or if you want to copy only one table
```php
artisan database:copy user {tablename}
```

Also you can change some configs in config file
